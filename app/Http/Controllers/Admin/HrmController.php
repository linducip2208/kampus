<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeLeave;
use App\Models\LecturerWorkload;
use App\Models\OrganizationalUnit;
use App\Models\Position;
use App\Services\Hrm\BkdService;
use App\Services\Hrm\HrmService;
use App\Services\Security\UniversityScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HrmController extends Controller
{
    public function index(Request $request, UniversityScope $scope, BkdService $bkd): View
    {
        abort_unless($request->user()?->hasRole(['super_admin', 'hr', 'rektor', 'dekan']), 403);

        $units = $scope->direct(OrganizationalUnit::query()->withCount('positions')->latest(), $request->user())->get();
        $positions = $scope->direct(Position::query()->with('unit')->latest(), $request->user())->paginate(15, ['*'], 'position_page');
        $employees = $scope->lecturers(Employee::query()->with(['user', 'lecturerProfile'])->latest(), $request->user())->paginate(15, ['*'], 'employee_page');
        $leaves = $scope->relation(EmployeeLeave::query()->with('employee')->where('status', 'proposed')->latest(), $request->user(), 'employee')->get();
        $workloads = $scope->relation(LecturerWorkload::query()->with(['lecturer.employee', 'semester'])->where('status', 'submitted')->latest(), $request->user(), 'lecturer.employee')->get()
            ->map(fn ($workload) => ['workload' => $workload, 'summary' => $bkd->summary($workload)]);

        return view('admin.hrm', compact('units', 'positions', 'employees', 'leaves', 'workloads'));
    }

    public function storeUnit(Request $request, HrmService $service): RedirectResponse
    {
        abort_unless($request->user()?->hasRole(['super_admin', 'hr']), 403);
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'code' => ['required', 'string', 'max:50'], 'kind' => ['nullable', 'string', 'max:50']]);
        $universityId = $request->user()->roles()->firstOrFail()->pivot->university_id;
        $service->createUnit($universityId, $data);

        return back()->with('success', 'Unit organisasi ditambahkan.');
    }

    public function storePosition(Request $request, HrmService $service): RedirectResponse
    {
        abort_unless($request->user()?->hasRole(['super_admin', 'hr']), 403);
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'code' => ['required', 'string', 'max:50'], 'level' => ['nullable', 'string', 'max:50'], 'unit_id' => ['nullable', 'exists:organizational_units,id']]);
        $universityId = $request->user()->roles()->firstOrFail()->pivot->university_id;
        $service->createPosition($universityId, $data);

        return back()->with('success', 'Jabatan ditambahkan.');
    }

    public function decideLeave(Request $request, EmployeeLeave $leave, HrmService $service): RedirectResponse
    {
        abort_unless($request->user()?->hasRole(['super_admin', 'hr']), 403);
        $scope = app(UniversityScope::class);
        abort_unless($scope->relation(EmployeeLeave::query(), $request->user(), 'employee')->whereKey($leave->id)->exists(), 404);
        $data = $request->validate(['status' => ['required', 'in:approved,rejected'], 'reject_reason' => ['nullable', 'string', 'max:1000']]);
        $service->decideLeave($leave, $data['status'], $request->user(), $data['reject_reason'] ?? null);

        return back()->with('success', 'Cuti pegawai diproses.');
    }

    public function decideWorkload(Request $request, LecturerWorkload $workload, BkdService $service): RedirectResponse
    {
        abort_unless($request->user()?->hasRole(['super_admin', 'hr', 'dekan']), 403);
        $data = $request->validate(['status' => ['required', 'in:approved,rejected'], 'reject_reason' => ['nullable', 'string', 'max:1000']]);
        if ($data['status'] === 'approved') {
            $service->approve($workload, $request->user());
        } else {
            $service->reject($workload, $request->user(), $data['reject_reason'] ?? 'Ditolak.');
        }

        return back()->with('success', 'BKD diproses.');
    }
}
