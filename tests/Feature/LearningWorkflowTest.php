<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\ClassSection;
use App\Models\StudentEnrollment;
use App\Services\Learning\AssignmentSubmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearningWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_registered_student_can_submit_and_resubmit_assignment(): void
    {
        $this->seed();
        $assignment = Assignment::create(['class_section_id' => ClassSection::query()->firstOrFail()->id, 'title' => 'Latihan struktur data', 'status' => 'open', 'due_at' => now()->addDay(), 'max_score' => 100]);
        $enrollment = StudentEnrollment::query()->firstOrFail();
        $service = app(AssignmentSubmissionService::class);
        $first = $service->submit($assignment, $enrollment, ['answer_text' => 'Jawaban pertama']);
        $second = $service->submit($assignment, $enrollment, ['answer_text' => 'Revisi jawaban']);
        $this->assertFalse($first->is_late);
        $this->assertSame($first->id, $second->id);
        $this->assertSame('Revisi jawaban', $second->answer_text);
        $this->assertDatabaseCount('assignment_submissions', 1);
    }

    public function test_closed_assignment_and_unregistered_student_are_rejected(): void
    {
        $this->seed();
        $assignment = Assignment::create(['class_section_id' => ClassSection::query()->firstOrFail()->id, 'title' => 'Ujian singkat', 'status' => 'closed', 'due_at' => now()->subHour()]);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(AssignmentSubmissionService::class)->submit($assignment, StudentEnrollment::query()->firstOrFail(), ['answer_text' => 'Tidak boleh']);
    }
}
