<?php

namespace App\Actions\Academic;

use App\Events\KrsFinalized;
use App\Models\AuditLog;
use App\Models\StudyPlan;
use App\Models\User;
use App\Services\Academic\KrsValidationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinalizeStudyPlanAction
{
    public function __construct(private readonly KrsValidationService $validator) {}

    public function execute(StudyPlan $studyPlan, ?User $actor = null): StudyPlan
    {
        return DB::transaction(function () use ($studyPlan, $actor): StudyPlan {
            $lockedPlan = StudyPlan::query()->lockForUpdate()->findOrFail($studyPlan->id);

            if ($lockedPlan->status !== 'approved') {
                throw ValidationException::withMessages(['study_plan' => 'Hanya KRS berstatus approved yang dapat difinalisasi.']);
            }

            $errors = $this->validator->validate($lockedPlan);
            if ($errors !== []) {
                throw ValidationException::withMessages(['study_plan' => implode(' ', $errors)]);
            }

            $oldStatus = $lockedPlan->status;
            $lockedPlan->forceFill([
                'status' => 'finalized',
                'total_credits' => $lockedPlan->items()->sum('credits'),
            ])->save();

            AuditLog::create([
                'user_id' => $actor?->id,
                'event' => 'krs.finalized',
                'module' => 'academic',
                'entity_type' => StudyPlan::class,
                'entity_id' => $lockedPlan->id,
                'old_values' => ['status' => $oldStatus],
                'new_values' => ['status' => $lockedPlan->status, 'total_credits' => $lockedPlan->total_credits],
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);

            KrsFinalized::dispatch($lockedPlan, $actor?->id);

            return $lockedPlan->fresh(['items.classSection.offering.course']);
        });
    }
}
