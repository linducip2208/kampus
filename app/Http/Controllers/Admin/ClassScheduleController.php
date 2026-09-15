<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassSchedule;
use App\Models\ClassSection;
use App\Services\Academic\ClassScheduleService;
use App\Services\Security\UniversityScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassScheduleController extends Controller
{
    public function index(Request $request, UniversityScope $scope): View
    {
        $this->authorizePermission($request, 'class_sections.view');

        $sections = $scope->relation(ClassSection::query(), $request->user(), 'offering.course')
            ->with(['offering.course', 'lecturers.employee'])
            ->orderBy('code')
            ->get();

        $schedules = $scope->relation(ClassSchedule::query(), $request->user(), 'classSection.offering.course')
            ->with(['classSection.offering.course', 'classSection.lecturers.employee'])
            ->when($request->filled('day'), fn ($query) => $query->where('day_of_week', $request->integer('day')))
            ->orderBy('day_of_week')
            ->orderBy('starts_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.academic.schedules', compact('sections', 'schedules'));
    }

    public function store(Request $request, UniversityScope $scope, ClassScheduleService $service): RedirectResponse
    {
        $this->authorizePermission($request, 'class_sections.update');
        $data = $this->validated($request);
        $section = $scope->relation(ClassSection::query(), $request->user(), 'offering.course')
            ->findOrFail($data['class_section_id']);

        $service->create($section, $data, $request->user());

        return back()->with('success', 'Jadwal kuliah berhasil ditambahkan.');
    }

    public function update(Request $request, ClassSchedule $schedule, UniversityScope $scope, ClassScheduleService $service): RedirectResponse
    {
        $this->authorizePermission($request, 'class_sections.update');
        $schedule = $scope->relation(ClassSchedule::query(), $request->user(), 'classSection.offering.course')->findOrFail($schedule->id);

        $service->update($schedule, $this->validated($request), $request->user());

        return back()->with('success', 'Jadwal kuliah berhasil diperbarui.');
    }

    /** @return array{class_section_id:string, day_of_week:int, starts_at:string, ends_at:string, room:string|null} */
    private function validated(Request $request): array
    {
        return $request->validate([
            'class_section_id' => ['required', 'string'],
            'day_of_week' => ['required', 'integer', 'between:1,7'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
            'room' => ['nullable', 'string', 'max:100'],
        ]);
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_if($request->user()?->hasRole(['mahasiswa', 'dosen', 'dosen_wali', 'dosen_pembimbing', 'alumni']), 403);
        abort_unless($request->user()?->hasPermission($permission), 403);
    }
}
