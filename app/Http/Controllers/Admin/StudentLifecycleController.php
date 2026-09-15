<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StudentEnrollment;
use App\Models\StudentLeaveRequest;
use App\Models\StudentReactivationRequest;
use App\Services\Security\UniversityScope;
use App\Services\Student\StudentLeaveService;
use App\Services\Student\StudentReactivationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentLifecycleController extends Controller
{
    public function index(Request $request, UniversityScope $scope): View
    {
        $this->authorizeStaff($request);
        $enrollmentIds = $scope->enrollments(StudentEnrollment::query()->select('id'), $request->user());

        $leaveRequests = StudentLeaveRequest::query()
            ->whereIn('student_enrollment_id', clone $enrollmentIds)
            ->with(['enrollment.studentProfile', 'enrollment.studyProgram', 'semester', 'requestedBy', 'approvalRequests.actions.actedBy'])
            ->latest('submitted_at')
            ->paginate(20, ['*'], 'leave_page');

        $reactivationRequests = StudentReactivationRequest::query()
            ->whereIn('student_enrollment_id', clone $enrollmentIds)
            ->with(['enrollment.studentProfile', 'enrollment.studyProgram', 'semester', 'requestedBy', 'approvalRequests.actions.actedBy'])
            ->latest('submitted_at')
            ->paginate(20, ['*'], 'reactivation_page');

        return view('admin.student-lifecycle', compact('leaveRequests', 'reactivationRequests'));
    }

    public function approveLeave(Request $request, StudentLeaveRequest $leave, StudentLeaveService $service): RedirectResponse
    {
        $this->authorizeRecord($request, $leave->student_enrollment_id);
        $service->approve($leave, $request->user());

        return back()->with('success', 'Pengajuan cuti disetujui.');
    }

    public function rejectLeave(Request $request, StudentLeaveRequest $leave, StudentLeaveService $service): RedirectResponse
    {
        $this->authorizeRecord($request, $leave->student_enrollment_id);
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:5000']]);
        $service->reject($leave, $request->user(), $data['reason']);

        return back()->with('success', 'Pengajuan cuti ditolak.');
    }

    public function approveReactivation(Request $request, StudentReactivationRequest $reactivation, StudentReactivationService $service): RedirectResponse
    {
        $this->authorizeRecord($request, $reactivation->student_enrollment_id);
        $service->approve($reactivation, $request->user());

        return back()->with('success', 'Pengajuan aktif kembali disetujui.');
    }

    public function rejectReactivation(Request $request, StudentReactivationRequest $reactivation, StudentReactivationService $service): RedirectResponse
    {
        $this->authorizeRecord($request, $reactivation->student_enrollment_id);
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:5000']]);
        $service->reject($reactivation, $request->user(), $data['reason']);

        return back()->with('success', 'Pengajuan aktif kembali ditolak.');
    }

    private function authorizeStaff(Request $request): void
    {
        abort_unless($request->user()?->hasRole(['super_admin', 'baak']), 403);
    }

    private function authorizeRecord(Request $request, string $enrollmentId): void
    {
        $this->authorizeStaff($request);
        $scope = app(UniversityScope::class);
        abort_unless($scope->enrollments(StudentEnrollment::query(), $request->user())->whereKey($enrollmentId)->exists(), 404);
    }
}
