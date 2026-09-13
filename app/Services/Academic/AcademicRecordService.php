<?php

namespace App\Services\Academic;

use App\Models\StudentEnrollment;
use Illuminate\Support\Collection;

class AcademicRecordService
{
    public function __construct(private readonly GradeCalculator $calculator) {}

    public function forEnrollment(StudentEnrollment $enrollment): array
    {
        $enrollment->loadMissing([
            'studyPlans.semester.academicYear',
            'studyPlans.items.classSection.offering.course',
            'studyPlans.items.grade.gradeScale',
        ]);

        $plans = $enrollment->studyPlans
            ->sortBy(fn ($plan) => $plan->semester?->starts_on?->timestamp ?? 0)
            ->values();

        return [
            'plans' => $plans->map(fn ($plan) => $this->semester($plan))->values(),
            'attempted_credits' => $this->calculator->attemptedCredits($plans->flatMap->items),
            'earned_credits' => $this->calculator->earnedCredits($plans->flatMap->items),
            'failed_credits' => $this->calculator->failedCredits($plans->flatMap->items),
            'gpa' => $this->calculator->ipk($plans),
        ];
    }

    private function semester($plan): array
    {
        return [
            'plan' => $plan,
            'ips' => $this->calculator->ips($plan->items),
            'attempted_credits' => $this->calculator->attemptedCredits($plan->items),
            'earned_credits' => $this->calculator->earnedCredits($plan->items),
            'failed_credits' => $this->calculator->failedCredits($plan->items),
        ];
    }
}
