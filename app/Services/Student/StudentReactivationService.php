<?php

namespace App\Services\Student;

use App\Models\AuditLog;
use App\Models\StudentEnrollment;
use App\Models\StudentReactivationRequest;
use App\Models\User;
use App\Services\Approval\ApprovalEngine;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentReactivationService
{
    public function __construct(
        private readonly ApprovalEngine $approval,
        private readonly StudentStatusService $status,
    ) {}

    public function submit(StudentEnrollment $enrollment, string $semesterId, string $reason, User $requester): StudentReactivationRequest
    {
        return DB::transaction(function () use ($enrollment, $semesterId, $reason, $requester) {
            $locked = StudentEnrollment::query()->with(['studentProfile', 'studyProgram.department.faculty'])->lockForUpdate()->findOrFail($enrollment->id);
            if ($locked->studentProfile->user_id !== $requester->id) {
                throw ValidationException::withMessages(['authorization' => 'Pengajuan aktif kembali hanya dapat dibuat untuk enrollment sendiri.']);
            }
            if (! in_array($locked->status, ['leave', 'inactive'], true)) {
                throw ValidationException::withMessages(['enrollment' => 'Aktif kembali hanya tersedia bagi mahasiswa cuti atau nonaktif.']);
            }
            if (StudentReactivationRequest::query()->where('student_enrollment_id', $locked->id)->where('semester_id', $semesterId)->exists()) {
                throw ValidationException::withMessages(['semester_id' => 'Pengajuan aktif kembali untuk semester ini sudah tersedia.']);
            }

            $request = StudentReactivationRequest::query()->create([
                'student_enrollment_id' => $locked->id,
                'semester_id' => $semesterId,
                'requested_by' => $requester->id,
                'status' => 'submitted',
                'reason' => trim($reason),
                'submitted_at' => now(),
            ]);
            $this->approval->request($request, $locked->studyProgram->department->faculty->university_id, 'student_reactivation', $requester);
            $this->audit($requester, $request, 'student_reactivation.submitted', ['status' => 'submitted']);

            return $request->fresh();
        });
    }

    public function approve(StudentReactivationRequest $reactivation, User $actor): StudentReactivationRequest
    {
        return DB::transaction(function () use ($reactivation, $actor) {
            $request = StudentReactivationRequest::query()->lockForUpdate()->findOrFail($reactivation->id);
            if ($request->status !== 'submitted') {
                throw ValidationException::withMessages(['reactivation' => 'Pengajuan aktif kembali sudah diproses.']);
            }

            $approval = $request->approvalRequests()->where('status', 'pending')->latest()->firstOrFail();
            $result = $this->approval->act($approval, $actor, 'approved');
            if ($result->status === 'approved') {
                $enrollment = StudentEnrollment::query()->lockForUpdate()->findOrFail($request->student_enrollment_id);
                $this->status->reactivate($enrollment, $actor, $request->reason);
                $request->forceFill([
                    'status' => 'approved',
                    'approved_at' => now(),
                    'processed_by' => $actor->id,
                ])->save();
                $this->audit($actor, $request, 'student_reactivation.approved', ['status' => 'approved']);
            }

            return $request->fresh();
        });
    }

    public function reject(StudentReactivationRequest $reactivation, User $actor, string $reason): StudentReactivationRequest
    {
        return DB::transaction(function () use ($reactivation, $actor, $reason) {
            $request = StudentReactivationRequest::query()->lockForUpdate()->findOrFail($reactivation->id);
            if ($request->status !== 'submitted') {
                throw ValidationException::withMessages(['reactivation' => 'Pengajuan aktif kembali sudah diproses.']);
            }

            $approval = $request->approvalRequests()->where('status', 'pending')->latest()->firstOrFail();
            $this->approval->act($approval, $actor, 'rejected', $reason);
            $request->forceFill([
                'status' => 'rejected',
                'rejected_at' => now(),
                'processed_by' => $actor->id,
                'rejection_reason' => trim($reason),
            ])->save();
            $this->audit($actor, $request, 'student_reactivation.rejected', ['status' => 'rejected', 'reason' => trim($reason)]);

            return $request->fresh();
        });
    }

    private function audit(User $actor, StudentReactivationRequest $request, string $event, array $values): void
    {
        AuditLog::query()->create([
            'user_id' => $actor->id,
            'event' => $event,
            'module' => 'student',
            'entity_type' => StudentReactivationRequest::class,
            'entity_id' => $request->id,
            'new_values' => $values,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
            'user_agent' => app()->runningInConsole() ? null : request()->userAgent(),
        ]);
    }
}
