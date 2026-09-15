<?php

namespace App\Http\Controllers;

use App\Models\AssignmentSubmission;
use App\Models\ClassSection;
use App\Models\CourseModule;
use App\Models\Discussion;
use App\Models\LecturerProfile;
use App\Models\QuestionBank;
use App\Models\QuizAnswer;
use App\Services\Learning\LearningAuthoringService;
use App\Services\Learning\LearningCommunicationService;
use App\Services\Learning\QuizAuthoringService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LecturerLearningController extends Controller
{
    public function index(Request $request): View
    {
        $lecturer = $this->lecturer($request);
        $sections = $lecturer->classSections()->with([
            'offering.course',
            'courseModules.contents',
            'assignments.submissions.enrollment.studentProfile',
            'quizzes.questions',
            'quizzes.attempts.answers.question',
            'quizzes.attempts.enrollment.studentProfile',            'announcements.author',
            'discussions' => fn ($query) => $query->withCount('posts')->latest('updated_at'),
            'discussions.creator',
        ])->orderBy('code')->get();
        $questionBanks = QuestionBank::query()
            ->where('university_id', $lecturer->employee->university_id)
            ->with('questions.options')
            ->orderBy('name')
            ->get();

        return view('lecturer.learning', compact('lecturer', 'sections', 'questionBanks'));
    }

    public function module(Request $request, ClassSection $section, LearningAuthoringService $service): RedirectResponse
    {
        $this->lecturer($request);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'status' => ['required', 'in:draft,published'],
        ]);
        $service->createModule($section, $data, $request->user());

        return back()->with('success', 'Modul pembelajaran berhasil dibuat.');
    }

    public function content(Request $request, CourseModule $module, LearningAuthoringService $service): RedirectResponse
    {
        $this->lecturer($request);
        $data = $request->validate([
            'type' => ['required', 'in:text,url'],
            'title' => ['required', 'string', 'max:160'],
            'body' => ['nullable', 'string', 'max:50000'],
            'external_url' => ['nullable', 'url', 'max:2048'],
            'publish_now' => ['nullable', 'boolean'],
        ]);
        $service->createContent($module, $data, $request->user());

        return back()->with('success', 'Konten pembelajaran berhasil disimpan.');
    }

    public function assignment(Request $request, ClassSection $section, LearningAuthoringService $service): RedirectResponse
    {
        $this->lecturer($request);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'instructions' => ['nullable', 'string', 'max:20000'],
            'status' => ['required', 'in:draft,published,open'],
            'opens_at' => ['nullable', 'date'],
            'due_at' => ['nullable', 'date'],
            'allow_late' => ['nullable', 'boolean'],
            'max_attempts' => ['required', 'integer', 'between:1,10'],
            'max_score' => ['required', 'numeric', 'gt:0', 'lte:1000'],
        ]);
        $service->createAssignment($section, $data, $request->user());

        return back()->with('success', 'Assignment berhasil dibuat.');
    }

    public function grade(Request $request, AssignmentSubmission $submission, LearningAuthoringService $service): RedirectResponse
    {
        $this->lecturer($request);
        $data = $request->validate([
            'score' => ['required', 'numeric', 'min:0'],
            'feedback' => ['nullable', 'string', 'max:5000'],
        ]);
        $service->gradeSubmission($submission, $data['score'], $data['feedback'] ?? null, $request->user());

        return back()->with('success', 'Submission berhasil dinilai dan dikunci.');
    }

    public function announcement(Request $request, ClassSection $section, LearningCommunicationService $service): RedirectResponse
    {
        $this->lecturer($request);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'min:3', 'max:20000'],
            'is_pinned' => ['nullable', 'boolean'],
            'publish_now' => ['nullable', 'boolean'],
        ]);
        $service->announce($section, $data, $request->user());

        return back()->with('success', 'Pengumuman kelas berhasil disimpan.');
    }

    public function createDiscussion(Request $request, ClassSection $section, LearningCommunicationService $service): RedirectResponse
    {
        $this->lecturer($request);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'min:3', 'max:20000'],
        ]);
        $discussion = $service->createDiscussion($section, $data, $request->user());

        return redirect()->route('lecturer.discussions.show', $discussion)->with('success', 'Diskusi berhasil dibuat.');
    }

    public function discussion(Request $request, Discussion $discussion, LearningCommunicationService $service): View
    {
        $lecturer = $this->lecturer($request);
        $discussion->load(['classSection.offering.course', 'creator', 'posts.author']);
        $service->authorizeParticipant($discussion->classSection, $request->user());

        return view('learning.discussion', [
            'layout' => 'layouts.tabler.lecturer',
            'heading' => 'Diskusi kelas',
            'lecturer' => $lecturer,
            'discussion' => $discussion,
            'backRoute' => route('lecturer.learning.index'),
            'replyRoute' => route('lecturer.discussions.reply', $discussion),
            'lockRoute' => route('lecturer.discussions.lock', $discussion),
        ]);
    }

    public function replyDiscussion(Request $request, Discussion $discussion, LearningCommunicationService $service): RedirectResponse
    {
        $this->lecturer($request);
        $data = $request->validate(['body' => ['required', 'string', 'min:2', 'max:20000']]);
        $service->reply($discussion, $data['body'], $request->user());

        return back()->with('success', 'Balasan berhasil dikirim.');
    }

    public function lockDiscussion(Request $request, Discussion $discussion, LearningCommunicationService $service): RedirectResponse
    {
        $this->lecturer($request);
        $data = $request->validate(['locked' => ['required', 'boolean']]);
        $service->setLocked($discussion, $data['locked'], $request->user());

        return back()->with('success', $data['locked'] ? 'Diskusi dikunci.' : 'Diskusi dibuka kembali.');
    }

    public function questionBank(Request $request, QuizAuthoringService $service): RedirectResponse
    {
        $this->lecturer($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);
        $service->createBank($data, $request->user());

        return back()->with('success', 'Bank soal berhasil dibuat.');
    }

    public function question(Request $request, QuestionBank $bank, QuizAuthoringService $service): RedirectResponse
    {
        $this->lecturer($request);
        $data = $request->validate([
            'type' => ['required', 'in:single_choice,multiple_choice,multiple_answer,true_false,short_answer,essay'],
            'prompt' => ['required', 'string', 'max:20000'],
            'points' => ['required', 'numeric', 'gt:0', 'lte:1000'],
            'explanation' => ['nullable', 'string', 'max:20000'],
            'options_text' => ['nullable', 'string', 'max:20000'],
            'correct_answers' => ['nullable', 'string', 'max:20000'],
        ]);
        $data['options'] = preg_split('/\R/', (string) ($data['options_text'] ?? ''), flags: PREG_SPLIT_NO_EMPTY);
        $data['correct_answers'] = preg_split('/\R/', (string) ($data['correct_answers'] ?? ''), flags: PREG_SPLIT_NO_EMPTY);
        $service->createQuestion($bank, $data, $request->user());

        return back()->with('success', 'Pertanyaan berhasil ditambahkan.');
    }

    public function quiz(Request $request, ClassSection $section, QuizAuthoringService $service): RedirectResponse
    {
        $this->lecturer($request);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'instructions' => ['nullable', 'string', 'max:20000'],
            'status' => ['required', 'in:draft,published,open'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'between:1,1440'],
            'attempt_limit' => ['required', 'integer', 'between:1,10'],
            'question_ids' => ['required', 'array', 'min:1'],
            'question_ids.*' => ['ulid', 'distinct'],
            'randomize_questions' => ['nullable', 'boolean'],
            'randomize_options' => ['nullable', 'boolean'],
        ]);
        $service->createQuiz($section, $data, $request->user());

        return back()->with('success', 'Quiz berhasil dibuat.');
    }

    public function gradeQuizAnswer(Request $request, QuizAnswer $answer, QuizAuthoringService $service): RedirectResponse
    {
        $this->lecturer($request);
        $data = $request->validate([
            'score' => ['required', 'numeric', 'min:0'],
            'feedback' => ['nullable', 'string', 'max:5000'],
        ]);
        $service->gradeAnswer($answer, $data['score'], $data['feedback'] ?? null, $request->user());

        return back()->with('success', 'Jawaban essay berhasil dinilai.');
    }

    private function lecturer(Request $request): LecturerProfile
    {
        abort_unless($request->user()?->hasRole(['dosen', 'dosen_wali', 'dosen_pembimbing']), 403);
        $lecturer = $request->user()?->employee?->lecturerProfile;
        abort_unless($lecturer, 403);

        return $lecturer;
    }
}
