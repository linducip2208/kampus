<?php

namespace Tests\Feature;

use App\Models\AlumniProfile;
use App\Models\ApprovalStep;
use App\Models\ApprovalWorkflow;
use App\Models\LecturerProfile;
use App\Models\Semester;
use App\Models\StudentEnrollment;
use App\Models\StudentInvoice;
use App\Models\University;
use App\Models\User;
use App\Services\Academic\GraduationService;
use App\Services\Academic\ScholarshipService;
use App\Services\Academic\ThesisService;
use App\Services\Alumni\AlumniCareerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AcademicCompletionTest extends TestCase
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

    public function test_scholarship_period_and_disbursement(): void
    {
        $this->seed();
        $this->makeWorkflow('scholarship');
        $university = University::query()->firstOrFail();
        $semester = Semester::query()->firstOrFail();
        $enrollment = StudentEnrollment::query()->firstOrFail();
        $student = User::query()->where('email', 'mahasiswa@kampus.test')->firstOrFail();
        $admin = User::query()->where('email', 'admin@kampus.test')->firstOrFail();
        $invoice = StudentInvoice::query()->firstOrFail();

        $scholarship = app(ScholarshipService::class)->createScholarship($university->id, [
            'name' => 'Beasiswa Penuh', 'code' => 'FULL-2026', 'amount' => '1000000.00', 'quota' => 5,
        ]);
        $period = app(ScholarshipService::class)->openPeriod($scholarship, $semester->id, '2026-08-01', '2026-09-30', 5);
        $this->assertSame('open', $period->status);

        $award = app(ScholarshipService::class)->propose($scholarship, $enrollment, $student);
        $approved = app(ScholarshipService::class)->approve($award, $admin);
        $discount = app(ScholarshipService::class)->disburse($approved, $invoice, $admin);
        $this->assertSame('1000000.00', $discount->amount);
        $this->assertSame('6500000.00', $invoice->fresh()->total_amount);
    }

    public function test_thesis_guidance_examiner_revision(): void
    {
        $this->seed();
        $this->makeWorkflow('thesis');
        $enrollment = StudentEnrollment::query()->firstOrFail();
        $student = User::query()->where('email', 'mahasiswa@kampus.test')->firstOrFail();
        $admin = User::query()->where('email', 'admin@kampus.test')->firstOrFail();
        $lecturer = LecturerProfile::query()->firstOrFail();

        $proposal = app(ThesisService::class)->submit($enrollment, 'Judul Bimbingan Terstruktur', 'Abstrak.', $student);
        $guidance = app(ThesisService::class)->recordGuidance($proposal, 'Bab 1 selesai.', 'Lanjut bab 2.', $admin);
        $this->assertNotNull($guidance->guided_at);

        $approved = app(ThesisService::class)->approve($proposal, $admin);
        $defense = app(ThesisService::class)->scheduleDefense($approved, now()->addWeek()->toDateTimeString(), 'Ruang 1', $admin);
        $examiner = app(ThesisService::class)->addExaminer($defense, $lecturer->id, 'chair', $admin);
        $graded = app(ThesisService::class)->scoreExaminer($examiner, 90, 'Baik.', $admin);
        $this->assertSame('graded', $graded->status);
        $this->assertSame('90.00', $graded->score);

        $revision = app(ThesisService::class)->addRevision($defense, 'Perbaiki latar belakang.', $admin);
        $checked = app(ThesisService::class)->checkRevision($revision, $admin);
        $this->assertTrue($checked->is_done);
    }

    public function test_graduation_requires_clearances_and_period(): void
    {
        $this->seed();
        $this->makeWorkflow('graduation');
        $university = University::query()->firstOrFail();
        $enrollment = StudentEnrollment::query()->firstOrFail();
        $semester = Semester::query()->firstOrFail();
        $admin = User::query()->where('email', 'admin@kampus.test')->firstOrFail();

        $period = app(GraduationService::class)->openPeriod($university->id, $semester->id, 'Wisuda 2026', '2026-09-01', '2026-10-31', '2026-11-20');
        $this->assertSame('open', $period->status);

        $graduation = app(GraduationService::class)->propose($enrollment, $semester->id, 3.2, 150, $admin);
        try {
            app(GraduationService::class)->approve($graduation, $admin);
            $this->fail('Yudisium tanpa clearance seharusnya ditolak.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('clearance', $e->errors());
        }

        foreach (['academic', 'finance', 'library', 'thesis'] as $kind) {
            app(GraduationService::class)->checkClearance($graduation, $kind, true, $admin);
        }
        $approved = app(GraduationService::class)->approve($graduation, $admin);
        $this->assertSame('approved', $approved->status);
    }

    public function test_tracer_builder_career_analytics(): void
    {
        $this->seed();
        $this->makeWorkflow('graduation');
        $university = University::query()->firstOrFail();
        $enrollment = StudentEnrollment::query()->firstOrFail();
        $semester = Semester::query()->firstOrFail();
        $admin = User::query()->where('email', 'admin@kampus.test')->firstOrFail();

        $graduation = app(GraduationService::class)->propose($enrollment, $semester->id, 3.4, 146, $admin);
        foreach (['academic', 'finance', 'library', 'thesis'] as $kind) {
            app(GraduationService::class)->checkClearance($graduation, $kind, true, $admin);
        }
        app(GraduationService::class)->approve($graduation, $admin);
        $alumni = AlumniProfile::query()->where('student_enrollment_id', $enrollment->id)->firstOrFail();
        $survey = app(GraduationService::class)->fillTracer($alumni, [
            'graduation_year' => 2026, 'employment_status' => 'employed', 'company' => 'PT X',
        ], $admin);

        $section = app(AlumniCareerService::class)->createSection($university->id, 'Pekerjaan');
        $question = app(AlumniCareerService::class)->addQuestion($section, 'Bidang pekerjaan?', 'choice', true, ['IT', 'Keuangan']);
        $this->assertCount(2, $question->options);

        app(AlumniCareerService::class)->answerSurvey($survey, [$question->id => 'IT'], $admin);
        $this->assertDatabaseHas('tracer_responses', ['tracer_survey_id' => $survey->id, 'answer' => 'IT']);

        $analytics = app(AlumniCareerService::class)->analytics($university->id, 2026);
        $this->assertSame(1, $analytics['responses']);
        $this->assertSame(100.0, $analytics['employment_rate']);

        $company = app(AlumniCareerService::class)->createCompany($university->id, ['name' => 'PT Digital', 'industry' => 'IT', 'is_partner' => true]);
        $vacancy = app(AlumniCareerService::class)->postVacancy($company, ['title' => 'Backend Dev', 'closes_on' => now()->addMonth()->toDateString()]);
        $application = app(AlumniCareerService::class)->apply($vacancy, $alumni);
        $decided = app(AlumniCareerService::class)->decideApplication($application, 'accepted', $admin);
        $this->assertSame('accepted', $decided->status);
    }
}
