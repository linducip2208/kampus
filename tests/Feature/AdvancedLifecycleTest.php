<?php

namespace Tests\Feature;

use App\Models\ApprovalStep;
use App\Models\ApprovalWorkflow;
use App\Models\ClassSection;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\Quiz;
use App\Models\StudentEnrollment;
use App\Models\StudentInvoice;
use App\Models\StudyProgram;
use App\Models\University;
use App\Models\User;
use App\Services\Approval\ApprovalBuilderService;
use App\Services\Documents\PrivateFileService;
use App\Services\Finance\InstallmentService;
use App\Services\Learning\QuizLifecycleService;
use App\Services\Reporting\ReportingService;
use App\Services\Student\StudentTransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AdvancedLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function makeWorkflow(string $module): void
    {
        $university = University::query()->firstOrFail();
        $workflow = ApprovalWorkflow::query()->firstOrCreate(
            ['university_id' => $university->id, 'module' => $module],
            ['name' => "Workflow {$module}", 'is_active' => true],
        );
        ApprovalStep::query()->firstOrCreate(
            ['approval_workflow_id' => $workflow->id, 'step_order' => 1],
            ['label' => 'Persetujuan admin', 'role_name' => 'super_admin'],
        );
    }

    public function test_quiz_publish_close_lifecycle(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@kampus.test')->firstOrFail();
        $lecturer = User::query()->where('email', 'dosen@kampus.test')->firstOrFail();
        $section = ClassSection::query()->firstOrFail();
        $quiz = Quiz::query()->create(['class_section_id' => $section->id, 'title' => 'Quiz Lifecycle', 'status' => 'draft', 'attempt_limit' => 1]);

        try {
            app(QuizLifecycleService::class)->publish($quiz, $lecturer);
            $this->fail('Publish tanpa soal seharusnya gagal.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('quiz', $e->errors());
        }

        $bank = QuestionBank::query()->create(['university_id' => $lecturer->employee->university_id, 'name' => 'Bank', 'is_active' => true]);
        $question = Question::query()->create(['question_bank_id' => $bank->id, 'type' => 'essay', 'prompt' => 'Jelaskan.', 'points' => 10, 'is_active' => true]);
        $quiz->questions()->attach($question->id, ['id' => (string) Str::ulid(), 'sort_order' => 1, 'points' => 10]);

        $published = app(QuizLifecycleService::class)->publish($quiz->fresh(), $lecturer);
        $this->assertSame('published', $published->status);

        $closed = app(QuizLifecycleService::class)->close($published, $lecturer);
        $this->assertSame('closed', $closed->status);
        $this->assertDatabaseHas('audit_logs', ['event' => 'quiz.lifecycle_changed']);
        $this->assertTrue($admin->hasRole('super_admin'));
    }

    public function test_transfer_creates_new_enrollment_and_marks_old(): void
    {
        $this->seed();
        $this->makeWorkflow('student_transfer');
        $enrollment = StudentEnrollment::query()->firstOrFail();
        $target = StudyProgram::query()->where('id', '!=', $enrollment->study_program_id)->first();
        if (! $target) {
            $department = $enrollment->studyProgram->department;
            $target = StudyProgram::query()->create(['department_id' => $department->id, 'name' => 'S1 Sistem Informasi', 'code' => 'SI', 'level' => 'S1']);
        }
        $admin = User::query()->where('email', 'admin@kampus.test')->firstOrFail();

        $transfer = app(StudentTransferService::class)->propose($enrollment, $target->id, 'Pindah minat.', $admin);
        $approved = app(StudentTransferService::class)->approve($transfer, $admin);

        $this->assertSame('approved', $approved->status);
        $this->assertSame('transferred', $enrollment->fresh()->status);
        $this->assertNotNull($approved->new_enrollment_id);
    }

    public function test_private_file_access_and_installments_and_builder_and_reporting(): void
    {
        $this->seed();
        $owner = User::query()->where('email', 'mahasiswa@kampus.test')->firstOrFail();
        $admin = User::query()->where('email', 'admin@kampus.test')->firstOrFail();
        $university = University::query()->firstOrFail();
        $enrollment = StudentEnrollment::query()->firstOrFail();
        $invoice = StudentInvoice::query()->firstOrFail();

        $file = app(PrivateFileService::class)->store($owner, 'Ijazah SMA', 'private/ijazah.pdf', 'application/pdf', 1234, $enrollment);
        $this->assertSame('private', $file->visibility);
        $this->assertSame($file->id, app(PrivateFileService::class)->authorizeAccess($file, $owner)->id);
        // super_admin bypasses ownership check.
        $this->assertSame($file->id, app(PrivateFileService::class)->authorizeAccess($file, $admin)->id);

        $foreign = User::factory()->create(['email' => 'asing2@foreign.test']);
        try {
            app(PrivateFileService::class)->authorizeAccess($file, $foreign);
            $this->fail('Akses file privat oleh user asing seharusnya ditolak.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('authorization', $e->errors());
        }

        $plans = app(InstallmentService::class)->createPlan($invoice, 3, now()->toDateString(), $admin);
        $this->assertCount(3, $plans);
        $paid = app(InstallmentService::class)->markPaid($plans[0], $admin);
        $this->assertSame('paid', $paid->status);

        $workflow = app(ApprovalBuilderService::class)->createWorkflow($university->id, 'test_module_'.uniqid(), 'Workflow Test', ['baak'], $admin);
        $this->assertCount(1, $workflow->steps);

        $summary = app(ReportingService::class)->executiveSummary($admin);
        $this->assertArrayHasKey('enrollments_total', $summary);
        $csv = app(ReportingService::class)->exportEnrollmentsCsv($admin);
        $this->assertStringContainsString('student_number', $csv);

        $section = ClassSection::query()->firstOrFail();
        $aggregate = app(ReportingService::class)->attendanceAggregate($section->id);
        $this->assertArrayHasKey('present_rate', $aggregate);
    }
}
