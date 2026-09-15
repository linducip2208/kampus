<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Course;
use App\Models\Payment;
use App\Models\StudentInvoice;
use App\Models\StudentProfile;
use App\Models\StudyPlan;
use App\Models\User;
use App\Services\Security\UniversityScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CampusApiController
{
    public function __construct(private readonly UniversityScope $scope) {}

    public function students(Request $request): JsonResponse
    {
        $this->authorize($request, 'students.view');
        $query = $this->scope->students(
            StudentProfile::query()->with('enrollments.studyProgram')->latest(),
            $request->user()
        );
        if ($request->user()->hasRole('mahasiswa')) {
            $query->where('user_id', $request->user()->id);
        }

        return response()->json($query->paginate(min($request->integer('per_page', 25), 100)));
    }

    public function lecturers(Request $request): JsonResponse
    {
        $this->authorize($request, 'employees.view');
        $query = $this->scope->lecturers(
            User::query()->whereHas('employee.lecturerProfile')->latest(),
            $request->user()
        );

        return response()->json($query->paginate(min($request->integer('per_page', 25), 100)));
    }

    public function courses(Request $request): JsonResponse
    {
        $this->authorize($request, 'courses.view');
        $query = $this->scope->direct(
            Course::query()->select(['id', 'university_id', 'code', 'name', 'theory_credits', 'practical_credits', 'recommended_term']),
            $request->user()
        );

        return response()->json($query->orderBy('code')->paginate(min($request->integer('per_page', 50), 100)));
    }

    public function krs(Request $request): JsonResponse
    {
        $this->authorize($request, 'krs.view');
        $query = $this->scope->studyPlans(
            StudyPlan::query()->with(['semester', 'enrollment.studentProfile', 'items.classSection.offering.course'])->latest(),
            $request->user()
        );
        if ($request->user()->hasRole('mahasiswa')) {
            $query->whereHas('enrollment.studentProfile', fn ($student) => $student->where('user_id', $request->user()->id));
        }

        return response()->json($query->paginate(min($request->integer('per_page', 25), 100)));
    }

    public function invoices(Request $request): JsonResponse
    {
        $this->authorize($request, 'student_invoices.view');
        $query = $this->scope->invoices(
            StudentInvoice::query()->with(['semester', 'enrollment.studentProfile'])->latest(),
            $request->user()
        );
        if ($request->user()->hasRole('mahasiswa')) {
            $query->whereHas('enrollment.studentProfile', fn ($student) => $student->where('user_id', $request->user()->id));
        }

        return response()->json($query->paginate(min($request->integer('per_page', 25), 100)));
    }

    public function payments(Request $request): JsonResponse
    {
        $this->authorize($request, 'payments.view');
        $query = $this->scope->payments(
            Payment::query()->with('enrollment.studentProfile')->latest(),
            $request->user()
        );
        if ($request->user()->hasRole('mahasiswa')) {
            $query->whereHas('enrollment.studentProfile', fn ($student) => $student->where('user_id', $request->user()->id));
        }

        return response()->json($query->paginate(min($request->integer('per_page', 25), 100)));
    }

    private function authorize(Request $request, string $permission): void
    {
        abort_unless($request->user()?->hasPermission($permission), 403, 'Anda tidak memiliki permission untuk endpoint ini.');
    }
}
