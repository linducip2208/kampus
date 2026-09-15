<?php

namespace App\Http\Controllers;

use App\Models\LecturerWorkload;
use App\Models\Semester;
use App\Services\Hrm\BkdService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LecturerBkdController extends Controller
{
    public function index(Request $request, BkdService $service): View
    {
        abort_unless($request->user()?->hasRole(['dosen', 'dosen_wali', 'dosen_pembimbing']), 403);
        $lecturer = $request->user()->employee?->lecturerProfile;
        abort_unless($lecturer, 404);
        $universityId = $request->user()->employee->university_id;

        $workloads = LecturerWorkload::query()->where('lecturer_profile_id', $lecturer->id)->with(['semester', 'activities'])->latest()->get()
            ->map(fn ($workload) => ['workload' => $workload, 'summary' => $service->summary($workload)]);
        $semesters = Semester::query()->whereHas('academicYear', fn ($query) => $query->where('university_id', $universityId))->latest('starts_on')->limit(8)->get();

        return view('lecturer.bkd', compact('lecturer', 'workloads', 'semesters'));
    }

    public function open(Request $request, BkdService $service): RedirectResponse
    {
        abort_unless($request->user()?->hasRole(['dosen', 'dosen_wali', 'dosen_pembimbing']), 403);
        $lecturer = $request->user()->employee?->lecturerProfile;
        abort_unless($lecturer, 404);
        $data = $request->validate(['semester_id' => ['required', 'exists:semesters,id']]);
        $service->openWorkload($lecturer, $data['semester_id']);

        return back()->with('success', 'BKD semester dibuka.');
    }

    public function addActivity(Request $request, LecturerWorkload $workload, BkdService $service): RedirectResponse
    {
        $lecturer = $request->user()->employee?->lecturerProfile;
        abort_unless($lecturer && $workload->lecturer_profile_id === $lecturer->id, 403);
        $data = $request->validate([
            'category' => ['required', 'in:teaching,research,service,supporting'],
            'title' => ['required', 'string', 'max:500'],
            'sks' => ['required', 'numeric', 'min:0.01', 'max:12'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);
        $service->addActivity($workload, $data, $request->user());

        return back()->with('success', 'Aktivitas BKD ditambahkan.');
    }

    public function submit(Request $request, LecturerWorkload $workload, BkdService $service): RedirectResponse
    {
        $lecturer = $request->user()->employee?->lecturerProfile;
        abort_unless($lecturer && $workload->lecturer_profile_id === $lecturer->id, 403);
        $service->submit($workload, $request->user());

        return back()->with('success', 'BKD diajukan untuk persetujuan.');
    }
}
