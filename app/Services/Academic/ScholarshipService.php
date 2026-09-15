<?php

namespace App\Services\Academic;

use App\Models\AuditLog;
use App\Models\Scholarship;
use App\Models\ScholarshipAward;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Services\Approval\ApprovalEngine;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ScholarshipService
{
    public function __construct(private readonly ApprovalEngine $approval) {}

    public function createScholarship(string $universityId, array $data): Scholarship
    {
        if (Money::toMinorUnits($data['amount']) <= 0) {
            throw ValidationException::withMessages(['amount' => 'Nominal beasiswa harus lebih dari nol.']);
        }

        return Scholarship::query()->create([
            'university_id' => $universityId,
            'name' => trim($data['name']),
            'code' => trim($data['code']),
            'kind' => $data['kind'] ?? 'academic',
            'amount' => $data['amount'],
            'quota' => $data['quota'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function propose(Scholarship $scholarship, StudentEnrollment $enrollment, User $requester, ?string $semesterId = null): ScholarshipAward
    {
        return DB::transaction(function () use ($scholarship, $enrollment, $requester, $semesterId) {
            $locked = StudentEnrollment::query()->with('studyProgram.department.faculty')->lockForUpdate()->findOrFail($enrollment->id);
            if (! $scholarship->is_active) {
                throw ValidationException::withMessages(['scholarship' => 'Beasiswa tidak aktif.']);
            }
            if ($locked->status !== 'active') {
                throw ValidationException::withMessages(['enrollment' => 'Hanya mahasiswa aktif yang dapat diusulkan.']);
            }
            $query = ScholarshipAward::query()->where('scholarship_id', $scholarship->id)->where('student_enrollment_id', $locked->id);
            if ($semesterId) {
                $query->where('semester_id', $semesterId);
            }
            if ($query->exists()) {
                throw ValidationException::withMessages(['award' => 'Pengajuan beasiswa sudah tersedia.']);
            }
            if ($scholarship->quota > 0) {
                $used = ScholarshipAward::query()->where('scholarship_id', $scholarship->id)->whereIn('status', ['proposed', 'approved'])->count();
                if ($used >= $scholarship->quota) {
                    throw ValidationException::withMessages(['quota' => 'Kuota beasiswa sudah habis.']);
                }
            }

            $award = ScholarshipAward::query()->create([
                'scholarship_id' => $scholarship->id,
                'student_enrollment_id' => $locked->id,
                'semester_id' => $semesterId,
                'amount' => $scholarship->amount,
                'status' => 'proposed',
                'requested_by' => $requester->id,
            ]);
            $this->approval->request($award, $scholarship->university_id, 'scholarship', $requester);
            $this->audit($requester, $award, 'scholarship.proposed', ['status' => 'proposed']);

            return $award->fresh();
        });
    }

    public function approve(ScholarshipAward $award, User $actor): ScholarshipAward
    {
        return DB::transaction(function () use ($award, $actor) {
            $locked = ScholarshipAward::query()->lockForUpdate()->findOrFail($award->id);
            if ($locked->status !== 'proposed') {
                throw ValidationException::withMessages(['award' => 'Pengajuan beasiswa sudah diproses.']);
            }
            $approval = $locked->approvalRequests()->where('status', 'pending')->latest()->firstOrFail();
            $result = $this->approval->act($approval, $actor, 'approved');
            if ($result->status === 'approved') {
                $locked->forceFill(['status' => 'approved', 'decided_by' => $actor->id, 'decided_at' => now()])->save();
                $this->audit($actor, $locked, 'scholarship.approved', ['status' => 'approved']);
            }

            return $locked->fresh();
        });
    }

    public function reject(ScholarshipAward $award, User $actor, string $reason): ScholarshipAward
    {
        return DB::transaction(function () use ($award, $actor, $reason) {
            $locked = ScholarshipAward::query()->lockForUpdate()->findOrFail($award->id);
            if ($locked->status !== 'proposed') {
                throw ValidationException::withMessages(['award' => 'Pengajuan beasiswa sudah diproses.']);
            }
            $approval = $locked->approvalRequests()->where('status', 'pending')->latest()->firstOrFail();
            $this->approval->act($approval, $actor, 'rejected', $reason);
            $locked->forceFill(['status' => 'rejected', 'decided_by' => $actor->id, 'decided_at' => now(), 'reject_reason' => trim($reason)])->save();
            $this->audit($actor, $locked, 'scholarship.rejected', ['status' => 'rejected']);

            return $locked->fresh();
        });
    }

    private function audit(User $actor, ScholarshipAward $award, string $event, array $values): void
    {
        AuditLog::query()->create([
            'user_id' => $actor->id,
            'event' => $event,
            'module' => 'scholarship',
            'entity_type' => ScholarshipAward::class,
            'entity_id' => $award->id,
            'new_values' => $values,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
            'user_agent' => app()->runningInConsole() ? null : request()->userAgent(),
        ]);
    }
}
