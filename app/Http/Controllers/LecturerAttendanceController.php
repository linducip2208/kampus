<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSession;
use App\Models\LectureMeeting;
use App\Models\LecturerProfile;
use App\Models\StudentEnrollment;
use App\Services\Academic\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LecturerAttendanceController extends Controller
{
    public function index(): View
    {
        $lecturer = $this->lecturer();
        $sections = $lecturer->classSections()
            ->with([
                'offering.course',
                'meetings' => fn ($query) => $query->with(['attendanceSessions.attendances.enrollment.studentProfile'])->latest('meeting_date'),
                'studyPlanItems' => fn ($query) => $query->whereHas('studyPlan', fn ($plan) => $plan->whereIn('status', ['approved', 'finalized', 'locked']))
                    ->with('studyPlan.enrollment.studentProfile'),
            ])
            ->orderBy('code')
            ->get();

        return view('lecturer.attendance', compact('lecturer', 'sections'));
    }

    public function open(Request $request, LectureMeeting $meeting, AttendanceService $service): RedirectResponse
    {
        $this->lecturer();
        $data = $request->validate([
            'method' => ['required', 'in:manual,qr,pin'],
            'duration' => ['required', 'integer', 'between:1,240'],
            'pin' => ['nullable', 'required_if:method,pin', 'regex:/^\d{4,8}$/'],
        ]);
        $result = $service->open($meeting, $data['method'], (int) $data['duration'], $request->user(), $data['pin'] ?? null);

        return back()->with('success', 'Sesi presensi berhasil dibuka.')
            ->with('attendance_credential', $result['credential'])
            ->with('attendance_session_id', $result['session']->id);
    }

    public function close(Request $request, AttendanceSession $session, AttendanceService $service): RedirectResponse
    {
        $this->lecturer();
        $service->close($session, $request->user());

        return back()->with('success', 'Sesi presensi berhasil ditutup.');
    }

    public function record(Request $request, AttendanceSession $session, AttendanceService $service): RedirectResponse
    {
        $this->lecturer();
        $data = $request->validate([
            'student_enrollment_id' => ['required', 'string'],
            'status' => ['required', 'in:present,late,sick,permission,absent'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);
        $enrollment = StudentEnrollment::query()->findOrFail($data['student_enrollment_id']);
        $service->recordManual($session, $enrollment, $data['status'], $request->user(), $data['reason'] ?? null);

        return back()->with('success', 'Presensi mahasiswa berhasil disimpan.');
    }

    private function lecturer(): LecturerProfile
    {
        abort_unless(Auth::user()?->hasRole(['dosen', 'dosen_wali', 'dosen_pembimbing']), 403);
        $lecturer = Auth::user()?->employee?->lecturerProfile;
        abort_unless($lecturer, 403);

        return $lecturer;
    }
}
