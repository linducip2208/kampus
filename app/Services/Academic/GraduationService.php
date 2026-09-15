<?php

namespace App\Services\Academic;

use App\Models\AlumniProfile;
use App\Models\AuditLog;
use App\Models\Graduation;
use App\Models\StudentEnrollment;
use App\Models\TracerSurvey;
use App\Models\User;
use App\Services\Approval\ApprovalEngine;
use App\Services\NumberSequenceService;
use App\Services\Student\StudentStatusService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GraduationService
{
    public function __construct(
        private readonly ApprovalEngine $approval,
        private readonly StudentStatusService $status,
        private readonly NumberSequenceService $sequences,
    ) {}

    public function propose(StudentEnrollment $enrollment, string $semesterId, float $gpa, int $totalSks, User $requester): Graduation
    {
        return DB::transaction(function () use ($enrollment, $semesterId, $gpa, $totalSks, $requester) {
            $locked = StudentEnrollment::query()->with('studyProgram.department.faculty')->lockForUpdate()->findOrFail($enrollment->id);
            if ($locked->status !== 'active') {
                throw ValidationException::withMessages(['enrollment' => 'Hanya mahasiswa aktif yang dapat diyudisium.']);
            }
            if ($gpa < 2.0 || $totalSks < 144) {
                throw ValidationException::withMessages(['eligibility' => 'Syarat yudisium minimal IPK 2.00 dan 144 SKS.']);
            }
            if (Graduation::query()->where('student_enrollment_id', $locked->id)->whereIn('status', ['proposed', 'approved'])->exists()) {
                throw ValidationException::withMessages(['graduation' => 'Pengajuan yudisium sudah tersedia.']);
            }

            $graduation = Graduation::query()->create([
                'student_enrollment_id' => $locked->id,
                'semester_id' => $semesterId,
                'gpa' => number_format($gpa, 3, '.', ''),
                'total_sks' => $totalSks,
                'predicate' => $gpa >= 3.51 ? 'Dengan Pujian' : ($gpa >= 3.01 ? 'Sangat Memuaskan' : ($gpa >= 2.76 ? 'Memuaskan' : 'Baik')),
                'status' => 'proposed',
                'requested_by' => $requester->id,
            ]);
            $this->approval->request($graduation, $locked->studyProgram->department->faculty->university_id, 'graduation', $requester);
            $this->audit($requester, $graduation, 'graduation.proposed', ['status' => 'proposed', 'gpa' => $gpa]);

            return $graduation->fresh();
        });
    }

    public function approve(Graduation $graduation, User $actor): Graduation
    {
        return DB::transaction(function () use ($graduation, $actor) {
            $locked = Graduation::query()->with('enrollment.studyProgram.department.faculty')->lockForUpdate()->findOrFail($graduation->id);
            if ($locked->status !== 'proposed') {
                throw ValidationException::withMessages(['graduation' => 'Pengajuan yudisium sudah diproses.']);
            }
            $approval = $locked->approvalRequests()->where('status', 'pending')->latest()->firstOrFail();
            $result = $this->approval->act($approval, $actor, 'approved');
            if ($result->status !== 'approved') {
                return $locked->fresh();
            }

            $universityId = $locked->enrollment->studyProgram->department->faculty->university_id;
            $this->sequences->ensure($universityId, 'graduation_certificate', 'SKL/{year}/{number}');
            $locked->forceFill([
                'status' => 'approved',
                'decided_by' => $actor->id,
                'decided_at' => now(),
                'graduated_at' => now(),
                'certificate_number' => $this->sequences->next($universityId, 'graduation_certificate', ['year' => now()->year]),
            ])->save();

            $this->status->transition($locked->enrollment, 'graduated', $actor, 'Yudisium disetujui.');

            AlumniProfile::query()->firstOrCreate(
                ['student_enrollment_id' => $locked->student_enrollment_id],
                ['graduation_id' => $locked->id, 'employment_status' => 'unknown'],
            );

            $this->audit($actor, $locked, 'graduation.approved', ['status' => 'approved', 'certificate' => $locked->certificate_number]);

            return $locked->fresh();
        });
    }

    public function fillTracer(AlumniProfile $alumni, array $data, User $actor): TracerSurvey
    {
        return DB::transaction(function () use ($alumni, $data, $actor) {
            $locked = AlumniProfile::query()->lockForUpdate()->findOrFail($alumni->id);
            $survey = TracerSurvey::query()->updateOrCreate(
                ['alumni_profile_id' => $locked->id, 'graduation_year' => $data['graduation_year']],
                [
                    'employment_status' => $data['employment_status'],
                    'salary_range' => $data['salary_range'] ?? null,
                    'relevance' => $data['relevance'] ?? null,
                    'satisfaction' => $data['satisfaction'] ?? null,
                    'filled_at' => now(),
                ],
            );
            $locked->forceFill([
                'employment_status' => $data['employment_status'],
                'company' => $data['company'] ?? $locked->company,
                'position' => $data['position'] ?? $locked->position,
                'started_work_at' => $data['started_work_at'] ?? $locked->started_work_at,
            ])->save();

            AuditLog::query()->create([
                'user_id' => $actor->id,
                'event' => 'alumni.tracer_filled',
                'module' => 'alumni',
                'entity_type' => TracerSurvey::class,
                'entity_id' => $survey->id,
                'new_values' => ['employment_status' => $data['employment_status']],
                'ip_address' => app()->runningInConsole() ? null : request()->ip(),
                'user_agent' => app()->runningInConsole() ? null : request()->userAgent(),
            ]);

            return $survey->fresh();
        });
    }

    private function audit(User $actor, Graduation $graduation, string $event, array $values): void
    {
        AuditLog::query()->create([
            'user_id' => $actor->id,
            'event' => $event,
            'module' => 'graduation',
            'entity_type' => Graduation::class,
            'entity_id' => $graduation->id,
            'new_values' => $values,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
            'user_agent' => app()->runningInConsole() ? null : request()->userAgent(),
        ]);
    }
}
