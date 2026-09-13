<?php

namespace Tests\Feature;

use App\Models\AdmissionPath;
use App\Models\Applicant;
use App\Models\ApplicantProgramChoice;
use App\Models\ApplicantReRegistration;
use App\Models\StudyProgram;
use App\Services\Admission\AdmissionWorkflowService;
use App\Services\Admission\ApplicantConversionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdmissionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_applicant_moves_through_workflow_and_converts_once(): void
    {
        $this->seed();
        $university = \App\Models\University::query()->firstOrFail();
        $program = StudyProgram::query()->firstOrFail();
        $path = AdmissionPath::query()->where('university_id', $university->id)->where('code', 'REG')->firstOrFail();
        $applicant = Applicant::create(['university_id' => $university->id, 'admission_path_id' => $path->id, 'registration_number' => 'PMB/2026/000002', 'name' => 'Naufal Ramadhan', 'email' => 'naufal@example.test', 'national_id' => '3174000000000001']);
        ApplicantProgramChoice::create(['applicant_id' => $applicant->id, 'study_program_id' => $program->id, 'preference' => 1]);
        $workflow = app(AdmissionWorkflowService::class);
        foreach (['submitted', 'payment_pending', 'payment_verified', 'document_verification', 'exam_scheduled', 'exam', 'interview', 'passed', 're_registration'] as $status) $applicant = $workflow->transition($applicant, $status);
        ApplicantReRegistration::create(['applicant_id' => $applicant->id, 'status' => 'approved']);
        $enrollment = app(ApplicantConversionService::class)->convert($applicant);
        $this->assertSame('active', $enrollment->status);
        $this->assertStringContainsString('-IF-', $enrollment->studentProfile->student_number);
        $this->assertDatabaseHas('applicant_status_histories', ['applicant_id' => $applicant->id, 'to_status' => 'passed']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'admission.converted', 'entity_id' => $applicant->id]);
        $this->assertSame($enrollment->id, app(ApplicantConversionService::class)->convert($applicant->fresh())->id);
        $this->assertDatabaseCount('student_profiles', 2);
    }
}
