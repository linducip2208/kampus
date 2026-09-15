<?php

namespace App\Services\Academic;

use App\Models\AttendanceSession;
use App\Models\AuditLog;
use App\Models\LectureMeeting;
use App\Models\StudentAttendance;
use App\Models\StudentEnrollment;
use App\Models\StudyPlanItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public const METHODS = ['manual', 'qr', 'pin'];

    public const STATUSES = ['present', 'late', 'sick', 'permission', 'absent'];

    /** @return array{session:AttendanceSession, credential:string|null} */
    public function open(LectureMeeting $meeting, string $method, int $durationMinutes, User $actor, ?string $pin = null): array
    {
        return DB::transaction(function () use ($meeting, $method, $durationMinutes, $actor, $pin) {
            $meeting = LectureMeeting::query()->with('classSection.lecturers.employee')->lockForUpdate()->findOrFail($meeting->id);
            $this->authorizeLecturer($meeting, $actor);
            $this->validateMethod($method, $pin);

            if ($durationMinutes < 1 || $durationMinutes > 240) {
                throw ValidationException::withMessages(['duration' => 'Durasi sesi harus antara 1 dan 240 menit.']);
            }

            $active = $meeting->attendanceSessions()->where('status', 'open')->lockForUpdate()->get();
            foreach ($active as $session) {
                if ($session->expires_at?->isPast()) {
                    $session->forceFill(['status' => 'expired', 'closed_at' => now()])->save();
                } else {
                    throw ValidationException::withMessages(['session' => 'Pertemuan ini masih memiliki sesi presensi aktif.']);
                }
            }

            $credential = $method === 'qr' ? Str::random(48) : ($method === 'pin' ? $pin : null);
            $session = $meeting->attendanceSessions()->create([
                'method' => $method,
                'status' => 'open',
                'token_hash' => $method === 'qr' ? hash('sha256', $credential) : null,
                'pin_hash' => $method === 'pin' ? hash('sha256', (string) $credential) : null,
                'expires_at' => now()->addMinutes($durationMinutes),
                'opened_by' => $actor->id,
                'opened_at' => now(),
                'token_rotated_at' => $method === 'qr' ? now() : null,
            ]);

            $this->audit('attendance.session_opened', $session, $actor, null, [
                'method' => $method,
                'expires_at' => $session->expires_at?->toIso8601String(),
            ]);

            return ['session' => $session->fresh(), 'credential' => $credential];
        });
    }

    public function close(AttendanceSession $session, User $actor): AttendanceSession
    {
        return DB::transaction(function () use ($session, $actor) {
            $session = AttendanceSession::query()->with('lectureMeeting.classSection.lecturers.employee')->lockForUpdate()->findOrFail($session->id);
            $this->authorizeLecturer($session->lectureMeeting, $actor);

            if ($session->status !== 'open') {
                throw ValidationException::withMessages(['session' => 'Sesi presensi sudah ditutup.']);
            }

            $session->forceFill(['status' => 'closed', 'closed_at' => now()])->save();
            $this->audit('attendance.session_closed', $session, $actor, ['status' => 'open'], ['status' => 'closed']);

            return $session->fresh();
        });
    }

    /** @param array{ip_address?:string|null,device_hash?:string|null,latitude?:numeric|null,longitude?:numeric|null} $metadata */
    public function recordSelf(AttendanceSession $session, StudentEnrollment $enrollment, string $credential, array $metadata = []): StudentAttendance
    {
        return DB::transaction(function () use ($session, $enrollment, $credential, $metadata) {
            $session = AttendanceSession::query()->with('lectureMeeting.classSection')->lockForUpdate()->findOrFail($session->id);
            $enrollment = StudentEnrollment::query()->lockForUpdate()->findOrFail($enrollment->id);
            $this->assertSessionOpen($session);
            $this->assertEnrolled($session, $enrollment);

            if ($session->method === 'manual') {
                throw ValidationException::withMessages(['method' => 'Sesi manual hanya dapat diisi oleh dosen.']);
            }
            $expected = $session->method === 'qr' ? $session->token_hash : $session->pin_hash;
            if (! $expected || ! hash_equals($expected, hash('sha256', $credential))) {
                throw ValidationException::withMessages(['credential' => 'Token atau PIN presensi tidak valid.']);
            }
            if ($session->attendances()->where('student_enrollment_id', $enrollment->id)->lockForUpdate()->exists()) {
                throw ValidationException::withMessages(['attendance' => 'Presensi untuk sesi ini sudah tercatat.']);
            }

            $attendance = $session->attendances()->create([
                'student_enrollment_id' => $enrollment->id,
                'status' => 'present',
                'method' => $session->method,
                'recorded_at' => now(),
                'ip_address' => $metadata['ip_address'] ?? null,
                'device_hash' => $metadata['device_hash'] ?? null,
                'latitude' => $metadata['latitude'] ?? null,
                'longitude' => $metadata['longitude'] ?? null,
            ]);
            $this->audit('attendance.recorded', $attendance, $enrollment->studentProfile?->user, null, ['status' => 'present', 'method' => $session->method]);

            return $attendance->fresh();
        });
    }

    public function recordManual(AttendanceSession $session, StudentEnrollment $enrollment, string $status, User $actor, ?string $reason = null): StudentAttendance
    {
        return DB::transaction(function () use ($session, $enrollment, $status, $actor, $reason) {
            $session = AttendanceSession::query()->with('lectureMeeting.classSection.lecturers.employee')->lockForUpdate()->findOrFail($session->id);
            $enrollment = StudentEnrollment::query()->lockForUpdate()->findOrFail($enrollment->id);
            $this->authorizeLecturer($session->lectureMeeting, $actor);
            $this->assertSessionOpen($session);
            $this->assertEnrolled($session, $enrollment);
            if (! in_array($status, self::STATUSES, true)) {
                throw ValidationException::withMessages(['status' => 'Status presensi tidak valid.']);
            }

            $attendance = $session->attendances()->where('student_enrollment_id', $enrollment->id)->lockForUpdate()->first();
            $oldValues = $attendance?->only(['status', 'method', 'recorded_at']);
            if ($attendance && blank($reason)) {
                throw ValidationException::withMessages(['reason' => 'Alasan wajib diisi untuk koreksi presensi.']);
            }

            $attendance ??= new StudentAttendance([
                'attendance_session_id' => $session->id,
                'student_enrollment_id' => $enrollment->id,
            ]);
            $attendance->forceFill([
                'status' => $status,
                'method' => 'manual',
                'recorded_at' => now(),
                'corrected_by' => $oldValues ? $actor->id : null,
                'correction_reason' => $oldValues ? $reason : null,
            ])->save();

            $this->audit($oldValues ? 'attendance.corrected' : 'attendance.recorded', $attendance, $actor, $oldValues, [
                'status' => $status,
                'method' => 'manual',
                'reason' => $reason,
            ]);

            return $attendance->fresh();
        });
    }

    private function assertSessionOpen(AttendanceSession $session): void
    {
        if ($session->status !== 'open' || ! $session->expires_at || $session->expires_at->isPast()) {
            if ($session->status === 'open') {
                $session->forceFill(['status' => 'expired', 'closed_at' => now()])->save();
            }
            throw ValidationException::withMessages(['session' => 'Sesi presensi tidak aktif atau sudah kedaluwarsa.']);
        }
    }

    private function assertEnrolled(AttendanceSession $session, StudentEnrollment $enrollment): void
    {
        $eligible = StudyPlanItem::query()
            ->where('class_section_id', $session->lectureMeeting->class_section_id)
            ->whereHas('studyPlan', fn ($query) => $query
                ->where('student_enrollment_id', $enrollment->id)
                ->whereIn('status', ['approved', 'finalized', 'locked']))
            ->exists();

        if (! $eligible) {
            throw ValidationException::withMessages(['enrollment' => 'Mahasiswa tidak terdaftar pada kelas ini melalui KRS yang disetujui.']);
        }
    }

    private function authorizeLecturer(LectureMeeting $meeting, User $actor): void
    {
        if ($actor->hasRole('super_admin')) {
            return;
        }
        $lecturerId = $actor->employee?->lecturerProfile?->id;
        if (! $lecturerId || ! $meeting->classSection->lecturers->contains('id', $lecturerId)) {
            throw ValidationException::withMessages(['authorization' => 'Hanya dosen pengampu kelas yang dapat mengelola sesi presensi.']);
        }
    }

    private function validateMethod(string $method, ?string $pin): void
    {
        if (! in_array($method, self::METHODS, true)) {
            throw ValidationException::withMessages(['method' => 'Metode presensi tidak valid.']);
        }
        if ($method === 'pin' && (! preg_match('/^\d{4,8}$/', (string) $pin))) {
            throw ValidationException::withMessages(['pin' => 'PIN harus terdiri dari 4 sampai 8 digit.']);
        }
    }

    private function audit(string $event, object $entity, ?User $actor, ?array $oldValues, array $newValues): void
    {
        AuditLog::create([
            'user_id' => $actor?->id,
            'event' => $event,
            'module' => 'attendance',
            'entity_type' => $entity::class,
            'entity_id' => $entity->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
            'user_agent' => app()->runningInConsole() ? null : request()->userAgent(),
        ]);
    }
}
