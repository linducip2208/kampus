<?php

namespace App\Services\Academic;

use Illuminate\Support\Collection;

class GradeCalculator
{
    public function attemptedCredits(Collection $items): int
    {
        return (int) $items->sum(fn ($item) => (int) ($item->credits ?? $item->grade?->studyPlanItem?->credits ?? 0));
    }

    public function earnedCredits(Collection $items): int
    {
        return (int) $items->sum(function ($item): int {
            $grade = $item->grade ?? $item;
            return ($grade->gradeScale?->grade_point ?? 0) >= 2.0
                ? (int) ($item->credits ?? $grade->studyPlanItem?->credits ?? 0)
                : 0;
        });
    }

    public function failedCredits(Collection $items): int
    {
        return max(0, $this->attemptedCredits($items) - $this->earnedCredits($items));
    }

    public function gpa(Collection $items): float
    {
        $credits = 0; $points = 0;
        foreach ($items as $item) {
            $grade = $item->grade ?? $item;
            $credit = (int) ($item->credits ?? $grade->studyPlanItem?->credits ?? 0);
            $point = (float) ($grade->gradeScale?->grade_point ?? 0);
            $credits += $credit; $points += $credit * $point;
        }
        return $credits > 0 ? round($points / $credits, 2) : 0.0;
    }

    public function ips(Collection $items): float
    {
        return $this->gpa($items);
    }

    public function ipk(Collection $plans): float
    {
        return $this->gpa($plans->flatMap(fn ($plan) => $plan->items ?? []));
    }
}
