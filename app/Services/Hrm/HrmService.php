<?php

namespace App\Services\Hrm;

use App\Models\AuditLog;
use App\Models\Certification;
use App\Models\EducationHistory;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeLeave;
use App\Models\EmploymentContract;
use App\Models\OrganizationalUnit;
use App\Models\Position;
use App\Models\User;
use App\Services\NumberSequenceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HrmService
{
    public function __construct(private readonly NumberSequenceService $sequences) {}

    public function createUnit(string $universityId, array $data): OrganizationalUnit
    {
        if (OrganizationalUnit::query()->where('university_id', $universityId)->where('code', trim($data['code']))->exists()) {
            throw ValidationException::withMessages(['code' => 'Kode unit sudah dipakai.']);
        }

        return OrganizationalUnit::query()->create([
            'university_id' => $universityId,
            'parent_id' => $data['parent_id'] ?? null,
            'name' => trim($data['name']),
            'code' => trim($data['code']),
            'kind' => $data['kind'] ?? 'unit',
            'head_id' => $data['head_id'] ?? null,
            'status' => 'active',
        ]);
    }

    public function createPosition(string $universityId, array $data): Position
    {
        if (Position::query()->where('university_id', $universityId)->where('code', trim($data['code']))->exists()) {
            throw ValidationException::withMessages(['code' => 'Kode jabatan sudah dipakai.']);
        }

        return Position::query()->create([
            'university_id' => $universityId,
            'unit_id' => $data['unit_id'] ?? null,
            'name' => trim($data['name']),
            'code' => trim($data['code']),
            'level' => $data['level'] ?? 'staff',
            'description' => $data['description'] ?? null,
        ]);
    }

    public function createContract(Employee $employee, array $data, User $actor): EmploymentContract
    {
        return DB::transaction(function () use ($employee, $data, $actor) {
            if (! empty($data['ends_on']) && $data['ends_on'] < $data['starts_on']) {
                throw ValidationException::withMessages(['ends_on' => 'Tanggal selesai harus setelah tanggal mulai.']);
            }
            $universityId = $employee->university_id;
            $this->sequences->ensure($universityId, 'employment_contract', 'SPK/{year}/{number}');

            $contract = EmploymentContract::query()->create([
                'employee_id' => $employee->id,
                'position_id' => $data['position_id'] ?? null,
                'contract_number' => $this->sequences->next($universityId, 'employment_contract', ['year' => now()->year]),
                'type' => $data['type'] ?? 'tetap',
                'starts_on' => $data['starts_on'],
                'ends_on' => $data['ends_on'] ?? null,
                'salary' => $data['salary'] ?? 0,
                'status' => 'active',
            ]);
            $this->audit($actor, $contract, 'hrm.contract_created', ['contract_number' => $contract->contract_number]);

            return $contract->fresh();
        });
    }

    public function recordAttendance(Employee $employee, string $date, ?string $checkIn, ?string $checkOut, string $status, ?string $note, User $actor): EmployeeAttendance
    {
        if (! in_array($status, ['present', 'late', 'absent', 'leave', 'sick', 'remote'], true)) {
            throw ValidationException::withMessages(['status' => 'Status presensi tidak valid.']);
        }

        $attendance = EmployeeAttendance::query()->updateOrCreate(
            ['employee_id' => $employee->id, 'date' => $date],
            ['check_in' => $checkIn, 'check_out' => $checkOut, 'status' => $status, 'note' => $note],
        );
        $this->audit($actor, $attendance, 'hrm.attendance_recorded', ['date' => $date, 'status' => $status]);

        return $attendance->fresh();
    }

    public function requestLeave(Employee $employee, array $data, User $requester): EmployeeLeave
    {
        return DB::transaction(function () use ($employee, $data, $requester) {
            if ($data['ends_on'] < $data['starts_on']) {
                throw ValidationException::withMessages(['ends_on' => 'Tanggal selesai harus setelah tanggal mulai.']);
            }
            $overlap = EmployeeLeave::query()->where('employee_id', $employee->id)->where('status', 'approved')
                ->where('starts_on', '<=', $data['ends_on'])->where('ends_on', '>=', $data['starts_on'])->exists();
            if ($overlap) {
                throw ValidationException::withMessages(['leave' => 'Terdapat cuti disetujui yang bertabrakan.']);
            }

            return EmployeeLeave::query()->create([
                'employee_id' => $employee->id,
                'kind' => $data['kind'] ?? 'annual',
                'starts_on' => $data['starts_on'],
                'ends_on' => $data['ends_on'],
                'reason' => trim($data['reason']),
                'status' => 'proposed',
                'requested_by' => $requester->id,
            ]);
        });
    }

    public function decideLeave(EmployeeLeave $leave, string $status, User $actor, ?string $reason = null): EmployeeLeave
    {
        return DB::transaction(function () use ($leave, $status, $actor, $reason) {
            if (! in_array($status, ['approved', 'rejected'], true)) {
                throw ValidationException::withMessages(['status' => 'Status tidak valid.']);
            }
            $locked = EmployeeLeave::query()->lockForUpdate()->findOrFail($leave->id);
            if ($locked->status !== 'proposed') {
                throw ValidationException::withMessages(['leave' => 'Cuti sudah diproses.']);
            }
            $locked->forceFill([
                'status' => $status, 'decided_by' => $actor->id, 'decided_at' => now(),
                'reject_reason' => $status === 'rejected' ? trim((string) $reason) : null,
            ])->save();
            $this->audit($actor, $locked, 'hrm.leave_decided', ['status' => $status]);

            return $locked->fresh();
        });
    }

    public function addEducation(Employee $employee, array $data): EducationHistory
    {
        return EducationHistory::query()->create([
            'employee_id' => $employee->id,
            'level' => $data['level'],
            'institution' => trim($data['institution']),
            'major' => $data['major'] ?? null,
            'graduation_year' => $data['graduation_year'],
            'certificate_number' => $data['certificate_number'] ?? null,
        ]);
    }

    public function addCertification(Employee $employee, array $data): Certification
    {
        return Certification::query()->create([
            'employee_id' => $employee->id,
            'name' => trim($data['name']),
            'issuer' => trim($data['issuer']),
            'issued_on' => $data['issued_on'],
            'expires_on' => $data['expires_on'] ?? null,
            'credential_number' => $data['credential_number'] ?? null,
        ]);
    }

    private function audit(User $actor, object $entity, string $event, array $values): void
    {
        AuditLog::query()->create([
            'user_id' => $actor->id, 'event' => $event, 'module' => 'hrm',
            'entity_type' => $entity::class, 'entity_id' => $entity->id, 'new_values' => $values,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
            'user_agent' => app()->runningInConsole() ? null : request()->userAgent(),
        ]);
    }
}
