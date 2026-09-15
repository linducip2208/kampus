<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GradeRevisionRequest;
use App\Models\StudentGrade;
use App\Services\Academic\GradeWorkflowService;
use App\Services\Security\UniversityScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GradeApprovalController extends Controller
{
    public function index(Request $request, UniversityScope $scope): View
    {
        $this->authorize($request);
        $grades = $scope->relation(StudentGrade::query(), $request->user(), 'studyPlanItem.classSection.offering.course')
            ->with(['studyPlanItem.studyPlan.enrollment.studentProfile', 'studyPlanItem.classSection.offering.course', 'gradeScale'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()->paginate(20)->withQueryString();

        $revisions = $scope->relation(GradeRevisionRequest::query(), $request->user(), 'grade.studyPlanItem.classSection.offering.course')
            ->with(['grade.studyPlanItem.studyPlan.enrollment.studentProfile', 'grade.studyPlanItem.classSection.offering.course', 'requester'])
            ->where('status', 'pending')->oldest()->get();

        return view('admin.academic.grades', compact('grades', 'revisions'));
    }

    public function transition(Request $request, StudentGrade $grade, UniversityScope $scope, GradeWorkflowService $service): RedirectResponse
    {
        $this->authorize($request);
        $grade = $scope->relation(StudentGrade::query(), $request->user(), 'studyPlanItem.classSection.offering.course')->findOrFail($grade->id);
        $action = $request->validate(['action' => ['required', 'in:approve,publish,lock']])['action'];
        $service->{$action}($grade, $request->user());

        return back()->with('success', 'Status nilai berhasil diperbarui.');
    }

    public function reviewRevision(Request $request, GradeRevisionRequest $revision, UniversityScope $scope, GradeWorkflowService $service): RedirectResponse
    {
        $this->authorize($request);
        $revision = $scope->relation(GradeRevisionRequest::query(), $request->user(), 'grade.studyPlanItem.classSection.offering.course')->findOrFail($revision->id);
        $data = $request->validate([
            'action' => ['required', 'in:approve,reject'],
            'rejection_reason' => ['required_if:action,reject', 'nullable', 'string', 'min:10', 'max:2000'],
        ]);

        if ($data['action'] === 'approve') {
            $service->approveRevision($revision, $request->user());
        } else {
            $service->rejectRevision($revision, $data['rejection_reason'], $request->user());
        }

        return back()->with('success', 'Permintaan revisi nilai berhasil diproses.');
    }

    private function authorize(Request $request): void
    {
        abort_unless($request->user()?->hasRole(['super_admin', 'baak', 'rektor']), 403);
    }
}
