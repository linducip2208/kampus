<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\StudentEnrollment;
use App\Services\Learning\QuizAttemptService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StudentQuizController extends Controller
{
    public function show(Request $request, Quiz $quiz): View
    {
        $enrollment = $this->enrollment($request);
        $this->assertEnrolled($enrollment, $quiz);
        $attempts = $quiz->attempts()->where('student_enrollment_id', $enrollment->id)->latest()->get();

        return view('portal.quiz', compact('enrollment', 'quiz', 'attempts'));
    }

    public function start(Request $request, Quiz $quiz, QuizAttemptService $service): RedirectResponse
    {
        $enrollment = $this->enrollment($request);
        $attempt = $service->start($quiz, $enrollment, $request->user());

        return redirect()->route('portal.quiz-attempts.show', $attempt);
    }

    public function attempt(Request $request, QuizAttempt $attempt): View
    {
        $enrollment = $this->enrollment($request);
        $attempt = $this->ownedAttempt($attempt, $enrollment);
        $attempt->load(['quiz.questions.options', 'answers']);
        $byId = $attempt->quiz->questions->keyBy('id');
        $answers = $attempt->answers->keyBy('question_id');
        $questionRows = collect($attempt->question_order ?: $byId->keys()->all())
            ->map(function ($id) use ($attempt, $byId, $answers) {
                $question = $byId->get($id);
                if (! $question) {
                    return null;
                }

                $optionIds = data_get($attempt->option_order, $question->id, $question->options->pluck('id')->all());

                return [
                    'question' => $question,
                    'answer' => $answers->get($question->id),
                    'options' => collect($optionIds)
                        ->map(fn ($optionId) => $question->options->firstWhere('id', $optionId))
                        ->filter()
                        ->values(),
                ];
            })
            ->filter()
            ->values();

        return view('portal.quiz-attempt', compact('enrollment', 'attempt', 'questionRows'));
    }

    public function answer(Request $request, QuizAttempt $attempt, Question $question, QuizAttemptService $service): RedirectResponse
    {
        $enrollment = $this->enrollment($request);
        $attempt = $this->ownedAttempt($attempt, $enrollment);
        $data = $request->validate([
            'selected_option_id' => ['nullable', 'ulid'],
            'selected_option_ids' => ['nullable', 'array'],
            'selected_option_ids.*' => ['ulid'],
            'answer_text' => ['nullable', 'string', 'max:20000'],
        ]);
        $selected = in_array($question->type, ['multiple_choice', 'multiple_answer'], true)
            ? ($data['selected_option_ids'] ?? [])
            : ($data['selected_option_id'] ?? null);
        $service->answer($attempt, $question, $selected, $data['answer_text'] ?? null);

        return back()->with('success', 'Jawaban tersimpan.');
    }

    public function submit(Request $request, QuizAttempt $attempt, QuizAttemptService $service): RedirectResponse
    {
        $enrollment = $this->enrollment($request);
        $attempt = $this->ownedAttempt($attempt, $enrollment);
        $service->submit($attempt, $request->user());

        return redirect()->route('portal.quiz-attempts.show', $attempt)->with('success', 'Quiz berhasil dikirim.');
    }

    private function enrollment(Request $request): StudentEnrollment
    {
        abort_unless($request->user()?->hasRole('mahasiswa'), 403);
        $enrollment = $request->user()->studentProfile?->enrollments()->latest()->first();
        abort_unless($enrollment, 404);

        return $enrollment;
    }

    private function assertEnrolled(StudentEnrollment $enrollment, Quiz $quiz): void
    {
        abort_unless(DB::table('study_plan_items')
            ->join('study_plans', 'study_plans.id', '=', 'study_plan_items.study_plan_id')
            ->where('study_plan_items.class_section_id', $quiz->class_section_id)
            ->where('study_plans.student_enrollment_id', $enrollment->id)
            ->whereIn('study_plans.status', ['approved', 'finalized', 'locked'])
            ->exists(), 404);
    }

    private function ownedAttempt(QuizAttempt $attempt, StudentEnrollment $enrollment): QuizAttempt
    {
        abort_unless($attempt->student_enrollment_id === $enrollment->id, 404);

        return $attempt;
    }
}
