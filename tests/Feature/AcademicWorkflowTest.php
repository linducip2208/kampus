<?php

namespace Tests\Feature;

use App\Actions\Academic\FinalizeStudyPlanAction;
use App\Models\AuditLog;
use App\Models\StudyPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_krs_can_be_finalized_and_audited(): void
    {
        $this->seed();
        $plan = StudyPlan::query()->firstOrFail();

        $result = app(FinalizeStudyPlanAction::class)->execute($plan);

        $this->assertSame('finalized', $result->status);
        $this->assertGreaterThan(0, $result->total_credits);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'krs.finalized',
            'entity_id' => $plan->id,
        ]);
    }

    public function test_krs_cannot_be_finalized_twice(): void
    {
        $this->seed();
        $plan = StudyPlan::query()->firstOrFail();
        app(FinalizeStudyPlanAction::class)->execute($plan);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(FinalizeStudyPlanAction::class)->execute($plan->fresh());
    }
}
