<?php

namespace Tests\Feature;

use App\Models\ClassSection;
use App\Models\GradingComponent;
use App\Models\StudentGrade;
use App\Models\StudyPlanItem;
use App\Models\User;
use App\Services\Academic\GradeWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradePortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_lecturer_can_configure_scheme_and_open_grade_workspace(): void
    {
        $this->seed();
        $lecturer = User::query()->where('email', 'dosen@kampus.test')->firstOrFail();
        $section = ClassSection::query()->firstOrFail();

        $this->actingAs($lecturer)->get(route('lecturer.grades.index'))
            ->assertOk()->assertSee('Input dan publikasi nilai');

        $this->post(route('lecturer.grades.configure', $section), [
            'components' => [
                ['name' => 'Tugas', 'weight' => 40],
                ['name' => 'UAS', 'weight' => 60],
            ],
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('grading_components', ['class_section_id' => $section->id, 'name' => 'Tugas', 'weight' => 40]);
    }

    public function test_baak_can_open_scoped_grade_approval_page(): void
    {
        $this->seed();

        $this->actingAs(User::query()->where('email', 'baak@kampus.test')->firstOrFail())
            ->get(route('admin.grades.index'))
            ->assertOk()
            ->assertSee('Approval dan publikasi nilai');
    }

    public function test_student_cannot_access_lecturer_or_admin_grade_workspaces(): void
    {
        $this->seed();
        $student = User::query()->where('email', 'mahasiswa@kampus.test')->firstOrFail();

        $this->actingAs($student)->get(route('lecturer.grades.index'))->assertForbidden();
        $this->get(route('admin.grades.index'))->assertForbidden();
    }

    public function test_admin_can_approve_submitted_grade_through_tabler_action(): void
    {
        $this->seed();
        $lecturer = User::query()->where('email', 'dosen@kampus.test')->firstOrFail();
        $admin = User::query()->where('email', 'admin@kampus.test')->firstOrFail();
        $item = StudyPlanItem::query()->firstOrFail();
        $item->grade()->update(['status' => 'draft']);
        $components = collect([
            GradingComponent::create(['class_section_id' => $item->class_section_id, 'name' => 'Tugas', 'weight' => 40]),
            GradingComponent::create(['class_section_id' => $item->class_section_id, 'name' => 'UAS', 'weight' => 60]),
        ]);
        $workflow = app(GradeWorkflowService::class);
        $components->each(fn ($component) => $workflow->saveComponentScore($item, $component, 90, $lecturer));
        $grade = $workflow->submit($item, $lecturer);

        $this->actingAs($admin)->post(route('admin.grades.transition', $grade), ['action' => 'approve'])
            ->assertRedirect()->assertSessionHas('success');

        $this->assertSame('approved', StudentGrade::query()->findOrFail($grade->id)->status);
    }
}
