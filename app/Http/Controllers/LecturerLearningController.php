<?php

namespace App\Http\Controllers;

use App\Models\AssignmentSubmission;
use App\Models\ClassSection;
use App\Models\CourseModule;
use App\Models\LecturerProfile;
use App\Services\Learning\LearningAuthoringService;
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
        ])->orderBy('code')->get();

        return view('lecturer.learning', compact('lecturer', 'sections'));
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

    private function lecturer(Request $request): LecturerProfile
    {
        abort_unless($request->user()?->hasRole(['dosen', 'dosen_wali', 'dosen_pembimbing']), 403);
        $lecturer = $request->user()?->employee?->lecturerProfile;
        abort_unless($lecturer, 403);

        return $lecturer;
    }
}
