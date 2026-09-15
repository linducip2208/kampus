<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Course;
use App\Models\Graduation;
use App\Models\LibraryBook;
use App\Models\MbkmProgram;
use App\Models\Payment;
use App\Models\ResearchProject;
use App\Models\Scholarship;
use App\Models\StudentInvoice;
use App\Models\StudentProfile;
use App\Models\StudyPlan;
use App\Models\ThesisProposal;
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

    public function scholarships(Request $request): JsonResponse
    {
        $this->authorizeAny($request, ['students.view', 'payments.view']);
        $query = $this->scope->direct(Scholarship::query()->latest(), $request->user());

        return response()->json($query->paginate(min($request->integer('per_page', 25), 100)));
    }

    public function thesis(Request $request): JsonResponse
    {
        $this->authorizeAny($request, ['students.view', 'courses.view']);
        $query = $this->scope->relation(
            ThesisProposal::query()->with(['enrollment.studentProfile'])->latest(),
            $request->user(),
            'enrollment.studyProgram.department.faculty'
        );
        if ($request->user()->hasRole('mahasiswa')) {
            $query->whereHas('enrollment.studentProfile', fn ($student) => $student->where('user_id', $request->user()->id));
        }

        return response()->json($query->paginate(min($request->integer('per_page', 25), 100)));
    }

    public function graduations(Request $request): JsonResponse
    {
        $this->authorizeAny($request, ['students.view', 'courses.view']);
        $query = $this->scope->relation(
            Graduation::query()->with(['enrollment.studentProfile', 'semester'])->latest(),
            $request->user(),
            'enrollment.studyProgram.department.faculty'
        );

        return response()->json($query->paginate(min($request->integer('per_page', 25), 100)));
    }

    public function libraryBooks(Request $request): JsonResponse
    {
        $this->authorizeAny($request, ['courses.view', 'students.view']);
        $query = $this->scope->direct(LibraryBook::query()->latest(), $request->user());

        return response()->json($query->paginate(min($request->integer('per_page', 25), 100)));
    }

    public function research(Request $request): JsonResponse
    {
        $this->authorizeAny($request, ['courses.view', 'students.view']);
        $query = $this->scope->direct(ResearchProject::query()->with('lead')->latest(), $request->user());

        return response()->json($query->paginate(min($request->integer('per_page', 25), 100)));
    }

    public function mbkm(Request $request): JsonResponse
    {
        $this->authorizeAny($request, ['students.view', 'courses.view']);
        $query = $this->scope->direct(MbkmProgram::query()->latest(), $request->user());

        return response()->json($query->paginate(min($request->integer('per_page', 25), 100)));
    }

    private function authorize(Request $request, string $permission): void
    {
        abort_unless($request->user()?->hasPermission($permission), 403, 'Anda tidak memiliki permission untuk endpoint ini.');
    }

    private function authorizeAny(Request $request, array $permissions): void
    {
        $user = $request->user();
        abort_unless($user && ($user->hasRole('super_admin') || $user->hasAnyPermission($permissions)), 403, 'Anda tidak memiliki permission untuk endpoint ini.');
    }
}
