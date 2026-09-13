<?php

namespace App\Services\Academic;

use App\Models\Setting;
use App\Models\StudyPlan;
use Illuminate\Support\Collection;

class KrsValidationService
{
    public function __construct(private readonly GradeCalculator $gradeCalculator) {}

    /** @return array<int, string> */
    public function validate(StudyPlan $plan): array
    {
        $plan->loadMissing([
            'enrollment.curriculum.courses',
            'enrollment.studyProgram.department.faculty',
            'enrollment.invoices',
            'semester',
            'items.classSection.offering.course.prerequisites',
            'items.classSection.schedules',
        ]);

        $errors = [];
        $enrollment = $plan->enrollment;
        $items = $plan->items;

        if (! $enrollment || $enrollment->status !== 'active') {
            $errors[] = 'Mahasiswa harus berstatus aktif untuk mengajukan KRS.';
        }
        if (! $plan->semester?->is_active) {
            $errors[] = 'Semester KRS belum aktif.';
        }
        if ($items->isEmpty()) {
            $errors[] = 'KRS minimal memiliki satu mata kuliah.';
        }

        $courseIds = $items->map(fn ($item) => $item->classSection?->offering?->course_id)->filter();
        if ($courseIds->duplicates()->isNotEmpty()) {
            $errors[] = 'Mata kuliah yang sama tidak boleh dipilih lebih dari satu kali.';
        }

        if ($enrollment?->curriculum) {
            $curriculumCourseIds = $enrollment->curriculum->courses->pluck('id');
            $outsideCurriculum = $courseIds->diff($curriculumCourseIds);
            if ($outsideCurriculum->isNotEmpty()) {
                $errors[] = 'Semua mata kuliah KRS harus tersedia pada kurikulum mahasiswa.';
            }
        }

        $totalCredits = (int) $items->sum('credits');
        if ($totalCredits > $this->maximumCredits($plan)) {
            $errors[] = sprintf('Total SKS %d melebihi batas %d SKS berdasarkan IPS sebelumnya.', $totalCredits, $this->maximumCredits($plan));
        }

        if ($this->hasScheduleConflict($items)) {
            $errors[] = 'Terdapat bentrok jadwal antar mata kuliah.';
        }

        foreach ($items as $item) {
            $course = $item->classSection?->offering?->course;
            $passedCourseIds = $this->passedCourseIds($enrollment, $plan);
            $missing = $course?->prerequisites?->pluck('id')->diff($passedCourseIds) ?? collect();
            if ($missing->isNotEmpty()) {
                $errors[] = sprintf('Prasyarat untuk mata kuliah %s belum terpenuhi.', $course->code);
            }
            if ($item->classSection && $this->isClassFull($item->classSection->id, $plan->id, (int) $item->classSection->capacity)) {
                $errors[] = sprintf('Kelas %s sudah penuh.', $item->classSection->code);
            }
        }

        $financialHold = (bool) Setting::value('academic', 'enforce_financial_hold', true, $enrollment?->studyProgram?->department?->faculty?->university_id);
        if ($financialHold && $enrollment?->invoices?->where('semester_id', $plan->semester_id)->sum(fn ($invoice) => $invoice->outstanding_amount) > 0) {
            $errors[] = 'KRS tertahan karena masih ada tagihan semester yang belum lunas.';
        }

        return array_values(array_unique($errors));
    }

    public function maximumCredits(StudyPlan $plan): int
    {
        $universityId = $plan->enrollment?->studyProgram?->department?->faculty?->university_id;
        $rules = Setting::value('academic', 'maximum_credits_by_gpa', [
            ['minimum' => 3.0, 'credits' => 24], ['minimum' => 2.5, 'credits' => 21],
            ['minimum' => 2.0, 'credits' => 18], ['minimum' => 0.0, 'credits' => 15],
        ], $universityId);

        $previous = $plan->enrollment?->studyPlans()
            ->where('id', '!=', $plan->id)
            ->whereIn('status', ['approved', 'finalized', 'locked'])
            ->with('items.grade.gradeScale')
            ->latest('approved_at')
            ->first();
        $ips = $previous ? $this->gradeCalculator->ips($previous->items) : 0.0;

        foreach (collect($rules)->sortByDesc('minimum') as $rule) {
            if ($ips >= (float) $rule['minimum']) {
                return (int) $rule['credits'];
            }
        }

        return 15;
    }

    private function hasScheduleConflict(Collection $items): bool
    {
        $slots = $items->flatMap(fn ($item) => $item->classSection?->schedules ?? [])
            ->map(fn ($schedule) => [$schedule->day_of_week, $schedule->starts_at, $schedule->ends_at]);
        foreach ($slots as $index => $left) {
            foreach ($slots as $otherIndex => $right) {
                if ($index >= $otherIndex || $left[0] !== $right[0]) {
                    continue;
                }
                if ($left[1] < $right[2] && $left[2] > $right[1]) {
                    return true;
                }
            }
        }
        return false;
    }

    private function isClassFull(string $classSectionId, string $planId, int $capacity): bool
    {
        return \App\Models\StudyPlanItem::query()
            ->where('class_section_id', $classSectionId)
            ->where('study_plan_id', '!=', $planId)
            ->whereHas('studyPlan', fn ($query) => $query->whereIn('status', ['approved', 'finalized', 'locked']))
            ->count() >= $capacity;
    }

    private function passedCourseIds($enrollment, StudyPlan $currentPlan): Collection
    {
        return $enrollment?->studyPlans()
            ->where('id', '!=', $currentPlan->id)
            ->whereIn('status', ['approved', 'finalized', 'locked'])
            ->with('items.classSection.offering.course', 'items.grade.gradeScale')
            ->get()
            ->flatMap(fn ($plan) => $plan->items)
            ->filter(fn ($item) => ($item->grade?->gradeScale?->grade_point ?? 0) >= 2.0)
            ->map(fn ($item) => $item->classSection?->offering?->course_id)
            ->filter() ?? collect();
    }
}
