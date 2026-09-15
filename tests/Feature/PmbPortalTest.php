<?php

namespace Tests\Feature;

use App\Models\AdmissionPath;
use App\Models\Applicant;
use App\Models\StudyProgram;
use App\Models\University;
use App\Models\User;
use App\Services\Admission\ApplicantPortalService;
use App\Services\Admission\PmbAdminService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PmbPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_pmb_flow_to_nim_with_idempotent_conversion(): void
    {
        $this->seed();
        $university = University::query()->firstOrFail();
        $path = AdmissionPath::query()->firstOrFail();
        $programs = StudyProgram::query()->limit(2)->pluck('id')->all();
        $admin = User::query()->where('email', 'admin@kampus.test')->firstOrFail();

        $applicant = app(ApplicantPortalService::class)->register($university->id, [
            'name' => 'Calon Baru', 'email' => 'calon@baru.test', 'password' => 'password123',
            'phone' => '0812000001', 'admission_path_id' => $path->id,
        ]);
        $this->assertSame('draft', $applicant->status);
        $user = User::query()->where('email', 'calon@baru.test')->firstOrFail();
        $this->assertTrue($user->hasRole('applicant'));

        app(ApplicantPortalService::class)->updateBiodata($applicant, [
            'previous_school' => 'SMA 1', 'graduation_year' => 2026, 'school_score' => '88.50',
        ], $user);
        app(ApplicantPortalService::class)->setProgramChoices($applicant, $programs, $user);
        app(ApplicantPortalService::class)->uploadDocument($applicant, 'ijazah', 'Ijazah', 'pmb/ijazah.pdf', $user);
        $submitted = app(ApplicantPortalService::class)->submit($applicant, $user);
        $this->assertSame('payment_pending', $submitted->status);

        $payment = app(ApplicantPortalService::class)->recordPayment($submitted, ['amount' => '500000.00', 'method' => 'transfer', 'proof_path' => 'pmb/bukti.pdf'], $user);
        $verified = app(PmbAdminService::class)->verifyPayment($payment, $admin);
        $this->assertSame('verified', $verified->status);
        $this->assertSame('payment_verified', $verified->applicant->fresh()->status);

        $docsOk = app(PmbAdminService::class)->verifyDocuments($verified->applicant, $admin);
        $this->assertSame('document_verification', $docsOk->status);

        $exam = app(PmbAdminService::class)->scheduleExam($docsOk, 'Ujian tulis', now()->addWeek()->toDateTimeString(), $admin);
        $scored = app(PmbAdminService::class)->scoreExam($exam, 85.5, $admin);
        $this->assertSame('exam', $scored->applicant->fresh()->status);

        app(PmbAdminService::class)->interview($scored->applicant, now()->addWeeks(2)->toDateTimeString(), 90, $admin);
        $decided = app(PmbAdminService::class)->decide(Applicant::query()->findOrFail($applicant->id), true, $admin);
        $this->assertSame('passed', $decided->status);

        $reReg = app(ApplicantPortalService::class)->requestReRegistration($decided, $user);
        $this->assertSame('re_registration', $reReg->status);
        app(PmbAdminService::class)->approveReRegistration($reReg, $admin);

        $enrollment = app(PmbAdminService::class)->convert(Applicant::query()->findOrFail($applicant->id), $admin);
        $this->assertSame('active', $enrollment->status);
        $again = app(PmbAdminService::class)->convert(Applicant::query()->findOrFail($applicant->id), $admin);
        $this->assertSame($enrollment->id, $again->id);
        $this->assertSame('student_created', Applicant::query()->findOrFail($applicant->id)->status);
    }

    public function test_applicant_cannot_touch_other_applicant_data(): void
    {
        $this->seed();
        $university = University::query()->firstOrFail();
        $path = AdmissionPath::query()->firstOrFail();

        $first = app(ApplicantPortalService::class)->register($university->id, [
            'name' => 'Satu', 'email' => 'satu@test.id', 'password' => 'password123', 'admission_path_id' => $path->id,
        ]);
        $second = app(ApplicantPortalService::class)->register($university->id, [
            'name' => 'Dua', 'email' => 'dua@test.id', 'password' => 'password123', 'admission_path_id' => $path->id,
        ]);
        $userOne = User::query()->where('email', 'satu@test.id')->firstOrFail();

        try {
            app(ApplicantPortalService::class)->updateBiodata($second, ['previous_school' => 'X'], $userOne);
            $this->fail('Akses data applicant lain seharusnya ditolak.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('authorization', $e->errors());
        }
        $this->assertSame($first->id, app(ApplicantPortalService::class)->applicantFor($userOne)->id);
    }

    public function test_admission_pages_render(): void
    {
        $this->seed();
        $this->get(route('admission.landing'))->assertOk();

        $university = University::query()->firstOrFail();
        $path = AdmissionPath::query()->firstOrFail();
        app(ApplicantPortalService::class)->register($university->id, [
            'name' => 'Tamu', 'email' => 'tamu@test.id', 'password' => 'password123', 'admission_path_id' => $path->id,
        ]);
        $user = User::query()->where('email', 'tamu@test.id')->firstOrFail();
        $this->actingAs($user)->get(route('admission.dashboard'))->assertOk();

        $admin = User::query()->where('email', 'admin@kampus.test')->firstOrFail();
        $this->actingAs($admin)->get(route('admin.pmb.index'))->assertOk();
    }
}
