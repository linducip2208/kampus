<?php

namespace App\Services\Admission;

use App\Events\ApplicantAccepted;
use App\Models\Applicant;
use App\Models\ApplicantStatusHistory;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdmissionWorkflowService
{
    private const TRANSITIONS = [
        'draft' => ['submitted'], 'submitted' => ['payment_pending'],
        'payment_pending' => ['payment_verified'], 'payment_verified' => ['document_verification'],
        'document_verification' => ['exam_scheduled'], 'exam_scheduled' => ['exam'],
        'exam' => ['interview'], 'interview' => ['passed', 'failed'],
        'passed' => ['re_registration'], 're_registration' => ['student_created'],
    ];

    public function transition(Applicant $applicant, string $toStatus, ?User $actor = null, ?string $reason = null): Applicant
    {
        return DB::transaction(function () use ($applicant, $toStatus, $actor, $reason) {
            $locked = Applicant::query()->lockForUpdate()->findOrFail($applicant->id);
            $fromStatus = $locked->status;
            if (! in_array($toStatus, self::TRANSITIONS[$fromStatus] ?? [], true)) {
                throw ValidationException::withMessages(['status' => "Perubahan status {$fromStatus} ke {$toStatus} tidak diizinkan."]);
            }
            $locked->forceFill(['status' => $toStatus, 'passed_at' => $toStatus === 'passed' ? now() : $locked->passed_at])->save();
            ApplicantStatusHistory::create(['applicant_id' => $locked->id, 'from_status' => $fromStatus, 'to_status' => $toStatus, 'reason' => $reason, 'changed_by' => $actor?->id, 'changed_at' => now()]);
            AuditLog::create(['user_id' => $actor?->id, 'event' => 'admission.status_changed', 'module' => 'admission', 'entity_type' => Applicant::class, 'entity_id' => $locked->id, 'old_values' => ['status' => $fromStatus], 'new_values' => ['status' => $toStatus], 'ip_address' => request()?->ip(), 'user_agent' => request()?->userAgent()]);
            if ($toStatus === 'passed') ApplicantAccepted::dispatch($locked);
            return $locked->fresh();
        });
    }
}
