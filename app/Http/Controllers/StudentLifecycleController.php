<?php

namespace App\Http\Controllers;

use App\Models\Semester;
use App\Models\StudentEnrollment;
use App\Services\Student\StudentLeaveService;
use App\Services\Student\StudentReactivationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentLifecycleController extends Controller
{
    public function index(Request $request): View
    {
        $enrollment = $this->enrollment($request);
        $enrollment->load([
            'studentProfile',
            'studyProgram.department.faculty',
            'statusHistories.changedBy',
            'leaveRequests.semester',
            'leaveRequests.processedBy',
            'reactivationRequests.semester',
            'reactivationRequests.processedBy',
        ]);
        $universityId = $enrollment->studyProgram->department->faculty->university_id;
        $semesters = Semester::query()
            ->whereHas('academicYear', fn ($query) => $query->where('university_id', $universityId))
            ->latest('starts_on')
            ->limit(8)
            ->get();

        return view('portal.lifecycle', compact('enrollment', 'semesters'));
    }

    public function leave(Request $request, StudentLeaveService $service): RedirectResponse
    {
        $enrollment = $this->enrollment($request);
        $data = $request->validate([
            'semester_id' => ['required', 'ulid', 'exists:semesters,id'],
            'reason' => ['required', 'string', 'min:10', 'max:5000'],
        ]);
        $service->submit($enrollment, $data['semester_id'], $data['reason'], $request->user());

        return back()->with('success', 'Pengajuan cuti berhasil dikirim untuk diproses.');
    }

    public function reactivate(Request $request, StudentReactivationService $service): RedirectResponse
    {
        $enrollment = $this->enrollment($request);
        $data = $request->validate([
            'semester_id' => ['required', 'ulid', 'exists:semesters,id'],
            'reason' => ['required', 'string', 'min:10', 'max:5000'],
        ]);
        $service->submit($enrollment, $data['semester_id'], $data['reason'], $request->user());

        return back()->with('success', 'Pengajuan aktif kembali berhasil dikirim.');
    }

    private function enrollment(Request $request): StudentEnrollment
    {
        abort_unless($request->user()?->hasRole('mahasiswa'), 403);
        $enrollment = $request->user()->studentProfile?->enrollments()->latest()->first();
        abort_unless($enrollment, 404);

        return $enrollment;
    }
}
