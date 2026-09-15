<?php

namespace App\Services\Academic;

use App\Models\AuditLog;
use App\Models\StudentEnrollment;
use App\Models\ThesisDefense;
use App\Models\ThesisProposal;
use App\Models\User;
use App\Services\Approval\ApprovalEngine;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ThesisService
{
    public function __construct(private readonly ApprovalEngine $approval) {}

    public function submit(StudentEnrollment $enrollment, string $title, ?string $abstract, User $requester, ?string $advisorId = null): ThesisProposal
    {
        return DB::transaction(function () use ($enrollment, $title, $abstract, $requester, $advisorId) {
            $locked = StudentEnrollment::query()->with('studentProfile', 'studyProgram.department.faculty')->lockForUpdate()->findOrFail($enrollment->id);
            if ($locked->studentProfile->user_id !== $requester->id && ! $requester->hasRole('super_admin')) {
                throw ValidationException::withMessages(['authorization' => 'Proposal hanya dapat diajukan untuk enrollment sendiri.']);
            }
            if (trim($title) === '') {
                throw ValidationException::withMessages(['title' => 'Judul proposal wajib diisi.']);
            }
            if (ThesisProposal::query()->where('student_enrollment_id', $locked->id)->whereIn('status', ['submitted', 'approved'])->exists()) {
                throw ValidationException::withMessages(['proposal' => 'Proposal aktif sudah tersedia.']);
            }

            $proposal = ThesisProposal::query()->create([
                'student_enrollment_id' => $locked->id,
                'advisor_id' => $advisorId,
                'title' => trim($title),
                'abstract' => $abstract ? trim($abstract) : null,
                'status' => 'submitted',
                'requested_by' => $requester->id,
            ]);
            $this->approval->request($proposal, $locked->studyProgram->department->faculty->university_id, 'thesis', $requester);
            $this->audit($requester, $proposal, 'thesis.submitted', ['status' => 'submitted']);

            return $proposal->fresh();
        });
    }

    public function approve(ThesisProposal $proposal, User $actor): ThesisProposal
    {
        return DB::transaction(function () use ($proposal, $actor) {
            $locked = ThesisProposal::query()->lockForUpdate()->findOrFail($proposal->id);
            if ($locked->status !== 'submitted') {
                throw ValidationException::withMessages(['proposal' => 'Proposal sudah diproses.']);
            }
            $approval = $locked->approvalRequests()->where('status', 'pending')->latest()->firstOrFail();
            $result = $this->approval->act($approval, $actor, 'approved');
            if ($result->status === 'approved') {
                $locked->forceFill(['status' => 'approved', 'decided_by' => $actor->id, 'decided_at' => now()])->save();
                $this->audit($actor, $locked, 'thesis.approved', ['status' => 'approved']);
            }

            return $locked->fresh();
        });
    }

    public function reject(ThesisProposal $proposal, User $actor, string $reason): ThesisProposal
    {
        return DB::transaction(function () use ($proposal, $actor, $reason) {
            $locked = ThesisProposal::query()->lockForUpdate()->findOrFail($proposal->id);
            if ($locked->status !== 'submitted') {
                throw ValidationException::withMessages(['proposal' => 'Proposal sudah diproses.']);
            }
            $approval = $locked->approvalRequests()->where('status', 'pending')->latest()->firstOrFail();
            $this->approval->act($approval, $actor, 'rejected', $reason);
            $locked->forceFill(['status' => 'rejected', 'decided_by' => $actor->id, 'decided_at' => now(), 'reject_reason' => trim($reason)])->save();
            $this->audit($actor, $locked, 'thesis.rejected', ['status' => 'rejected']);

            return $locked->fresh();
        });
    }

    public function scheduleDefense(ThesisProposal $proposal, string $scheduledAt, ?string $venue, User $actor): ThesisDefense
    {
        return DB::transaction(function () use ($proposal, $scheduledAt, $venue, $actor) {
            $locked = ThesisProposal::query()->lockForUpdate()->findOrFail($proposal->id);
            if ($locked->status !== 'approved') {
                throw ValidationException::withMessages(['proposal' => 'Sidang hanya dapat dijadwalkan untuk proposal approved.']);
            }

            $defense = ThesisDefense::query()->create([
                'thesis_proposal_id' => $locked->id,
                'scheduled_at' => $scheduledAt,
                'venue' => $venue,
                'status' => 'scheduled',
            ]);
            $this->audit($actor, $locked, 'thesis.defense_scheduled', ['defense_id' => $defense->id]);

            return $defense->fresh();
        });
    }

    public function gradeDefense(ThesisDefense $defense, float $score, User $actor): ThesisDefense
    {
        return DB::transaction(function () use ($defense, $score, $actor) {
            $locked = ThesisDefense::query()->lockForUpdate()->findOrFail($defense->id);
            if ($locked->status !== 'scheduled') {
                throw ValidationException::withMessages(['defense' => 'Sidang sudah dinilai.']);
            }
            if ($score < 0 || $score > 100) {
                throw ValidationException::withMessages(['score' => 'Nilai sidang 0-100.']);
            }

            $locked->forceFill([
                'score' => number_format($score, 2, '.', ''),
                'grade' => $score >= 85 ? 'A' : ($score >= 70 ? 'B' : ($score >= 60 ? 'C' : 'E')),
                'status' => 'graded',
                'graded_by' => $actor->id,
                'graded_at' => now(),
            ])->save();

            return $locked->fresh();
        });
    }

    private function audit(User $actor, ThesisProposal $proposal, string $event, array $values): void
    {
        AuditLog::query()->create([
            'user_id' => $actor->id,
            'event' => $event,
            'module' => 'thesis',
            'entity_type' => ThesisProposal::class,
            'entity_id' => $proposal->id,
            'new_values' => $values,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
            'user_agent' => app()->runningInConsole() ? null : request()->userAgent(),
        ]);
    }
}
