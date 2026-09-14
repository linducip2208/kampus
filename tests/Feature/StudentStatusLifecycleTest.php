<?php

namespace Tests\Feature;

use App\Models\StudentEnrollment;
use App\Models\User;
use App\Services\Student\StudentStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StudentStatusLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_leave_and_reactivation_preserve_status_history_and_audit(): void
    {
        $this->seed();
        $enrollment = StudentEnrollment::query()->firstOrFail();
        $admin = User::query()->where('email', 'admin@kampus.test')->firstOrFail();
        $service = app(StudentStatusService::class);

        $service->transition($enrollment, 'leave', $admin, 'Cuti akademik.');
        $service->reactivate($enrollment->fresh(), $admin, 'Masa cuti selesai.');

        $this->assertSame('active', $enrollment->fresh()->status);
        $this->assertDatabaseHas('student_status_histories', [
            'student_enrollment_id' => $enrollment->id,
            'from_status' => 'active',
            'to_status' => 'leave',
        ]);
        $this->assertDatabaseHas('student_status_histories', [
            'student_enrollment_id' => $enrollment->id,
            'from_status' => 'leave',
            'to_status' => 'active',
        ]);
        $this->assertDatabaseCount('audit_logs', 2);
    }

    public function test_terminal_status_cannot_be_reactivated(): void
    {
        $this->seed();
        $enrollment = StudentEnrollment::query()->firstOrFail();
        $admin = User::query()->where('email', 'admin@kampus.test')->firstOrFail();
        $service = app(StudentStatusService::class);

        $service->transition($enrollment, 'graduated', $admin, 'Lulus yudisium.');

        $this->expectException(ValidationException::class);
        $service->reactivate($enrollment->fresh(), $admin);
    }
}
