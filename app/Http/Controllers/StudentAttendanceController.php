<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSession;
use App\Models\StudentEnrollment;
use App\Services\Academic\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentAttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $enrollment = $this->enrollment($request);
        $plans = $enrollment->studyPlans()
            ->whereIn('status', ['approved', 'finalized', 'locked'])
            ->with(['semester', 'items.classSection.offering.course', 'items.classSection.meetings.attendanceSessions.attendances'])
            ->latest()
            ->get();

        return view('portal.attendance', compact('enrollment', 'plans'));
    }

    public function record(Request $request, AttendanceSession $session, AttendanceService $service): RedirectResponse
    {
        $enrollment = $this->enrollment($request);
        $data = $request->validate(['credential' => ['required', 'string', 'max:100']]);
        $service->recordSelf($session, $enrollment, $data['credential'], [
            'ip_address' => $request->ip(),
            'device_hash' => hash('sha256', (string) $request->userAgent()),
        ]);

        return back()->with('success', 'Presensi Anda berhasil dicatat.');
    }

    private function enrollment(Request $request): StudentEnrollment
    {
        abort_unless($request->user()?->hasRole('mahasiswa'), 403);

        return $request->user()->studentProfile?->enrollments()->with('studentProfile')->latest()->firstOrFail();
    }
}
