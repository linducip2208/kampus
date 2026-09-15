<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\AuditLog;
use App\Models\ClassSection;
use App\Models\CourseContent;
use App\Models\CourseModule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentLearningPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_only_sees_published_learning_content_from_enrolled_class(): void
    {
        $this->seed();
        $student = User::query()->where('email', 'mahasiswa@kampus.test')->firstOrFail();
        $section = ClassSection::query()->firstOrFail();
        $module = CourseModule::query()->create([
            'class_section_id' => $section->id,
            'title' => 'Modul Algoritma Dasar',
            'position' => 90,
            'status' => 'published',
        ]);
        CourseContent::query()->create([
            'course_module_id' => $module->id,
            'type' => 'text',
            'title' => 'Materi terpublikasi',
            'body' => 'Materi hanya untuk peserta kelas.',
            'position' => 1,
            'published_at' => now(),
        ]);
        CourseContent::query()->create([
            'course_module_id' => $module->id,
            'type' => 'text',
            'title' => 'Materi belum terbit',
            'position' => 2,
        ]);

        $this->actingAs($student)->get(route('portal.learning.index'))
            ->assertOk()
            ->assertSee('Modul Algoritma Dasar')
            ->assertSee('Materi terpublikasi')
            ->assertDontSee('Materi belum terbit');
    }

    public function test_assignment_attempt_limit_and_audit_are_enforced_through_portal(): void
    {
        $this->seed();
        $student = User::query()->where('email', 'mahasiswa@kampus.test')->firstOrFail();
        $assignment = Assignment::query()->create([
            'class_section_id' => ClassSection::query()->firstOrFail()->id,
            'title' => 'Analisis kompleksitas',
            'status' => 'open',
            'due_at' => now()->addDay(),
            'max_attempts' => 2,
        ]);

        $this->actingAs($student)->get(route('portal.assignments.show', $assignment))
            ->assertOk()->assertSee('Analisis kompleksitas');

        $this->post(route('portal.assignments.submit', $assignment), ['answer_text' => 'Jawaban percobaan pertama.'])
            ->assertRedirect()->assertSessionHas('success');
        $this->post(route('portal.assignments.submit', $assignment), ['answer_text' => 'Jawaban percobaan kedua.'])
            ->assertRedirect()->assertSessionHas('success');
        $this->post(route('portal.assignments.submit', $assignment), ['answer_text' => 'Percobaan ketiga ditolak.'])
            ->assertSessionHasErrors('submission');

        $this->assertDatabaseHas('assignment_submissions', [
            'assignment_id' => $assignment->id,
            'attempts_count' => 2,
            'answer_text' => 'Jawaban percobaan kedua.',
        ]);
        $this->assertSame(2, AuditLog::query()->where('event', 'assignment.submitted')->count());
    }

    public function test_staff_cannot_open_student_learning_workspace(): void
    {
        $this->seed();

        $this->actingAs(User::query()->where('email', 'admin@kampus.test')->firstOrFail())
            ->get(route('portal.learning.index'))
            ->assertForbidden();
    }
}
