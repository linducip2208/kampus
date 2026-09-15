<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\ClassSection;
use App\Models\Discussion;
use App\Models\StudentEnrollment;
use App\Services\Learning\AssignmentSubmissionService;
use App\Services\Learning\LearningCommunicationService;
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
                'quizzes' => fn ($query) => $query->whereIn('status', ['published', 'open'])->orderBy('starts_at'),                'announcements' => fn ($query) => $query->whereNotNull('published_at')->where('published_at', '<=', now())->orderByDesc('is_pinned')->latest('published_at'),
                'announcements.author',
                'discussions' => fn ($query) => $query->withCount('posts')->latest('updated_at'),
                'discussions.creator',
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

    public function createDiscussion(Request $request, ClassSection $section, LearningCommunicationService $service): RedirectResponse
    {
        $this->enrollment($request);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'min:3', 'max:20000'],
        ]);
        $discussion = $service->createDiscussion($section, $data, $request->user());

        return redirect()->route('portal.discussions.show', $discussion)->with('success', 'Diskusi berhasil dibuat.');
    }

    public function discussion(Request $request, Discussion $discussion, LearningCommunicationService $service): View
    {
        $enrollment = $this->enrollment($request);
        $discussion->load(['classSection.offering.course', 'creator', 'posts.author']);
        $service->authorizeParticipant($discussion->classSection, $request->user());

        return view('learning.discussion', [
            'layout' => 'portal.layout',
            'heading' => 'Diskusi kelas',
            'enrollment' => $enrollment,
            'discussion' => $discussion,
            'backRoute' => route('portal.learning.index'),
            'replyRoute' => route('portal.discussions.reply', $discussion),
            'lockRoute' => null,
        ]);
    }

    public function replyDiscussion(Request $request, Discussion $discussion, LearningCommunicationService $service): RedirectResponse
    {
        $this->enrollment($request);
        $data = $request->validate(['body' => ['required', 'string', 'min:2', 'max:20000']]);
        $service->reply($discussion, $data['body'], $request->user());

        return back()->with('success', 'Balasan berhasil dikirim.');
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
