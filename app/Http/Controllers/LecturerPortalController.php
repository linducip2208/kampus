<?php

namespace App\Http\Controllers;

use App\Models\ClassSection;
use App\Models\LecturerProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LecturerPortalController extends Controller
{
    protected function lecturer(): LecturerProfile
    {
        abort_unless(Auth::user()?->hasRole(['dosen', 'dosen_wali', 'dosen_pembimbing']), 403);

        $lecturer = Auth::user()?->employee?->lecturerProfile;

        abort_unless($lecturer, 403, 'Akun ini belum terhubung ke profil dosen.');

        return $lecturer;
    }

    protected function sections(LecturerProfile $lecturer)
    {
        return $lecturer->classSections()
            ->with([
                'offering.semester.academicYear',
                'offering.course',
                'schedules',
                'meetings',
                'studyPlanItems.grade',
                'studyPlanItems.studyPlan.enrollment.studentProfile',
            ])
            ->orderBy('code')
            ->get();
    }

    public function dashboard()
    {
        $lecturer = $this->lecturer();
        $sections = $this->sections($lecturer);
        $advisees = $lecturer->enrollmentsAsAdvisor()
            ->with(['studentProfile', 'studyProgram'])
            ->where('status', 'active')
            ->latest()
            ->limit(6)
            ->get();
        $today = now()->dayOfWeek;
        $todaySections = $sections->filter(fn (ClassSection $section) => $section->schedules->contains('day_of_week', $today));
        $pendingGrades = $sections->flatMap(fn (ClassSection $section) => $section->studyPlanItems)
            ->filter(fn ($item) => $item->grade?->status === 'draft')
            ->count();

        return view('lecturer.dashboard', compact('lecturer', 'sections', 'advisees', 'todaySections', 'pendingGrades'));
    }

    public function schedule()
    {
        $lecturer = $this->lecturer();
        $sections = $this->sections($lecturer);

        return view('lecturer.schedule', compact('lecturer', 'sections'));
    }

    public function classes()
    {
        $lecturer = $this->lecturer();
        $sections = $this->sections($lecturer);

        return view('lecturer.classes', compact('lecturer', 'sections'));
    }

    public function advisees(Request $request)
    {
        $lecturer = $this->lecturer();
        $search = trim((string) $request->query('q'));
        $advisees = $lecturer->enrollmentsAsAdvisor()
            ->with(['studentProfile', 'studyProgram', 'statusHistories'])
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('studentProfile', fn ($student) => $student
                    ->where('full_name', 'like', "%{$search}%")
                    ->orWhere('student_number', 'like', "%{$search}%"));
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('lecturer.advisees', compact('lecturer', 'advisees', 'search'));
    }
}
