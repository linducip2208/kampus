<?php

namespace Tests\Feature;

use App\Models\ApprovalStep;
use App\Models\ApprovalWorkflow;
use App\Models\Semester;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Services\Student\StudentLeaveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentLeaveApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_leave_uses_generic_approval_and_updates_status_history(): void
    {
        $this->seed();
        $university = \App\Models\University::query()->firstOrFail();
        $workflow = ApprovalWorkflow::create(['university_id' => $university->id, 'name' => 'Persetujuan cuti mahasiswa', 'module' => 'student_leave']);
        ApprovalStep::create(['approval_workflow_id' => $workflow->id, 'step_order' => 1, 'label' => 'Persetujuan administrator', 'role_name' => 'super_admin']);
        $student = User::query()->where('email', 'mahasiswa@kampus.test')->firstOrFail();
        $admin = User::query()->where('email', 'admin@kampus.test')->firstOrFail();
        $enrollment = StudentEnrollment::query()->firstOrFail();
        $semester = Semester::query()->firstOrFail();
        $leave = app(StudentLeaveService::class)->submit($enrollment, $semester->id, 'Mengikuti program pemulihan kesehatan.', $student);
        $this->assertSame('submitted', $leave->status);
        $approved = app(StudentLeaveService::class)->approve($leave, $admin);
        $this->assertSame('approved', $approved->status);
        $this->assertSame('leave', $enrollment->fresh()->status);
        $this->assertDatabaseHas('student_status_histories', ['student_enrollment_id' => $enrollment->id, 'to_status' => 'leave']);
    }
}
