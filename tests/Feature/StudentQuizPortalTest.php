<?php

namespace Tests\Feature;

use App\Models\ClassSection;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StudentQuizPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_start_answer_and_submit_an_enrolled_quiz(): void
    {
        $this->seed();
        [$quiz, $questions, $correctOptions] = $this->createQuiz();
        $student = User::query()->where('email', 'mahasiswa@kampus.test')->firstOrFail();

        $this->actingAs($student)
            ->get(route('portal.quizzes.show', $quiz))
            ->assertOk()
            ->assertSee($quiz->title);

        $response = $this->actingAs($student)->post(route('portal.quizzes.start', $quiz));
        $attempt = QuizAttempt::query()->where('quiz_id', $quiz->id)->firstOrFail();

        $response->assertRedirect(route('portal.quiz-attempts.show', $attempt));
        $this->assertCount(2, $attempt->question_order);
        $this->assertCount(2, $attempt->option_order);
        $this->assertNotNull($attempt->expires_at);

        $this->actingAs($student)
            ->get(route('portal.quiz-attempts.show', $attempt))
            ->assertOk()
            ->assertSee('Pengerjaan CBT');

        foreach ($questions as $index => $question) {
            $this->actingAs($student)
                ->post(route('portal.quiz-attempts.answer', [$attempt, $question]), [
                    'selected_option_id' => $correctOptions[$index]->id,
                ])
                ->assertRedirect()
                ->assertSessionHas('success');
        }

        $this->actingAs($student)
            ->post(route('portal.quiz-attempts.submit', $attempt))
            ->assertRedirect(route('portal.quiz-attempts.show', $attempt))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('quiz_attempts', [
            'id' => $attempt->id,
            'status' => 'graded',
            'score' => '10.00',
        ]);
        $this->assertDatabaseCount('quiz_answers', 2);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'quiz.attempt_started',
            'entity_id' => $attempt->id,
        ]);
    }

    public function test_non_student_cannot_access_student_quiz_attempt(): void
    {
        $this->seed();
        [$quiz] = $this->createQuiz();
        $student = User::query()->where('email', 'mahasiswa@kampus.test')->firstOrFail();
        $lecturer = User::query()->where('email', 'dosen@kampus.test')->firstOrFail();

        $this->actingAs($student)->post(route('portal.quizzes.start', $quiz));
        $attempt = QuizAttempt::query()->where('quiz_id', $quiz->id)->firstOrFail();

        $this->actingAs($lecturer)
            ->get(route('portal.quiz-attempts.show', $attempt))
            ->assertForbidden();
    }

    public function test_expired_attempt_rejects_answers_and_persists_expired_status(): void
    {
        $this->seed();
        [$quiz, $questions, $correctOptions] = $this->createQuiz();
        $student = User::query()->where('email', 'mahasiswa@kampus.test')->firstOrFail();

        $this->actingAs($student)->post(route('portal.quizzes.start', $quiz));
        $attempt = QuizAttempt::query()->where('quiz_id', $quiz->id)->firstOrFail();
        $attempt->forceFill(['expires_at' => now()->subSecond()])->save();

        $this->actingAs($student)
            ->post(route('portal.quiz-attempts.answer', [$attempt, $questions[0]]), [
                'selected_option_id' => $correctOptions[0]->id,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('quiz');

        $this->assertDatabaseHas('quiz_attempts', [
            'id' => $attempt->id,
            'status' => 'expired',
        ]);
        $this->assertDatabaseMissing('quiz_answers', [
            'quiz_attempt_id' => $attempt->id,
        ]);
    }

    private function createQuiz(): array
    {
        $section = ClassSection::query()->firstOrFail();
        $enrollment = StudentEnrollment::query()->firstOrFail();
        $bank = QuestionBank::create([
            'university_id' => $section->offering->course->university_id,
            'name' => 'Bank CBT '.$enrollment->id,
        ]);

        $questions = collect();
        $correctOptions = collect();

        foreach (['Hasil 2 + 2?', 'Laravel menggunakan bahasa PHP?'] as $index => $prompt) {
            $question = Question::create([
                'question_bank_id' => $bank->id,
                'type' => $index === 0 ? 'single_choice' : 'true_false',
                'prompt' => $prompt,
                'points' => 5,
            ]);
            $correctOptions->push(QuestionOption::create([
                'question_id' => $question->id,
                'option_text' => $index === 0 ? '4' : 'Benar',
                'is_correct' => true,
                'sort_order' => 1,
            ]));
            QuestionOption::create([
                'question_id' => $question->id,
                'option_text' => $index === 0 ? '5' : 'Salah',
                'is_correct' => false,
                'sort_order' => 2,
            ]);
            $questions->push($question);
        }

        $quiz = Quiz::create([
            'class_section_id' => $section->id,
            'title' => 'CBT Algoritma Dasar',
            'status' => 'open',
            'duration_minutes' => 30,
            'attempt_limit' => 2,
            'randomize_questions' => true,
            'randomize_options' => true,
        ]);

        foreach ($questions as $index => $question) {
            $quiz->questions()->attach($question->id, [
                'id' => (string) Str::ulid(),
                'sort_order' => $index + 1,
                'points' => 5,
            ]);
        }

        return [$quiz, $questions, $correctOptions];
    }
}
