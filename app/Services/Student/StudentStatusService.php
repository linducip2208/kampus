<?php

namespace App\Services\Student;

use App\Models\AuditLog;
use App\Models\StudentEnrollment;
use App\Models\StudentStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentStatusService
{
    public const STATUSES = [
        'active',
        'leave',
        'inactive',
        'suspended',
        'dropout',
        'resigned',
        'transferred',
        'graduated',
        'deceased',
    ];

    private const TRANSITIONS = [
        'active' => ['leave', 'inactive', 'suspended', 'dropout', 'resigned', 'transferred', 'graduated', 'deceased'],
        'leave' => ['active', 'inactive', 'resigned', 'transferred', 'dropout'],
        'inactive' => ['active', 'resigned', 'transferred', 'dropout'],
        'suspended' => ['active', 'dropout', 'resigned'],
    ];

    public function transition(StudentEnrollment $enrollment, string $toStatus, ?User $actor = null, ?string $reason = null): StudentEnrollment
    {
        return DB::transaction(function () use ($enrollment, $toStatus, $actor, $reason) {
            if (! in_array($toStatus, self::STATUSES, true)) {
                throw ValidationException::withMessages(['status' => 'Status mahasiswa tidak dikenal.']);
            }

            $locked = StudentEnrollment::query()->lockForUpdate()->findOrFail($enrollment->id);
            $fromStatus = $locked->status;

            if ($fromStatus === $toStatus) {
                throw ValidationException::withMessages(['status' => 'Mahasiswa sudah memiliki status tersebut.']);
            }

            if (! in_array($toStatus, self::TRANSITIONS[$fromStatus] ?? [], true)) {
                throw ValidationException::withMessages([
                    'status' => "Perubahan status dari {$fromStatus} ke {$toStatus} tidak diizinkan.",
                ]);
            }

            $locked->forceFill(['status' => $toStatus])->save();
            StudentStatusHistory::create([
                'student_enrollment_id' => $locked->id,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'reason' => $reason,
                'changed_by' => $actor?->id,
                'changed_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => $actor?->id,
                'event' => 'student.status_changed',
                'module' => 'student',
                'entity_type' => StudentEnrollment::class,
                'entity_id' => $locked->id,
                'old_values' => ['status' => $fromStatus],
                'new_values' => ['status' => $toStatus, 'reason' => $reason],
                'ip_address' => app()->runningInConsole() ? null : request()->ip(),
                'user_agent' => app()->runningInConsole() ? null : request()->userAgent(),
            ]);

            return $locked->fresh();
        });
    }

    public function reactivate(StudentEnrollment $enrollment, ?User $actor = null, ?string $reason = null): StudentEnrollment
    {
        return $this->transition($enrollment, 'active', $actor, $reason ?? 'Mahasiswa diaktifkan kembali.');
    }
}
