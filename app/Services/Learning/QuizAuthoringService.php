<?php

namespace App\Services\Learning;

use App\Models\AuditLog;
use App\Models\ClassSection;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class QuizAuthoringService
{
    private const OBJECTIVE_TYPES = ['single_choice', 'multiple_choice', 'multiple_answer', 'true_false'];

    public function createBank(array $data, User $actor): QuestionBank
    {
        $universityId = $actor->employee?->university_id;
        if (! $universityId) {
            throw ValidationException::withMessages(['authorization' => 'Identitas institusi dosen tidak ditemukan.']);
        }

        return DB::transaction(function () use ($data, $actor, $universityId) {
            $bank = QuestionBank::query()->create([
                'university_id' => $universityId,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_active' => true,
            ]);
            $this->audit($actor, $bank, 'quiz.bank_created', ['name' => $bank->name]);

            return $bank;
        });
    }

    public function createQuestion(QuestionBank $bank, array $data, User $actor): Question
    {
        return DB::transaction(function () use ($bank, $data, $actor) {
            $bank = QuestionBank::query()->lockForUpdate()->findOrFail($bank->id);
            $this->authorizeBank($bank, $actor);

            $type = $data['type'];
            $options = collect($data['options'] ?? [])->map(fn ($value) => trim((string) $value))->filter()->values();
            $correctAnswers = collect($data['correct_answers'] ?? [])->map(fn ($value) => mb_strtolower(trim((string) $value)))->filter()->values();

            if (in_array($type, self::OBJECTIVE_TYPES, true)) {
                if ($options->count() < 2) {
                    throw ValidationException::withMessages(['options_text' => 'Soal objektif membutuhkan minimal dua opsi.']);
                }
                if ($correctAnswers->isEmpty()) {
                    throw ValidationException::withMessages(['correct_answers' => 'Minimal satu jawaban benar wajib ditentukan.']);
                }
                if ($type !== 'multiple_answer' && $type !== 'multiple_choice' && $correctAnswers->count() !== 1) {
                    throw ValidationException::withMessages(['correct_answers' => 'Tipe soal ini hanya boleh memiliki satu jawaban benar.']);
                }
                if ($correctAnswers->contains(fn ($answer) => ! $options->contains(fn ($option) => mb_strtolower($option) === $answer))) {
                    throw ValidationException::withMessages(['correct_answers' => 'Jawaban benar harus sama persis dengan salah satu opsi.']);
                }
            }

            $question = Question::query()->create([
                'question_bank_id' => $bank->id,
                'type' => $type,
                'prompt' => $data['prompt'],
                'points' => $data['points'],
                'explanation' => $data['explanation'] ?? null,
                'is_active' => true,
            ]);

            foreach ($options as $position => $optionText) {
                QuestionOption::query()->create([
                    'question_id' => $question->id,
                    'option_text' => $optionText,
                    'is_correct' => $correctAnswers->contains(mb_strtolower($optionText)),
                    'sort_order' => $position + 1,
                ]);
            }

            $this->audit($actor, $question, 'quiz.question_created', ['type' => $type]);

            return $question->load('options');
        });
    }

    public function createQuiz(ClassSection $section, array $data, User $actor): Quiz
    {
        return DB::transaction(function () use ($section, $data, $actor) {
            $section = ClassSection::query()->with(['lecturers.employee', 'offering.course'])->lockForUpdate()->findOrFail($section->id);
            $this->authorizeSection($section, $actor);
            if (! empty($data['starts_at']) && ! empty($data['ends_at']) && $data['ends_at'] <= $data['starts_at']) {
                throw ValidationException::withMessages(['ends_at' => 'Waktu selesai harus setelah waktu mulai.']);
            }

            $questionIds = collect($data['question_ids'])->unique()->values();
            $questions = Question::query()
                ->whereIn('id', $questionIds)
                ->whereHas('questionBank', fn ($query) => $query->where('university_id', $section->offering->course->university_id))
                ->get();
            if ($questions->count() !== $questionIds->count()) {
                throw ValidationException::withMessages(['question_ids' => 'Pilihan soal tidak valid untuk institusi kelas ini.']);
            }

            $quiz = Quiz::query()->create([
                'class_section_id' => $section->id,
                'title' => $data['title'],
                'instructions' => $data['instructions'] ?? null,
                'status' => $data['status'],
                'starts_at' => $data['starts_at'] ?? null,
                'ends_at' => $data['ends_at'] ?? null,
                'duration_minutes' => $data['duration_minutes'] ?? null,
                'attempt_limit' => $data['attempt_limit'],
                'randomize_questions' => $data['randomize_questions'] ?? false,
                'randomize_options' => $data['randomize_options'] ?? false,
            ]);

            foreach ($questionIds as $position => $questionId) {
                $question = $questions->firstWhere('id', $questionId);
                $quiz->questions()->attach($questionId, [
                    'id' => (string) Str::ulid(),
                    'sort_order' => $position + 1,
                    'points' => $question->points,
                ]);
            }

            $this->audit($actor, $quiz, 'quiz.created', ['status' => $quiz->status, 'question_count' => $questionIds->count()]);

            return $quiz->load('questions');
        });
    }

    public function gradeAnswer(QuizAnswer $answer, string|int|float $score, ?string $feedback, User $actor): QuizAttempt
    {
        return DB::transaction(function () use ($answer, $score, $feedback, $actor) {
            $answer = QuizAnswer::query()
                ->with(['question', 'attempt.quiz.classSection.lecturers.employee'])
                ->lockForUpdate()
                ->findOrFail($answer->id);
            $attempt = QuizAttempt::query()->with(['quiz.questions', 'answers.question'])->lockForUpdate()->findOrFail($answer->quiz_attempt_id);
            $this->authorizeSection($answer->attempt->quiz->classSection, $actor);

            if ($attempt->status !== 'submitted') {
                throw ValidationException::withMessages(['attempt' => 'Hanya attempt yang menunggu penilaian manual yang dapat dinilai.']);
            }
            if (in_array($answer->question->type, self::OBJECTIVE_TYPES, true)) {
                throw ValidationException::withMessages(['answer' => 'Jawaban objektif dinilai otomatis.']);
            }

            $pivotQuestion = $attempt->quiz->questions->firstWhere('id', $answer->question_id);
            $maximum = (float) ($pivotQuestion?->pivot?->points ?? $answer->question->points ?? 0);
            $numericScore = (float) $score;
            if ($numericScore < 0 || $numericScore > $maximum) {
                throw ValidationException::withMessages(['score' => "Nilai harus berada antara 0 dan {$maximum}."]);
            }

            $answer->forceFill([
                'score' => number_format($numericScore, 2, '.', ''),
                'feedback' => $feedback,
                'graded_by' => $actor->id,
                'graded_at' => now(),
            ])->save();

            $attempt->load(['quiz.questions', 'answers.question']);
            $manualAnswers = $attempt->answers->filter(fn ($item) => ! in_array($item->question->type, self::OBJECTIVE_TYPES, true));
            $allManualGraded = $manualAnswers->isNotEmpty() && $manualAnswers->every(fn ($item) => $item->score !== null);
            $total = $attempt->answers->sum(fn ($item) => (float) ($item->score ?? 0));

            $attempt->forceFill([
                'score' => number_format($total, 2, '.', ''),
                'status' => $allManualGraded ? 'graded' : 'submitted',
            ])->save();

            $this->audit($actor, $answer, 'quiz.answer_graded', ['score' => $answer->score, 'attempt_status' => $attempt->status]);

            return $attempt->fresh();
        });
    }

    private function authorizeBank(QuestionBank $bank, User $actor): void
    {
        if ($actor->employee?->university_id !== $bank->university_id) {
            throw ValidationException::withMessages(['authorization' => 'Bank soal berada di luar institusi Anda.']);
        }
    }

    private function authorizeSection(ClassSection $section, User $actor): void
    {
        $lecturerId = $actor->employee?->lecturerProfile?->id;
        if (! $lecturerId || ! $section->lecturers->contains('id', $lecturerId)) {
            throw ValidationException::withMessages(['authorization' => 'Hanya dosen pengampu yang dapat mengelola quiz kelas ini.']);
        }
    }

    private function audit(User $actor, object $entity, string $event, array $values): void
    {
        AuditLog::query()->create([
            'user_id' => $actor->id,
            'event' => $event,
            'module' => 'learning',
            'entity_type' => $entity::class,
            'entity_id' => $entity->id,
            'new_values' => $values,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
            'user_agent' => app()->runningInConsole() ? null : request()->userAgent(),
        ]);
    }
}
