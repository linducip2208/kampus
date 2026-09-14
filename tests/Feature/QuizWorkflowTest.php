<?php

namespace Tests\Feature;

use App\Models\ClassSection;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\StudentEnrollment;
use App\Models\StudentProfile;
use App\Services\Learning\QuizAttemptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class QuizWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_registered_student_can_complete_objective_quiz_once(): void
    {
        $this->seed();
        $section = ClassSection::query()->firstOrFail();
        $enrollment = StudentEnrollment::query()->firstOrFail();
        $bank = QuestionBank::create(['university_id' => $section->offering->course->university_id, 'name' => 'Bank Algoritma']);
        $question = Question::create(['question_bank_id' => $bank->id, 'type' => 'single_choice', 'prompt' => 'Hasil 2 + 2?', 'points' => 5]);
        $correct = QuestionOption::create(['question_id' => $question->id, 'option_text' => '4', 'is_correct' => true, 'sort_order' => 1]);
        QuestionOption::create(['question_id' => $question->id, 'option_text' => '5', 'is_correct' => false, 'sort_order' => 2]);
        $quiz = Quiz::create(['class_section_id' => $section->id, 'title' => 'Quiz Dasar', 'status' => 'open', 'attempt_limit' => 1]);
        $quiz->questions()->attach($question->id, ['id' => (string) str()->ulid(), 'sort_order' => 1]);

        $service = app(QuizAttemptService::class);
        $attempt = $service->start($quiz, $enrollment);
        $service->answer($attempt, $question, $correct->id);
        $completed = $service->submit($attempt);

        $this->assertSame('graded', $completed->status);
        $this->assertSame('5.00', $completed->score);
        $this->assertDatabaseHas('quiz_answers', ['quiz_attempt_id' => $attempt->id, 'is_correct' => true]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'quiz.attempt_submitted', 'entity_id' => $attempt->id]);

        $this->expectException(ValidationException::class);
        $service->start($quiz, $enrollment);
    }

    public function test_unregistered_student_cannot_start_quiz(): void
    {
        $this->seed();
        $section = ClassSection::query()->firstOrFail();
        $bank = QuestionBank::create(['university_id' => $section->offering->course->university_id, 'name' => 'Bank Ujian']);
        $question = Question::create(['question_bank_id' => $bank->id, 'type' => 'true_false', 'prompt' => 'Pernyataan benar?', 'points' => 1]);
        QuestionOption::create(['question_id' => $question->id, 'option_text' => 'Benar', 'is_correct' => true, 'sort_order' => 1]);
        $unregisteredSection = ClassSection::query()->whereKeyNot($section->id)->firstOrFail();
        $quiz = Quiz::create(['class_section_id' => $unregisteredSection->id, 'title' => 'Quiz Terbatas', 'status' => 'published']);
        $quiz->questions()->attach($question->id, ['id' => (string) str()->ulid(), 'sort_order' => 1]);

        $profile = StudentProfile::create(['student_number' => '2026909999', 'full_name' => 'Peserta Tidak Terdaftar']);
        $registered = StudentEnrollment::query()->firstOrFail();
        $unregistered = StudentEnrollment::create([
            'student_profile_id' => $profile->id,
            'study_program_id' => $registered->study_program_id,
            'curriculum_id' => $registered->curriculum_id,
            'cohort' => 2026,
            'status' => 'active',
        ]);
        $this->expectException(ValidationException::class);
        app(QuizAttemptService::class)->start($quiz, $unregistered);
    }
}
