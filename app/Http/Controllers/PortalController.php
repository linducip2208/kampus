<?php

namespace App\Http\Controllers;

use App\Models\StudentEnrollment;
use App\Services\Academic\AcademicRecordService;
use App\Services\Academic\GradeCalculator;
use Illuminate\Support\Facades\Auth;

class PortalController extends Controller
{
    protected function enrollment(): ?StudentEnrollment
    {
        return Auth::user()?->studentProfile?->enrollments()->with(['studyProgram.department.faculty', 'advisor.employee', 'studyPlans.items.classSection.offering.course', 'invoices.items'])->latest()->first();
    }

    public function dashboard()
    {
        $enrollment = $this->enrollment();
        abort_unless($enrollment, 404, 'Profil mahasiswa belum terhubung.');
        $plan = $enrollment->studyPlans->first();
        $grades = $plan?->items->map(fn ($item) => $item->grade)->filter();
        $gpa = app(GradeCalculator::class)->ips($plan?->items ?? collect());
        return view('portal.dashboard', compact('enrollment', 'plan', 'gpa'));
    }

    public function krs()
    {
        $enrollment = $this->enrollment();
        abort_unless($enrollment, 404);
        return view('portal.krs', compact('enrollment'));
    }

    public function invoices()
    {
        $enrollment = $this->enrollment();
        abort_unless($enrollment, 404);
        return view('portal.invoices', compact('enrollment'));
    }

    public function academicRecord(AcademicRecordService $records)
    {
        $enrollment = $this->enrollment();
        abort_unless($enrollment, 404);

        return view('portal.academic-record', [
            'enrollment' => $enrollment,
            'record' => $records->forEnrollment($enrollment),
        ]);
    }
}
