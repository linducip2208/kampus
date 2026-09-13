<?php

namespace App\Services\Student;

use App\Models\AuditLog;
use App\Models\StudentEnrollment;
use App\Models\StudentLeaveRequest;
use App\Models\StudentStatusHistory;
use App\Models\User;
use App\Services\Approval\ApprovalEngine;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentLeaveService
{
    public function __construct(private readonly ApprovalEngine $approval) {}

    public function submit(StudentEnrollment $enrollment, string $semesterId, string $reason, User $requester): StudentLeaveRequest
    {
        return DB::transaction(function () use ($enrollment, $semesterId, $reason, $requester) {
            $locked = StudentEnrollment::query()->lockForUpdate()->findOrFail($enrollment->id);
            if ($locked->status !== 'active') throw ValidationException::withMessages(['enrollment' => 'Hanya mahasiswa aktif yang dapat mengajukan cuti.']);
            $leave = StudentLeaveRequest::create(['student_enrollment_id' => $locked->id, 'semester_id' => $semesterId, 'requested_by' => $requester->id, 'status' => 'submitted', 'reason' => $reason, 'submitted_at' => now()]);
            $this->approval->request($leave, $locked->studyProgram->department->faculty->university_id, 'student_leave', $requester);
            AuditLog::create(['user_id' => $requester->id, 'event' => 'student_leave.submitted', 'module' => 'student', 'entity_type' => StudentLeaveRequest::class, 'entity_id' => $leave->id, 'new_values' => ['status' => 'submitted'], 'ip_address' => request()?->ip(), 'user_agent' => request()?->userAgent()]);
            return $leave->fresh();
        });
    }

    public function approve(StudentLeaveRequest $leave, User $actor): StudentLeaveRequest
    {
        return DB::transaction(function () use ($leave, $actor) {
            $approval = $leave->approvalRequests()->where('status', 'pending')->latest()->firstOrFail();
            $result = $this->approval->act($approval, $actor, 'approved');
            if ($result->status === 'approved') {
                $request = StudentLeaveRequest::query()->lockForUpdate()->findOrFail($leave->id);
                $enrollment = StudentEnrollment::query()->lockForUpdate()->findOrFail($request->student_enrollment_id);
                $enrollment->forceFill(['status' => 'leave'])->save();
                StudentStatusHistory::create(['student_enrollment_id' => $enrollment->id, 'from_status' => 'active', 'to_status' => 'leave', 'reason' => $request->reason, 'changed_by' => $actor->id, 'changed_at' => now()]);
                $request->forceFill(['status' => 'approved', 'approved_at' => now()])->save();
            }
            return $leave->fresh();
        });
    }
}
