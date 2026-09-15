<?php

namespace App\Services\Academic;

use App\Models\AuditLog;
use App\Models\CommunityService;
use App\Models\MbkmProgram;
use App\Models\MbkmRegistration;
use App\Models\ResearchMember;
use App\Models\ResearchProject;
use App\Models\StudentActivity;
use App\Models\StudentEnrollment;
use App\Models\StudentOrganization;
use App\Models\StudentOrganizationMember;
use App\Models\User;
use App\Services\Approval\ApprovalEngine;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CampusActivityService
{
    public function __construct(private readonly ApprovalEngine $approval) {}

    public function createResearch(string $universityId, array $data, User $actor): ResearchProject
    {
        $project = ResearchProject::query()->create([
            'university_id' => $universityId,
            'title' => trim($data['title']),
            'scheme' => $data['scheme'] ?? 'internal',
            'year' => $data['year'] ?? now()->year,
            'lead_id' => $data['lead_id'] ?? null,
            'status' => 'proposed',
            'budget' => $data['budget'] ?? 0,
        ]);
        $this->log($actor, $project, 'research.created');
        if (! empty($data['lead_id'])) {
            ResearchMember::query()->create(['research_project_id' => $project->id, 'lecturer_profile_id' => $data['lead_id'], 'role' => 'lead']);
        }

        return $project->fresh();
    }

    public function setResearchStatus(ResearchProject $project, string $status, User $actor): ResearchProject
    {
        return DB::transaction(function () use ($project, $status, $actor) {
            if (! in_array($status, ['proposed', 'approved', 'ongoing', 'completed', 'rejected'], true)) {
                throw ValidationException::withMessages(['status' => 'Status riset tidak valid.']);
            }
            $locked = ResearchProject::query()->lockForUpdate()->findOrFail($project->id);
            $locked->forceFill(['status' => $status])->save();
            $this->log($actor, $locked, 'research.status_changed', ['status' => $status]);

            return $locked->fresh();
        });
    }

    public function createCommunityService(string $universityId, array $data, User $actor): CommunityService
    {
        $service = CommunityService::query()->create([
            'university_id' => $universityId,
            'title' => trim($data['title']),
            'location' => $data['location'] ?? null,
            'year' => $data['year'] ?? now()->year,
            'lead_id' => $data['lead_id'] ?? null,
            'status' => 'proposed',
            'budget' => $data['budget'] ?? 0,
        ]);
        AuditLog::query()->create([
            'user_id' => $actor->id, 'event' => 'community.created', 'module' => 'research',
            'entity_type' => CommunityService::class, 'entity_id' => $service->id,
            'new_values' => ['title' => $service->title],
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
            'user_agent' => app()->runningInConsole() ? null : request()->userAgent(),
        ]);

        return $service->fresh();
    }

    public function createOrganization(string $universityId, array $data): StudentOrganization
    {
        return StudentOrganization::query()->create([
            'university_id' => $universityId,
            'name' => trim($data['name']),
            'code' => trim($data['code']),
            'kind' => $data['kind'] ?? 'ukm',
            'advisor_id' => $data['advisor_id'] ?? null,
            'is_active' => true,
        ]);
    }

    public function joinOrganization(StudentOrganization $org, StudentEnrollment $enrollment, string $role = 'member'): StudentOrganizationMember
    {
        return StudentOrganizationMember::query()->firstOrCreate(
            ['student_organization_id' => $org->id, 'student_enrollment_id' => $enrollment->id],
            ['role' => $role, 'joined_at' => now()],
        );
    }

    public function proposeActivity(StudentOrganization $org, string $title, string $startsAt, string $endsAt, User $requester, ?string $venue = null): StudentActivity
    {
        return DB::transaction(function () use ($org, $title, $startsAt, $endsAt, $requester, $venue) {
            if (strtotime($endsAt) <= strtotime($startsAt)) {
                throw ValidationException::withMessages(['ends_at' => 'Waktu selesai harus setelah mulai.']);
            }

            return StudentActivity::query()->create([
                'student_organization_id' => $org->id,
                'title' => trim($title),
                'venue' => $venue,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => 'proposed',
                'requested_by' => $requester->id,
            ]);
        });
    }

    public function decideActivity(StudentActivity $activity, string $status, User $actor): StudentActivity
    {
        return DB::transaction(function () use ($activity, $status, $actor) {
            if (! in_array($status, ['approved', 'rejected'], true)) {
                throw ValidationException::withMessages(['status' => 'Status kegiatan tidak valid.']);
            }
            $locked = StudentActivity::query()->lockForUpdate()->findOrFail($activity->id);
            $locked->forceFill(['status' => $status, 'decided_by' => $actor->id])->save();

            return $locked->fresh();
        });
    }

    public function createMbkmProgram(string $universityId, array $data): MbkmProgram
    {
        return MbkmProgram::query()->create([
            'university_id' => $universityId,
            'name' => trim($data['name']),
            'kind' => $data['kind'] ?? 'magang',
            'partner' => $data['partner'] ?? null,
            'quota' => $data['quota'] ?? 0,
            'status' => 'open',
        ]);
    }

    public function registerMbkm(MbkmProgram $program, StudentEnrollment $enrollment, User $requester): MbkmRegistration
    {
        return DB::transaction(function () use ($program, $enrollment, $requester) {
            $locked = MbkmProgram::query()->with('registrations')->lockForUpdate()->findOrFail($program->id);
            if ($locked->status !== 'open') {
                throw ValidationException::withMessages(['program' => 'Program MBKM tidak dibuka.']);
            }
            if ($locked->quota > 0 && $locked->registrations()->whereIn('status', ['proposed', 'approved'])->count() >= $locked->quota) {
                throw ValidationException::withMessages(['quota' => 'Kuota program habis.']);
            }
            if (MbkmRegistration::query()->where('mbkm_program_id', $locked->id)->where('student_enrollment_id', $enrollment->id)->exists()) {
                throw ValidationException::withMessages(['registration' => 'Sudah terdaftar pada program ini.']);
            }
            $lockedEnrollment = StudentEnrollment::query()->with('studyProgram.department.faculty')->findOrFail($enrollment->id);
            $registration = MbkmRegistration::query()->create([
                'mbkm_program_id' => $locked->id,
                'student_enrollment_id' => $lockedEnrollment->id,
                'status' => 'proposed',
                'requested_by' => $requester->id,
            ]);
            $this->approval->request($registration, $lockedEnrollment->studyProgram->department->faculty->university_id, 'mbkm', $requester);

            return $registration->fresh();
        });
    }

    public function decideMbkm(MbkmRegistration $registration, string $status, User $actor, int $credits = 0): MbkmRegistration
    {
        return DB::transaction(function () use ($registration, $status, $actor, $credits) {
            if (! in_array($status, ['approved', 'rejected'], true)) {
                throw ValidationException::withMessages(['status' => 'Status tidak valid.']);
            }
            $locked = MbkmRegistration::query()->lockForUpdate()->findOrFail($registration->id);
            if ($locked->status !== 'proposed') {
                throw ValidationException::withMessages(['registration' => 'Pendaftaran sudah diproses.']);
            }
            $approval = $locked->approvalRequests()->where('status', 'pending')->latest()->firstOrFail();
            $this->approval->act($approval, $actor, $status === 'approved' ? 'approved' : 'rejected');
            $locked->forceFill(['status' => $status, 'decided_by' => $actor->id, 'decided_at' => now(), 'credits_recognized' => $status === 'approved' ? $credits : 0])->save();

            return $locked->fresh();
        });
    }

    private function log(User $actor, ResearchProject $project, string $event, array $values = []): void
    {
        AuditLog::query()->create([
            'user_id' => $actor->id, 'event' => $event, 'module' => 'research',
            'entity_type' => ResearchProject::class, 'entity_id' => $project->id,
            'new_values' => $values ?: ['title' => $project->title],
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
            'user_agent' => app()->runningInConsole() ? null : request()->userAgent(),
        ]);
    }
}
