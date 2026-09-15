<?php

namespace Tests\Feature;

use App\Models\AlumniProfile;
use App\Models\ApprovalStep;
use App\Models\ApprovalWorkflow;
use App\Models\Semester;
use App\Models\StudentEnrollment;
use App\Models\University;
use App\Models\User;
use App\Services\Academic\GraduationService;
use App\Services\Academic\ScholarshipService;
use App\Services\Academic\ThesisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicLifecycleTest extends TestCase
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

    public function test_scholarship_propose_approve_reject_with_quota(): void
    {
        $this->seed();
        $this->makeWorkflow('scholarship');
        $university = University::query()->firstOrFail();
        $enrollment = StudentEnrollment::query()->firstOrFail();
        $student = User::query()->where('email', 'mahasiswa@kampus.test')->firstOrFail();
        $admin = User::query()->where('email', 'admin@kampus.test')->firstOrFail();

        $scholarship = app(ScholarshipService::class)->createScholarship($university->id, [
            'name' => 'Beasiswa Prestasi', 'code' => 'PRESTASI-2026', 'amount' => '2500000.00', 'quota' => 5,
        ]);

        $award = app(ScholarshipService::class)->propose($scholarship, $enrollment, $student);
        $this->assertSame('proposed', $award->status);

        $approved = app(ScholarshipService::class)->approve($award, $admin);
        $this->assertSame('approved', $approved->status);
        $this->assertDatabaseHas('audit_logs', ['event' => 'scholarship.approved']);
    }

    public function test_thesis_submit_approve_schedule_grade(): void
    {
        $this->seed();
        $this->makeWorkflow('thesis');
        $enrollment = StudentEnrollment::query()->firstOrFail();
        $student = User::query()->where('email', 'mahasiswa@kampus.test')->firstOrFail();
        $admin = User::query()->where('email', 'admin@kampus.test')->firstOrFail();

        $proposal = app(ThesisService::class)->submit($enrollment, 'Sistem Prediksi Kelulusan Berbasis ML', 'Abstrak penelitian.', $student);
        $this->assertSame('submitted', $proposal->status);

        $approved = app(ThesisService::class)->approve($proposal, $admin);
        $this->assertSame('approved', $approved->status);

        $defense = app(ThesisService::class)->scheduleDefense($approved, now()->addWeek()->toDateTimeString(), 'Ruang Sidang 1', $admin);
        $this->assertSame('scheduled', $defense->status);

        $graded = app(ThesisService::class)->gradeDefense($defense, 88.5, $admin);
        $this->assertSame('graded', $graded->status);
        $this->assertSame('A', $graded->grade);
    }

    public function test_graduation_creates_alumni_and_tracer(): void
    {
        $this->seed();
        $this->makeWorkflow('graduation');
        $enrollment = StudentEnrollment::query()->firstOrFail();
        $semester = Semester::query()->firstOrFail();
        $admin = User::query()->where('email', 'admin@kampus.test')->firstOrFail();

        $graduation = app(GraduationService::class)->propose($enrollment, $semester->id, 3.65, 148, $admin);
        $this->assertSame('Dengan Pujian', $graduation->predicate);

        foreach (['academic', 'finance', 'library', 'thesis'] as $kind) {
            app(GraduationService::class)->checkClearance($graduation, $kind, true, $admin, 'Lolos.');
        }
        $approved = app(GraduationService::class)->approve($graduation, $admin);
        $this->assertSame('approved', $approved->status);
        $this->assertNotNull($approved->certificate_number);
        $this->assertSame('graduated', $enrollment->fresh()->status);

        $alumni = AlumniProfile::query()->where('student_enrollment_id', $enrollment->id)->firstOrFail();
        $survey = app(GraduationService::class)->fillTracer($alumni, [
            'graduation_year' => 2026,
            'employment_status' => 'employed',
            'company' => 'PT Nusantara Digital',
            'position' => 'Software Engineer',
            'salary_range' => '8-12jt',
            'relevance' => 'sangat_relevan',
            'satisfaction' => 5,
        ], $admin);

        $this->assertSame('employed', $survey->employment_status);
        $this->assertSame('employed', $alumni->fresh()->employment_status);
        $this->assertDatabaseHas('audit_logs', ['event' => 'alumni.tracer_filled']);
    }
}
