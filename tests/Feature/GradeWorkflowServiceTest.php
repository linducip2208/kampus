<?php

namespace Tests\Feature;

use App\Models\GradeScale;
use App\Models\GradingComponent;
use App\Models\StudyPlanItem;
use App\Models\User;
use App\Services\Academic\GradeWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GradeWorkflowServiceTest extends TestCase
{
    use RefreshDatabase;

    private GradeWorkflowService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(GradeWorkflowService::class);
        $this->seed();
    }

    public function test_complete_grade_lifecycle_calculates_publishes_locks_and_revises(): void
    {
        $lecturer = User::query()->where('email', 'dosen@kampus.test')->firstOrFail();
        $admin = User::query()->where('email', 'admin@kampus.test')->firstOrFail();
        $item = StudyPlanItem::query()->with('classSection.offering.course')->firstOrFail();
        $item->grade()->update(['status' => 'draft', 'final_score' => null, 'grade_scale_id' => null]);
        [$assignment, $exam] = $this->components($item, 40, 60);

        $this->service->saveComponentScore($item, $assignment, '80.00', $lecturer);
        $this->service->saveComponentScore($item, $exam, '90.00', $lecturer);
        $grade = $this->service->submit($item, $lecturer);

        $this->assertSame('submitted', $grade->status);
        $this->assertSame('86.00', $grade->final_score);
        $grade = $this->service->approve($grade, $admin);
        $grade = $this->service->publish($grade, $admin);
        $grade = $this->service->lock($grade, $admin);
        $this->assertSame('locked', $grade->status);
        $this->assertNotNull($grade->locked_at);

        $this->assertValidationError('grade', fn () => $this->service->saveComponentScore($item, $assignment, 70, $lecturer));

        GradeScale::create([
            'university_id' => $item->classSection->offering->course->university_id,
            'grade' => 'B', 'minimum_score' => 70, 'maximum_score' => 84.99, 'grade_point' => 3,
        ]);
        $revision = $this->service->requestRevision($grade, 82, 'Koreksi hasil verifikasi lembar UAS.', $lecturer);
        $revised = $this->service->approveRevision($revision, $admin);

        $this->assertSame('82.00', $revised->final_score);
        $this->assertSame('B', $revised->gradeScale->grade);
        $this->assertSame('locked', $revised->status);
        $this->assertDatabaseHas('grade_revision_requests', ['id' => $revision->id, 'status' => 'approved']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'grade.revision_approved', 'entity_id' => $grade->id]);
    }

    public function test_submit_rejects_component_weights_that_do_not_total_one_hundred(): void
    {
        $lecturer = User::query()->where('email', 'dosen@kampus.test')->firstOrFail();
        $item = StudyPlanItem::query()->firstOrFail();
        $item->grade()->update(['status' => 'draft']);
        [$assignment, $exam] = $this->components($item, 30, 60);
        $this->service->saveComponentScore($item, $assignment, 80, $lecturer);
        $this->service->saveComponentScore($item, $exam, 90, $lecturer);

        $this->assertValidationError('components', fn () => $this->service->submit($item, $lecturer));
    }

    public function test_submit_requires_every_component_score(): void
    {
        $lecturer = User::query()->where('email', 'dosen@kampus.test')->firstOrFail();
        $item = StudyPlanItem::query()->firstOrFail();
        $item->grade()->update(['status' => 'draft']);
        [$assignment] = $this->components($item, 40, 60);
        $this->service->saveComponentScore($item, $assignment, 80, $lecturer);

        $this->assertValidationError('scores', fn () => $this->service->submit($item, $lecturer));
    }

    /** @return array{GradingComponent, GradingComponent} */
    private function components(StudyPlanItem $item, int $firstWeight, int $secondWeight): array
    {
        return [
            GradingComponent::create(['class_section_id' => $item->class_section_id, 'name' => 'Tugas', 'weight' => $firstWeight]),
            GradingComponent::create(['class_section_id' => $item->class_section_id, 'name' => 'UAS', 'weight' => $secondWeight]),
        ];
    }

    private function assertValidationError(string $field, callable $callback): void
    {
        try {
            $callback();
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($field, $exception->errors());

            return;
        }

        $this->fail("Expected validation error for {$field}.");
    }
}
