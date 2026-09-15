<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Graduation;
use App\Models\ScholarshipAward;
use App\Models\StudentEnrollment;
use App\Models\ThesisProposal;
use App\Services\Academic\GraduationService;
use App\Services\Academic\ScholarshipService;
use App\Services\Academic\ThesisService;
use App\Services\Security\UniversityScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AcademicLifecycleController extends Controller
{
    public function index(Request $request, UniversityScope $scope): View
    {
        abort_unless($request->user()?->hasRole(['super_admin', 'baak', 'dekan', 'ketua_prodi']), 403);

        $scholarships = $scope->relation(ScholarshipAward::query()->with(['scholarship', 'enrollment.studentProfile']), $request->user(), 'enrollment.studyProgram.department.faculty')
            ->latest()->paginate(15, ['*'], 'scholarship_page');
        $theses = $scope->relation(ThesisProposal::query()->with(['enrollment.studentProfile', 'advisor']), $request->user(), 'enrollment.studyProgram.department.faculty')
            ->latest()->paginate(15, ['*'], 'thesis_page');
        $graduations = $scope->relation(Graduation::query()->with(['enrollment.studentProfile', 'semester']), $request->user(), 'enrollment.studyProgram.department.faculty')
            ->latest()->paginate(15, ['*'], 'graduation_page');

        return view('admin.academic-lifecycle', compact('scholarships', 'theses', 'graduations'));
    }

    public function approveScholarship(Request $request, ScholarshipAward $award, ScholarshipService $service): RedirectResponse
    {
        $this->authorizeEnrollment($request, $award->student_enrollment_id);
        $service->approve($award, $request->user());

        return back()->with('success', 'Beasiswa disetujui.');
    }

    public function approveThesis(Request $request, ThesisProposal $proposal, ThesisService $service): RedirectResponse
    {
        $this->authorizeEnrollment($request, $proposal->student_enrollment_id);
        $service->approve($proposal, $request->user());

        return back()->with('success', 'Proposal tugas akhir disetujui.');
    }

    public function rejectThesis(Request $request, ThesisProposal $proposal, ThesisService $service): RedirectResponse
    {
        $this->authorizeEnrollment($request, $proposal->student_enrollment_id);
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:5000']]);
        $service->reject($proposal, $request->user(), $data['reason']);

        return back()->with('success', 'Proposal tugas akhir ditolak.');
    }

    public function approveGraduation(Request $request, Graduation $graduation, GraduationService $service): RedirectResponse
    {
        $this->authorizeEnrollment($request, $graduation->student_enrollment_id);
        $service->approve($graduation, $request->user());

        return back()->with('success', 'Yudisium disetujui, transkrip dan profil alumni diterbitkan.');
    }

    private function authorizeEnrollment(Request $request, string $enrollmentId): void
    {
        abort_unless($request->user()?->hasRole(['super_admin', 'baak', 'dekan', 'ketua_prodi']), 403);
        $scope = app(UniversityScope::class);
        abort_unless($scope->enrollments(StudentEnrollment::query(), $request->user())->whereKey($enrollmentId)->exists(), 404);
    }
}
