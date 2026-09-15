<?php

namespace App\Http\Controllers;

use App\Models\ClassSection;
use App\Models\GradingComponent;
use App\Models\LecturerProfile;
use App\Models\StudyPlanItem;
use App\Services\Academic\GradeWorkflowService;
use App\Services\Academic\GradingSchemeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LecturerGradeController extends Controller
{
    public function index(Request $request): View
    {
        $lecturer = $this->lecturer($request);
        $sections = $lecturer->classSections()->with([
            'offering.course', 'gradingComponents',
            'studyPlanItems' => fn ($query) => $query->whereHas('studyPlan', fn ($plan) => $plan->whereIn('status', ['approved', 'finalized', 'locked']))
                ->with(['studyPlan.enrollment.studentProfile', 'grade.gradeScale', 'grade.revisionRequests', 'componentScores']),
        ])->orderBy('code')->get();

        return view('lecturer.grades', compact('lecturer', 'sections'));
    }

    public function configure(Request $request, ClassSection $section, GradingSchemeService $service): RedirectResponse
    {
        $this->lecturer($request);
        $data = $request->validate([
            'components' => ['required', 'array', 'min:1', 'max:10'],
            'components.*.name' => ['nullable', 'string', 'max:80'],
            'components.*.weight' => ['nullable', 'numeric', 'gt:0', 'lte:100'],
        ]);
        $service->configure($section, $data['components'], $request->user());

        return back()->with('success', 'Skema nilai berhasil disimpan.');
    }

    public function score(Request $request, StudyPlanItem $item, GradingComponent $component, GradeWorkflowService $service): RedirectResponse
    {
        $this->lecturer($request);
        $data = $request->validate(['score' => ['required', 'numeric', 'between:0,100'], 'feedback' => ['nullable', 'string', 'max:1000']]);
        $service->saveComponentScore($item, $component, $data['score'], $request->user(), $data['feedback'] ?? null);

        return back()->with('success', 'Nilai komponen berhasil disimpan.');
    }

    public function submit(Request $request, StudyPlanItem $item, GradeWorkflowService $service): RedirectResponse
    {
        $this->lecturer($request);
        $service->submit($item, $request->user());

        return back()->with('success', 'Nilai akhir berhasil disubmit untuk persetujuan.');
    }

    public function requestRevision(Request $request, StudyPlanItem $item, GradeWorkflowService $service): RedirectResponse
    {
        $this->lecturer($request);
        $grade = $item->grade()->firstOrFail();
        $data = $request->validate([
            'new_score' => ['required', 'numeric', 'between:0,100'],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ]);
        $service->requestRevision($grade, $data['new_score'], $data['reason'], $request->user());

        return back()->with('success', 'Permintaan revisi nilai dikirim untuk persetujuan.');
    }

    private function lecturer(Request $request): LecturerProfile
    {
        abort_unless($request->user()?->hasRole(['dosen', 'dosen_wali', 'dosen_pembimbing']), 403);
        $lecturer = $request->user()?->employee?->lecturerProfile;
        abort_unless($lecturer, 403);

        return $lecturer;
    }
}
