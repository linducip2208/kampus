<?php

namespace App\Services\Learning;

use App\Models\AuditLog;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuizAttemptService
{
    public function start(Quiz $quiz, StudentEnrollment $enrollment, ?User $actor = null): QuizAttempt
    {
        return DB::transaction(function () use ($quiz, $enrollment, $actor) {
            $locked = Quiz::query()->lockForUpdate()->findOrFail($quiz->id);
            $this->assertAvailable($locked);
            $this->assertRegistered($locked, $enrollment);

            $active = QuizAttempt::query()
                ->where('quiz_id', $locked->id)
                ->where('student_enrollment_id', $enrollment->id)
                ->where('status', 'in_progress')
                ->lockForUpdate()
                ->first();

            if ($active) {
                return $active->fresh();
            }

            $attempts = QuizAttempt::query()
                ->where('quiz_id', $locked->id)
                ->where('student_enrollment_id', $enrollment->id)
                ->lockForUpdate()
                ->count();

            if ($attempts >= $locked->attempt_limit) {
                throw ValidationException::withMessages(['quiz' => 'Batas percobaan quiz sudah tercapai.']);
            }

            $attempt = QuizAttempt::create([
                'quiz_id' => $locked->id,
                'student_enrollment_id' => $enrollment->id,
                'attempt_number' => $attempts + 1,
                'started_at' => now(),
                'status' => 'in_progress',
            ]);

            $this->audit($actor, $attempt, 'quiz.attempt_started');

            return $attempt;
        });
    }

    public function answer(QuizAttempt $attempt, Question $question, array|string|null $selectedOptionIds = null, ?string $answerText = null): QuizAnswer
    {
        return DB::transaction(function () use ($attempt, $question, $selectedOptionIds, $answerText) {
            $locked = QuizAttempt::query()->lockForUpdate()->findOrFail($attempt->id);
            $quiz = Quiz::query()->findOrFail($locked->quiz_id);

            $this->assertAttemptActive($locked, $quiz);
            if (! $quiz->questions()->whereKey($question->id)->exists()) {
                throw ValidationException::withMessages(['question' => 'Pertanyaan bukan bagian dari quiz ini.']);
            }

            $optionIds = is_array($selectedOptionIds) ? array_values(array_unique($selectedOptionIds)) : ($selectedOptionIds ? [$selectedOptionIds] : []);
            $validOptionIds = $question->options()->whereIn('id', $optionIds)->pluck('id')->all();
            if (count($validOptionIds) !== count($optionIds)) {
                throw ValidationException::withMessages(['answer' => 'Pilihan jawaban tidak valid.']);
            }

            $answer = QuizAnswer::query()->firstOrNew([
                'quiz_attempt_id' => $locked->id,
                'question_id' => $question->id,
            ]);
            $answer->forceFill([
                'selected_option_ids' => $optionIds ?: null,
                'answer_text' => $answerText,
                'answered_at' => now(),
                'is_correct' => null,
                'score' => null,
            ])->save();

            return $answer->fresh();
        });
    }

    public function submit(QuizAttempt $attempt, ?User $actor = null): QuizAttempt
    {
        return DB::transaction(function () use ($attempt, $actor) {
            $locked = QuizAttempt::query()->lockForUpdate()->findOrFail($attempt->id);
            $quiz = Quiz::query()->with(['questions.options'])->findOrFail($locked->quiz_id);

            $this->assertAttemptActive($locked, $quiz);
            if ($quiz->duration_minutes && $locked->started_at->addMinutes($quiz->duration_minutes)->isPast()) {
                $locked->forceFill(['status' => 'expired', 'submitted_at' => now()])->save();
                throw ValidationException::withMessages(['quiz' => 'Waktu pengerjaan quiz sudah habis.']);
            }

            $answers = $locked->answers()->get()->keyBy('question_id');
            $total = 0.0;
            $hasManualQuestions = false;

            foreach ($quiz->questions as $question) {
                $answer = $answers->get($question->id);
                if (in_array($question->type, ['single_choice', 'multiple_choice', 'true_false'], true)) {
                    $selected = collect($answer?->selected_option_ids ?? [])->sort()->values()->all();
                    $correct = $question->options->where('is_correct', true)->pluck('id')->sort()->values()->all();
                    $isCorrect = $selected === $correct;
                    $points = (float) ($question->pivot->points ?? $question->points ?? 0);
                    if ($answer) {
                        $answer->forceFill(['is_correct' => $isCorrect, 'score' => $isCorrect ? $points : 0])->save();
                    }
                    if ($isCorrect) {
                        $total += $points;
                    }
                } else {
                    $hasManualQuestions = true;
                }
            }

            $locked->forceFill([
                'status' => $hasManualQuestions ? 'submitted' : 'graded',
                'submitted_at' => now(),
                'score' => round($total, 2),
            ])->save();
            $this->audit($actor, $locked, 'quiz.attempt_submitted', ['score' => round($total, 2)]);

            return $locked->fresh();
        });
    }

    private function assertAvailable(Quiz $quiz): void
    {
        if (! in_array($quiz->status, ['published', 'open'], true)) {
            throw ValidationException::withMessages(['quiz' => 'Quiz belum dibuka.']);
        }
        if ($quiz->starts_at?->isFuture()) {
            throw ValidationException::withMessages(['quiz' => 'Quiz belum memasuki waktu mulai.']);
        }
        if ($quiz->ends_at?->isPast()) {
            throw ValidationException::withMessages(['quiz' => 'Waktu quiz sudah berakhir.']);
        }
    }

    private function assertRegistered(Quiz $quiz, StudentEnrollment $enrollment): void
    {
        $registered = DB::table('study_plan_items')
            ->join('study_plans', 'study_plans.id', '=', 'study_plan_items.study_plan_id')
            ->where('study_plan_items.class_section_id', $quiz->class_section_id)
            ->where('study_plans.student_enrollment_id', $enrollment->id)
            ->exists();

        if (! $registered) {
            throw ValidationException::withMessages(['enrollment' => 'Mahasiswa tidak terdaftar pada kelas quiz ini.']);
        }
    }

    private function assertAttemptActive(QuizAttempt $attempt, Quiz $quiz): void
    {
        if ($attempt->status !== 'in_progress') {
            throw ValidationException::withMessages(['attempt' => 'Percobaan quiz sudah tidak aktif.']);
        }
        if ($quiz->ends_at?->isPast()) {
            throw ValidationException::withMessages(['quiz' => 'Waktu quiz sudah berakhir.']);
        }
    }

    private function audit(?User $actor, QuizAttempt $attempt, string $event, array $newValues = []): void
    {
        AuditLog::create([
            'user_id' => $actor?->id,
            'event' => $event,
            'module' => 'learning',
            'entity_type' => QuizAttempt::class,
            'entity_id' => $attempt->id,
            'old_values' => [],
            'new_values' => $newValues,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
            'user_agent' => app()->runningInConsole() ? null : request()->userAgent(),
        ]);
    }
}
