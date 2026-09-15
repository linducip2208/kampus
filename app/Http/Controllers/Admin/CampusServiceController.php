<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LibraryBook;
use App\Models\MbkmProgram;
use App\Models\MbkmRegistration;
use App\Models\ResearchProject;
use App\Models\StudentActivity;
use App\Models\StudentEnrollment;
use App\Models\StudentOrganization;
use App\Services\Academic\CampusActivityService;
use App\Services\Security\UniversityScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CampusServiceController extends Controller
{
    public function index(Request $request, UniversityScope $scope): View
    {
        abort_unless($request->user()?->hasRole(['super_admin', 'baak', 'perpustakaan', 'lppm', 'kemahasiswaan', 'dekan']), 403);

        $books = $scope->direct(LibraryBook::query()->latest(), $request->user())->paginate(15, ['*'], 'book_page');
        $research = $scope->direct(ResearchProject::query()->with('lead')->latest(), $request->user())->paginate(15, ['*'], 'research_page');
        $organizations = $scope->direct(StudentOrganization::query()->withCount('members')->latest(), $request->user())->paginate(15, ['*'], 'org_page');
        $mbkmPrograms = $scope->direct(MbkmProgram::query()->withCount('registrations')->latest(), $request->user())->paginate(15, ['*'], 'mbkm_page');
        $pendingMbkm = MbkmRegistration::query()->where('status', 'proposed')->with(['program', 'enrollment.studentProfile'])->latest()->limit(20)->get()
            ->filter(fn ($item) => $scope->enrollments(StudentEnrollment::query(), $request->user())->whereKey($item->student_enrollment_id)->exists());
        $pendingActivities = StudentActivity::query()->where('status', 'proposed')->with('organization')->latest()->limit(20)->get();

        return view('admin.campus-services', compact('books', 'research', 'organizations', 'mbkmPrograms', 'pendingMbkm', 'pendingActivities'));
    }

    public function decideMbkm(Request $request, MbkmRegistration $registration, CampusActivityService $service): RedirectResponse
    {
        abort_unless($request->user()?->hasRole(['super_admin', 'baak', 'dekan']), 403);
        $data = $request->validate(['status' => ['required', 'in:approved,rejected'], 'credits' => ['nullable', 'integer', 'min:0', 'max:40']]);
        $service->decideMbkm($registration, $data['status'], $request->user(), (int) ($data['credits'] ?? 0));

        return back()->with('success', 'Pendaftaran MBKM diproses.');
    }

    public function decideActivity(Request $request, StudentActivity $activity, CampusActivityService $service): RedirectResponse
    {
        abort_unless($request->user()?->hasRole(['super_admin', 'baak', 'kemahasiswaan']), 403);
        $data = $request->validate(['status' => ['required', 'in:approved,rejected']]);
        $service->decideActivity($activity, $data['status'], $request->user());

        return back()->with('success', 'Kegiatan kemahasiswaan diproses.');
    }
}
