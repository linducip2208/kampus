<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Campus;
use App\Models\Department;
use App\Models\Laboratory;
use App\Models\Room;
use App\Services\Organization\FacilityService;
use App\Services\Security\UniversityScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    public function index(Request $request, UniversityScope $scope): View
    {
        abort_unless($request->user()?->hasRole(['super_admin', 'baak', 'dekan']), 403);

        $campuses = $scope->relation(Campus::query()->withCount('buildings')->latest(), $request->user(), 'university')->get();
        $buildings = $scope->relation(Building::query()->with(['campus', 'rooms'])->latest(), $request->user(), 'campus')->paginate(15, ['*'], 'building_page');
        $rooms = $scope->relation(Room::query()->with('building.campus')->latest(), $request->user(), 'building.campus')->paginate(15, ['*'], 'room_page');
        $laboratories = $scope->relation(Laboratory::query()->with(['department.faculty', 'room', 'head'])->latest(), $request->user(), 'department.faculty')->paginate(15, ['*'], 'lab_page');

        return view('admin.organization', compact('campuses', 'buildings', 'rooms', 'laboratories'));
    }

    public function storeBuilding(Request $request, FacilityService $service): RedirectResponse
    {
        abort_unless($request->user()?->hasRole(['super_admin', 'baak']), 403);
        $data = $request->validate([
            'campus_id' => ['required', 'exists:campuses,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50'],
            'floors' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);
        $scope = app(UniversityScope::class);
        abort_unless($scope->relation(Campus::query(), $request->user(), 'university')->whereKey($data['campus_id'])->exists(), 404);
        $service->createBuilding($data['campus_id'], $data, $request->user());

        return back()->with('success', 'Gedung ditambahkan.');
    }

    public function storeRoom(Request $request, FacilityService $service): RedirectResponse
    {
        abort_unless($request->user()?->hasRole(['super_admin', 'baak']), 403);
        $data = $request->validate([
            'building_id' => ['required', 'exists:buildings,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50'],
            'kind' => ['required', 'in:classroom,lab,office,hall,library,other'],
            'capacity' => ['nullable', 'integer', 'min:0'],
            'floor' => ['nullable', 'integer', 'min:1'],
        ]);
        $scope = app(UniversityScope::class);
        $building = $scope->relation(Building::query(), $request->user(), 'campus')->findOrFail($data['building_id']);
        $service->createRoom($building, $data, $request->user());

        return back()->with('success', 'Ruangan ditambahkan.');
    }

    public function storeLaboratory(Request $request, FacilityService $service): RedirectResponse
    {
        abort_unless($request->user()?->hasRole(['super_admin', 'baak', 'dekan']), 403);
        $data = $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'room_id' => ['nullable', 'exists:rooms,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50'],
            'kind' => ['nullable', 'string', 'max:50'],
        ]);
        $scope = app(UniversityScope::class);
        abort_unless($scope->relation(Department::query(), $request->user(), 'faculty')->whereKey($data['department_id'])->exists(), 404);
        $service->createLaboratory($data['department_id'], $data, $request->user());

        return back()->with('success', 'Laboratorium ditambahkan.');
    }
}
