<?php

namespace App\Services\Academic;

use App\Models\Setting;
use App\Models\StudentEnrollment;
use Illuminate\Support\Collection;

class AcademicRecordService
{
    public function __construct(private readonly GradeCalculator $calculator) {}

    public function forEnrollment(StudentEnrollment $enrollment): array
    {
        $enrollment->loadMissing([
            'studyProgram.department.faculty',
            'studyPlans.semester.academicYear',
            'studyPlans.items.classSection.offering.course',
            'studyPlans.items.grade.gradeScale',
        ]);

        $plans = $enrollment->studyPlans
            ->filter(fn ($plan) => in_array($plan->status, ['approved', 'finalized', 'locked'], true))
            ->sortBy(fn ($plan) => $plan->semester?->starts_on?->timestamp ?? 0)
            ->map(function ($plan) {
                $plan->setRelation('items', $plan->items
                    ->filter(fn ($item) => in_array($item->grade?->status, ['published', 'locked'], true))
                    ->values());

                return $plan;
            })
            ->values();

        $policy = strtoupper((string) Setting::value(
            'academic',
            'repeat_course_policy',
            'BEST_GRADE',
            $enrollment->studyProgram?->department?->faculty?->university_id
        ));
        if (! in_array($policy, ['BEST_GRADE', 'LATEST', 'ALL_ATTEMPTS'], true)) {
            $policy = 'BEST_GRADE';
        }

        $transcriptItems = $this->applyRepeatPolicy($plans, $policy);

        return [
            'plans' => $plans->map(fn ($plan) => $this->semester($plan))->values(),
            'transcript_items' => $transcriptItems,
            'repeat_policy' => $policy,
            'attempted_credits' => $this->calculator->attemptedCredits($transcriptItems),
            'earned_credits' => $this->calculator->earnedCredits($transcriptItems),
            'failed_credits' => $this->calculator->failedCredits($transcriptItems),
            'gpa' => $this->calculator->gpa($transcriptItems),
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

    private function applyRepeatPolicy(Collection $plans, string $policy): Collection
    {
        $items = $plans->flatMap(fn ($plan) => $plan->items->map(function ($item) use ($plan) {
            $item->setRelation('academicPlan', $plan);

            return $item;
        }))->values();

        if ($policy === 'ALL_ATTEMPTS') {
            return $items;
        }

        $grouped = $items->groupBy(fn ($item) => $item->classSection?->offering?->course_id ?? $item->id);

        return $grouped->map(function (Collection $attempts) use ($policy) {
            if ($policy === 'LATEST') {
                return $attempts->sortByDesc(fn ($item) => $item->academicPlan?->semester?->starts_on?->timestamp ?? 0)->first();
            }

            return $attempts->sortByDesc(fn ($item) => [
                (float) ($item->grade?->gradeScale?->grade_point ?? 0),
                (float) ($item->grade?->final_score ?? 0),
            ])->first();
        })->filter()->values();
    }
}
