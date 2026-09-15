<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicCalendar;
use App\Models\AcademicRule;
use App\Models\CourseCategory;
use App\Models\CourseEquivalence;
use App\Models\Holiday;
use App\Services\Academic\AcademicMasterService;
use App\Services\Security\UniversityScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AcademicMasterController extends Controller
{
    public function index(Request $request, UniversityScope $scope): View
    {
        abort_unless($request->user()?->hasRole(['super_admin', 'baak', 'staff_akademik', 'dekan']), 403);

        $calendars = $scope->direct(AcademicCalendar::query()->latest('starts_on'), $request->user())->paginate(15, ['*'], 'calendar_page');
        $holidays = $scope->direct(Holiday::query()->orderBy('date'), $request->user())->paginate(15, ['*'], 'holiday_page');
        $categories = $scope->direct(CourseCategory::query()->withCount('courses')->latest(), $request->user())->get();
        $equivalences = $scope->relation(CourseEquivalence::query()->with(['oldCourse', 'newCourse'])->latest(), $request->user(), 'oldCourse')->paginate(15, ['*'], 'equivalence_page');
        $rules = $scope->direct(AcademicRule::query()->latest(), $request->user())->get();

        return view('admin.academic-master', compact('calendars', 'holidays', 'categories', 'equivalences', 'rules'));
    }

    public function storeCalendar(Request $request, AcademicMasterService $service): RedirectResponse
    {
        abort_unless($request->user()?->hasRole(['super_admin', 'baak', 'staff_akademik']), 403);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'kind' => ['nullable', 'string', 'max:50'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
        ]);
        $universityId = $request->user()->roles()->firstOrFail()->pivot->university_id;
        $service->createCalendarEvent($universityId, $data, $request->user());

        return back()->with('success', 'Agenda kalender ditambahkan.');
    }

    public function storeHoliday(Request $request, AcademicMasterService $service): RedirectResponse
    {
        abort_unless($request->user()?->hasRole(['super_admin', 'baak', 'staff_akademik']), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'is_national' => ['nullable', 'boolean'],
        ]);
        $universityId = $request->user()->roles()->firstOrFail()->pivot->university_id;
        $service->createHoliday($universityId, $data, $request->user());

        return back()->with('success', 'Hari libur ditambahkan.');
    }

    public function storeCategory(Request $request, AcademicMasterService $service): RedirectResponse
    {
        abort_unless($request->user()?->hasRole(['super_admin', 'baak', 'staff_akademik']), 403);
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'code' => ['required', 'string', 'max:50']]);
        $universityId = $request->user()->roles()->firstOrFail()->pivot->university_id;
        $service->createCategory($universityId, $data);

        return back()->with('success', 'Kategori mata kuliah ditambahkan.');
    }
}
