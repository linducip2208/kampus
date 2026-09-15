<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\ClassSection;
use App\Models\CourseModule;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Services\Learning\AssignmentSubmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LecturerLearningPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_lecturer_can_author_modules_content_and_assignments(): void
    {
        $this->seed();
        $lecturer = User::query()->where('email', 'dosen@kampus.test')->firstOrFail();
        $section = ClassSection::query()->firstOrFail();

        $this->actingAs($lecturer)->get(route('lecturer.learning.index'))
            ->assertOk()->assertSee('Kelola pembelajaran');

        $this->post(route('lecturer.learning.modules.store', $section), [
            'title' => 'Struktur Data Linear',
            'status' => 'published',
        ])->assertRedirect()->assertSessionHas('success');
        $module = CourseModule::query()->where('title', 'Struktur Data Linear')->firstOrFail();

        $this->post(route('lecturer.learning.contents.store', $module), [
            'type' => 'text',
            'title' => 'Array dan Linked List',
            'body' => 'Materi pembelajaran terstruktur.',
            'publish_now' => 1,
        ])->assertRedirect()->assertSessionHas('success');

        $this->post(route('lecturer.learning.assignments.store', $section), [
            'title' => 'Latihan Linked List',
            'instructions' => 'Jelaskan operasi insert dan delete.',
            'status' => 'open',
            'max_attempts' => 2,
            'max_score' => 100,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('course_contents', ['course_module_id' => $module->id, 'title' => 'Array dan Linked List']);
        $this->assertDatabaseHas('assignments', ['class_section_id' => $section->id, 'title' => 'Latihan Linked List']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'lms.module_created', 'entity_id' => $module->id]);
    }

    public function test_assigned_lecturer_can_grade_submission_and_student_cannot_resubmit(): void
    {
        $this->seed();
        $lecturer = User::query()->where('email', 'dosen@kampus.test')->firstOrFail();
        $student = User::query()->where('email', 'mahasiswa@kampus.test')->firstOrFail();
        $assignment = Assignment::query()->create([
            'class_section_id' => ClassSection::query()->firstOrFail()->id,
            'title' => 'Tugas untuk dinilai',
            'status' => 'open',
            'max_attempts' => 3,
            'max_score' => 100,
        ]);
        $submission = app(AssignmentSubmissionService::class)->submit(
            $assignment,
            StudentEnrollment::query()->firstOrFail(),
            ['answer_text' => 'Jawaban mahasiswa'],
            $student,
        );

        $this->actingAs($lecturer)->post(route('lecturer.learning.submissions.grade', $submission), [
            'score' => 88,
            'feedback' => 'Analisis sudah tepat.',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('assignment_submissions', [
            'id' => $submission->id,
            'status' => 'graded',
            'score' => 88,
            'graded_by' => $lecturer->id,
        ]);
        $this->actingAs($student)->post(route('portal.assignments.submit', $assignment), [
            'answer_text' => 'Percobaan mengubah setelah dinilai.',
        ])->assertSessionHasErrors('submission');
        $this->assertDatabaseHas('audit_logs', ['event' => 'assignment.graded', 'entity_id' => $submission->id]);
    }

    public function test_student_cannot_open_lecturer_learning_workspace(): void
    {
        $this->seed();

        $this->actingAs(User::query()->where('email', 'mahasiswa@kampus.test')->firstOrFail())
            ->get(route('lecturer.learning.index'))
            ->assertForbidden();
    }
}
