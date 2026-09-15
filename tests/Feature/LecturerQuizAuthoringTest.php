<?php

namespace Tests\Feature;

use App\Models\ClassSection;
use App\Models\QuestionBank;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Services\Learning\QuizAttemptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LecturerQuizAuthoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_lecturer_can_build_quiz_and_grade_essay_attempt(): void
    {
        $this->seed();
        $lecturer = User::query()->where('email', 'dosen@kampus.test')->firstOrFail();
        $student = User::query()->where('email', 'mahasiswa@kampus.test')->firstOrFail();
        $section = ClassSection::query()->firstOrFail();

        $this->actingAs($lecturer)
            ->post(route('lecturer.learning.question-banks.store'), [
                'name' => 'Bank Rekayasa Perangkat Lunak',
                'description' => 'Soal evaluasi berbasis studi kasus.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $bank = QuestionBank::query()->where('name', 'Bank Rekayasa Perangkat Lunak')->firstOrFail();

        $this->actingAs($lecturer)
            ->post(route('lecturer.learning.questions.store', $bank), [
                'type' => 'essay',
                'prompt' => 'Jelaskan manfaat pemisahan domain service dari presentation layer.',
                'points' => 10,
                'explanation' => 'Jawaban menilai pemahaman arsitektur dan testability.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $question = $bank->questions()->firstOrFail();

        $this->actingAs($lecturer)
            ->post(route('lecturer.learning.quizzes.store', $section), [
                'title' => 'CBT Arsitektur Aplikasi',
                'instructions' => 'Jawab dengan ringkas dan tepat.',
                'status' => 'open',
                'duration_minutes' => 45,
                'attempt_limit' => 1,
                'question_ids' => [$question->id],
                'randomize_questions' => '1',
                'randomize_options' => '0',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $quiz = Quiz::query()->where('title', 'CBT Arsitektur Aplikasi')->firstOrFail();
        $this->assertDatabaseHas('quiz_questions', [
            'quiz_id' => $quiz->id,
            'question_id' => $question->id,
        ]);

        $enrollment = StudentEnrollment::query()->firstOrFail();
        $attemptService = app(QuizAttemptService::class);
        $attempt = $attemptService->start($quiz, $enrollment, $student);
        $attemptService->answer($attempt, $question, null, 'Domain service menjaga aturan bisnis dapat digunakan ulang dan diuji.');
        $submitted = $attemptService->submit($attempt, $student);

        $this->assertSame('submitted', $submitted->status);
        $answer = QuizAnswer::query()->where('quiz_attempt_id', $attempt->id)->firstOrFail();

        $this->actingAs($lecturer)
            ->get(route('lecturer.learning.index'))
            ->assertOk()
            ->assertSee('CBT Arsitektur Aplikasi')
            ->assertSee('Domain service menjaga aturan bisnis');

        $this->actingAs($lecturer)
            ->post(route('lecturer.learning.quiz-answers.grade', $answer), [
                'score' => 8.5,
                'feedback' => 'Argumentasi baik dan relevan.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('quiz_answers', [
            'id' => $answer->id,
            'score' => '8.50',
            'graded_by' => $lecturer->id,
            'feedback' => 'Argumentasi baik dan relevan.',
        ]);
        $this->assertDatabaseHas('quiz_attempts', [
            'id' => $attempt->id,
            'status' => 'graded',
            'score' => '8.50',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'quiz.answer_graded',
            'entity_id' => $answer->id,
        ]);
    }

    public function test_staff_without_lecturer_identity_cannot_use_quiz_authoring_routes(): void
    {
        $this->seed();
        $staff = User::query()->where('email', 'baak@kampus.test')->firstOrFail();

        $this->actingAs($staff)
            ->post(route('lecturer.learning.question-banks.store'), [
                'name' => 'Bank yang tidak sah',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('question_banks', ['name' => 'Bank yang tidak sah']);
        $this->assertSame(0, QuizAttempt::query()->count());
    }
}
