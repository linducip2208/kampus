<?php

namespace Tests\Feature;

use App\Models\StudentEnrollment;
use App\Models\StudentGrade;
use App\Models\User;
use App\Services\Academic\AcademicRecordService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicRecordPublicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_draft_and_submitted_grades_are_excluded_from_khs_and_gpa(): void
    {
        $this->seed();
        $enrollment = StudentEnrollment::query()->firstOrFail();
        $original = app(AcademicRecordService::class)->forEnrollment($enrollment);
        $grade = StudentGrade::query()->firstOrFail();
        $credits = $grade->studyPlanItem->credits;
        $grade->update(['status' => 'submitted']);

        $record = app(AcademicRecordService::class)->forEnrollment($enrollment->fresh());

        $this->assertSame($original['attempted_credits'] - $credits, $record['attempted_credits']);
        $this->assertFalse($record['transcript_items']->contains('id', $grade->study_plan_item_id));
    }

    public function test_student_can_print_published_transcript_in_indonesian_and_english(): void
    {
        $this->seed();
        $student = User::query()->where('email', 'mahasiswa@kampus.test')->firstOrFail();

        $this->actingAs($student)->get(route('portal.academic-record.print', ['lang' => 'id']))
            ->assertOk()->assertSee('Transkrip Akademik Sementara')->assertSee('Algoritma dan Pemrograman');
        $this->get(route('portal.academic-record.print', ['lang' => 'en']))
            ->assertOk()->assertSee('Temporary Academic Transcript')->assertSee('Print / Save PDF');
    }

    public function test_unpublished_grade_is_not_rendered_in_printed_transcript(): void
    {
        $this->seed();
        $student = User::query()->where('email', 'mahasiswa@kampus.test')->firstOrFail();
        $grade = StudentGrade::query()->firstOrFail();
        $courseName = $grade->studyPlanItem->classSection->offering->course->name;
        $grade->update(['status' => 'draft']);

        $this->actingAs($student)->get(route('portal.academic-record.print'))
            ->assertOk()->assertDontSee($courseName);
    }
}
