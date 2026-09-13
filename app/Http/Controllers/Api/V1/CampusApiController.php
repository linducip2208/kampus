<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Course;
use App\Models\Payment;
use App\Models\StudentInvoice;
use App\Models\StudentProfile;
use App\Models\StudyPlan;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CampusApiController
{
    public function students(Request $request): JsonResponse
    {
        $this->authorize($request, 'students.view');
        $query = StudentProfile::query()->with('enrollments.studyProgram')->latest();
        if ($request->user()->hasRole('mahasiswa')) $query->where('user_id', $request->user()->id);
        return response()->json($query->paginate(min($request->integer('per_page', 25), 100)));
    }

    public function lecturers(Request $request): JsonResponse
    {
        $this->authorize($request, 'employees.view');
        return response()->json(User::query()->whereHas('employee.lecturerProfile')->latest()->paginate(25));
    }

    public function courses(Request $request): JsonResponse
    {
        $this->authorize($request, 'courses.view');
        return response()->json(Course::query()->select(['id', 'university_id', 'code', 'name', 'theory_credits', 'practical_credits', 'recommended_term'])->orderBy('code')->paginate(50));
    }

    public function krs(Request $request): JsonResponse
    {
        $this->authorize($request, 'krs.view');
        $query = StudyPlan::query()->with(['semester', 'enrollment.studentProfile', 'items.classSection.offering.course'])->latest();
        if ($request->user()->hasRole('mahasiswa')) $query->whereHas('enrollment.studentProfile', fn ($q) => $q->where('user_id', $request->user()->id));
        return response()->json($query->paginate(25));
    }

    public function invoices(Request $request): JsonResponse
    {
        $this->authorize($request, 'student_invoices.view');
        $query = StudentInvoice::query()->with(['semester', 'enrollment.studentProfile'])->latest();
        if ($request->user()->hasRole('mahasiswa')) $query->whereHas('enrollment.studentProfile', fn ($q) => $q->where('user_id', $request->user()->id));
        return response()->json($query->paginate(25));
    }

    public function payments(Request $request): JsonResponse
    {
        $this->authorize($request, 'payments.view');
        $query = Payment::query()->with('enrollment.studentProfile')->latest();
        if ($request->user()->hasRole('mahasiswa')) $query->whereHas('enrollment.studentProfile', fn ($q) => $q->where('user_id', $request->user()->id));
        return response()->json($query->paginate(25));
    }

    private function authorize(Request $request, string $permission): void
    {
        abort_unless($request->user()?->hasPermission($permission), 403, 'Anda tidak memiliki permission untuk endpoint ini.');
    }
}
