<?php

namespace App\Services\Student;

use App\Models\AuditLog;
use App\Models\StudentEnrollment;
use App\Models\StudentTransfer;
use App\Models\User;
use App\Services\Approval\ApprovalEngine;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentTransferService
{
    public function __construct(
        private readonly ApprovalEngine $approval,
        private readonly StudentStatusService $status,
    ) {}

    public function propose(StudentEnrollment $enrollment, string $toProgramId, string $reason, User $requester): StudentTransfer
    {
        return DB::transaction(function () use ($enrollment, $toProgramId, $reason, $requester) {
            $locked = StudentEnrollment::query()->with('studyProgram.department.faculty')->lockForUpdate()->findOrFail($enrollment->id);
            if ($locked->status !== 'active') {
                throw ValidationException::withMessages(['enrollment' => 'Hanya mahasiswa aktif yang dapat mutasi.']);
            }
            if ($locked->study_program_id === $toProgramId) {
                throw ValidationException::withMessages(['program' => 'Program tujuan sama dengan program saat ini.']);
            }
            if (StudentTransfer::query()->where('student_enrollment_id', $locked->id)->where('status', 'proposed')->exists()) {
                throw ValidationException::withMessages(['transfer' => 'Pengajuan mutasi aktif sudah tersedia.']);
            }

            $transfer = StudentTransfer::query()->create([
                'student_enrollment_id' => $locked->id,
                'from_study_program_id' => $locked->study_program_id,
                'to_study_program_id' => $toProgramId,
                'reason' => trim($reason),
                'status' => 'proposed',
                'requested_by' => $requester->id,
            ]);
            $this->approval->request($transfer, $locked->studyProgram->department->faculty->university_id, 'student_transfer', $requester);
            $this->audit($requester, $transfer, 'student_transfer.proposed', ['status' => 'proposed']);

            return $transfer->fresh();
        });
    }

    public function approve(StudentTransfer $transfer, User $actor): StudentTransfer
    {
        return DB::transaction(function () use ($transfer, $actor) {
            $locked = StudentTransfer::query()->lockForUpdate()->findOrFail($transfer->id);
            if ($locked->status !== 'proposed') {
                throw ValidationException::withMessages(['transfer' => 'Pengajuan mutasi sudah diproses.']);
            }
            $approval = $locked->approvalRequests()->where('status', 'pending')->latest()->firstOrFail();
            $result = $this->approval->act($approval, $actor, 'approved');
            if ($result->status !== 'approved') {
                return $locked->fresh();
            }

            $old = StudentEnrollment::query()->lockForUpdate()->findOrFail($locked->student_enrollment_id);
            $new = StudentEnrollment::query()->create([
                'student_profile_id' => $old->student_profile_id,
                'study_program_id' => $locked->to_study_program_id,
                'curriculum_id' => $old->curriculum_id,
                'advisor_id' => $old->advisor_id,
                'cohort' => $old->cohort,
                'status' => 'active',
                'enrolled_on' => now()->toDateString(),
                'admission_type' => 'transfer',
            ]);
            $this->status->transition($old, 'transferred', $actor, 'Mutasi ke program lain.');
            $locked->forceFill(['status' => 'approved', 'decided_by' => $actor->id, 'decided_at' => now(), 'new_enrollment_id' => $new->id])->save();
            $this->audit($actor, $locked, 'student_transfer.approved', ['new_enrollment_id' => $new->id]);

            return $locked->fresh();
        });
    }

    public function reject(StudentTransfer $transfer, User $actor, string $reason): StudentTransfer
    {
        return DB::transaction(function () use ($transfer, $actor, $reason) {
            $locked = StudentTransfer::query()->lockForUpdate()->findOrFail($transfer->id);
            if ($locked->status !== 'proposed') {
                throw ValidationException::withMessages(['transfer' => 'Pengajuan mutasi sudah diproses.']);
            }
            $approval = $locked->approvalRequests()->where('status', 'pending')->latest()->firstOrFail();
            $this->approval->act($approval, $actor, 'rejected', $reason);
            $locked->forceFill(['status' => 'rejected', 'decided_by' => $actor->id, 'decided_at' => now(), 'reject_reason' => trim($reason)])->save();
            $this->audit($actor, $locked, 'student_transfer.rejected', ['status' => 'rejected']);

            return $locked->fresh();
        });
    }

    private function audit(User $actor, StudentTransfer $transfer, string $event, array $values): void
    {
        AuditLog::query()->create([
            'user_id' => $actor->id, 'event' => $event, 'module' => 'student',
            'entity_type' => StudentTransfer::class, 'entity_id' => $transfer->id, 'new_values' => $values,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
            'user_agent' => app()->runningInConsole() ? null : request()->userAgent(),
        ]);
    }
}
