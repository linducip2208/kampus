<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\ClassSection;
use App\Models\StudentEnrollment;
use App\Services\Learning\AssignmentSubmissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StudentLearningController extends Controller
{
    public function index(Request $request): View
    {
        $enrollment = $this->enrollment($request);
        $sections = ClassSection::query()
            ->whereIn('id', $this->classIds($enrollment))
            ->with([
                'offering.course',
                'offering.semester.academicYear',
                'courseModules' => fn ($query) => $query->where('status', 'published')->orderBy('position'),
                'courseModules.contents' => fn ($query) => $query->whereNotNull('published_at')->where('published_at', '<=', now())->orderBy('position'),
                'assignments' => fn ($query) => $query->whereIn('status', ['published', 'open'])->orderBy('due_at'),
                'assignments.submissions' => fn ($query) => $query->where('student_enrollment_id', $enrollment->id),
                'quizzes' => fn ($query) => $query->whereIn('status', ['published', 'open'])->orderBy('starts_at'),
            ])
            ->orderBy('code')
            ->get();

        return view('portal.learning', compact('enrollment', 'sections'));
    }

    public function assignment(Request $request, Assignment $assignment): View
    {
        $enrollment = $this->enrollment($request);
        $this->assertEnrolled($enrollment, $assignment->class_section_id);
        $assignment->load(['classSection.offering.course']);
        $submission = $assignment->submissions()->where('student_enrollment_id', $enrollment->id)->first();

        return view('portal.assignment', compact('enrollment', 'assignment', 'submission'));
    }

    public function submit(Request $request, Assignment $assignment, AssignmentSubmissionService $service): RedirectResponse
    {
        $enrollment = $this->enrollment($request);
        $this->assertEnrolled($enrollment, $assignment->class_section_id);
        $data = $request->validate(['answer_text' => ['required', 'string', 'min:3', 'max:20000']]);
        $service->submit($assignment, $enrollment, $data, $request->user());

        return back()->with('success', 'Tugas berhasil dikirim.');
    }

    private function enrollment(Request $request): StudentEnrollment
    {
        abort_unless($request->user()?->hasRole('mahasiswa'), 403);
        $enrollment = $request->user()->studentProfile?->enrollments()->latest()->first();
        abort_unless($enrollment, 404);

        return $enrollment;
    }

    private function classIds(StudentEnrollment $enrollment)
    {
        return DB::table('study_plan_items')
            ->join('study_plans', 'study_plans.id', '=', 'study_plan_items.study_plan_id')
            ->where('study_plans.student_enrollment_id', $enrollment->id)
            ->whereIn('study_plans.status', ['approved', 'finalized', 'locked'])
            ->select('study_plan_items.class_section_id');
    }

    private function assertEnrolled(StudentEnrollment $enrollment, string $classSectionId): void
    {
        abort_unless($this->classIds($enrollment)->where('study_plan_items.class_section_id', $classSectionId)->exists(), 404);
    }
}
