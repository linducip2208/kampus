<?php

namespace Tests\Feature;

use App\Models\AttendanceSession;
use App\Models\LectureMeeting;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Services\Academic\AttendanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AttendanceServiceTest extends TestCase
{
    use RefreshDatabase;

    private AttendanceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AttendanceService::class);
        $this->seed();
    }

    public function test_assigned_lecturer_can_open_qr_session_and_enrolled_student_can_attend(): void
    {
        $lecturer = User::query()->where('email', 'dosen@kampus.test')->firstOrFail();
        $meeting = LectureMeeting::query()->firstOrFail();
        $meeting->attendanceSessions()->delete();

        $opened = $this->service->open($meeting, 'qr', 15, $lecturer);
        $attendance = $this->service->recordSelf(
            $opened['session'],
            StudentEnrollment::query()->firstOrFail(),
            $opened['credential'],
            ['ip_address' => '127.0.0.1', 'device_hash' => 'device-a']
        );

        $this->assertSame('present', $attendance->status);
        $this->assertSame('qr', $attendance->method);
        $this->assertDatabaseHas('audit_logs', ['event' => 'attendance.recorded', 'entity_id' => $attendance->id]);
    }

    public function test_invalid_qr_token_is_rejected(): void
    {
        $lecturer = User::query()->where('email', 'dosen@kampus.test')->firstOrFail();
        $meeting = LectureMeeting::query()->firstOrFail();
        $meeting->attendanceSessions()->delete();
        $opened = $this->service->open($meeting, 'qr', 15, $lecturer);

        $this->assertValidationError('credential', fn () => $this->service->recordSelf(
            $opened['session'], StudentEnrollment::query()->firstOrFail(), 'wrong-token'
        ));
    }

    public function test_student_cannot_attend_class_outside_approved_krs(): void
    {
        $lecturer = User::query()->where('email', 'dosen@kampus.test')->firstOrFail();
        $meeting = LectureMeeting::query()->firstOrFail();
        $meeting->attendanceSessions()->delete();
        $opened = $this->service->open($meeting, 'pin', 15, $lecturer, '2468');
        $enrollment = StudentEnrollment::query()->firstOrFail();
        $enrollment->studyPlans()->update(['status' => 'draft']);

        $this->assertValidationError('enrollment', fn () => $this->service->recordSelf($opened['session'], $enrollment, '2468'));
    }

    public function test_duplicate_self_attendance_is_rejected(): void
    {
        $lecturer = User::query()->where('email', 'dosen@kampus.test')->firstOrFail();
        $meeting = LectureMeeting::query()->firstOrFail();
        $meeting->attendanceSessions()->delete();
        $opened = $this->service->open($meeting, 'pin', 15, $lecturer, '2468');
        $enrollment = StudentEnrollment::query()->firstOrFail();
        $this->service->recordSelf($opened['session'], $enrollment, '2468');

        $this->assertValidationError('attendance', fn () => $this->service->recordSelf($opened['session'], $enrollment, '2468'));
    }

    public function test_manual_correction_requires_reason_and_is_audited(): void
    {
        $admin = User::query()->where('email', 'admin@kampus.test')->firstOrFail();
        $session = AttendanceSession::query()->firstOrFail();
        $session->update(['status' => 'open', 'expires_at' => now()->addHour()]);
        $enrollment = StudentEnrollment::query()->firstOrFail();

        $this->assertValidationError('reason', fn () => $this->service->recordManual($session, $enrollment, 'late', $admin));
        $attendance = $this->service->recordManual($session, $enrollment, 'late', $admin, 'Koreksi keterlambatan tervalidasi.');

        $this->assertSame('late', $attendance->status);
        $this->assertSame($admin->id, $attendance->corrected_by);
        $this->assertDatabaseHas('audit_logs', ['event' => 'attendance.corrected', 'entity_id' => $attendance->id]);
    }

    private function assertValidationError(string $field, callable $callback): void
    {
        try {
            $callback();
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($field, $exception->errors());

            return;
        }

        $this->fail("Expected validation error for {$field}.");
    }
}
