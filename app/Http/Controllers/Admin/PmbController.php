<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdmissionExam;
use App\Models\Applicant;
use App\Models\ApplicantPayment;
use App\Services\Admission\PmbAdminService;
use App\Services\Security\UniversityScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PmbController extends Controller
{
    public function index(Request $request, UniversityScope $scope): View
    {
        abort_unless($request->user()?->hasRole(['super_admin', 'pmb', 'baak']), 403);

        $applicants = $scope->direct(
            Applicant::query()->with(['admissionPath', 'programChoices.studyProgram', 'payments', 'documents'])->latest(),
            $request->user()
        )->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))->paginate(20)->withQueryString();

        return view('admin.pmb', compact('applicants'));
    }

    public function verifyPayment(Request $request, ApplicantPayment $payment, PmbAdminService $service): RedirectResponse
    {
        $this->authorizeApplicant($request, $payment->applicant_id);
        $service->verifyPayment($payment, $request->user());

        return back()->with('success', 'Pembayaran applicant diverifikasi.');
    }

    public function verifyDocuments(Request $request, Applicant $applicant, PmbAdminService $service): RedirectResponse
    {
        $this->authorizeApplicant($request, $applicant->id);
        $service->verifyDocuments($applicant, $request->user());

        return back()->with('success', 'Dokumen applicant terverifikasi.');
    }

    public function scheduleExam(Request $request, Applicant $applicant, PmbAdminService $service): RedirectResponse
    {
        $this->authorizeApplicant($request, $applicant->id);
        $data = $request->validate(['title' => ['required', 'string', 'max:255'], 'scheduled_at' => ['required', 'date']]);
        $service->scheduleExam($applicant, $data['title'], $data['scheduled_at'], $request->user());

        return back()->with('success', 'Ujian dijadwalkan.');
    }

    public function scoreExam(Request $request, AdmissionExam $exam, PmbAdminService $service): RedirectResponse
    {
        $this->authorizeApplicant($request, $exam->applicant_id);
        $data = $request->validate(['score' => ['required', 'numeric', 'min:0', 'max:100']]);
        $service->scoreExam($exam, (float) $data['score'], $request->user());

        return back()->with('success', 'Nilai ujian disimpan.');
    }

    public function interview(Request $request, Applicant $applicant, PmbAdminService $service): RedirectResponse
    {
        $this->authorizeApplicant($request, $applicant->id);
        $data = $request->validate(['scheduled_at' => ['required', 'date'], 'score' => ['required', 'numeric', 'min:0', 'max:100']]);
        $service->interview($applicant, $data['scheduled_at'], (float) $data['score'], $request->user());

        return back()->with('success', 'Wawancara dicatat.');
    }

    public function decide(Request $request, Applicant $applicant, PmbAdminService $service): RedirectResponse
    {
        $this->authorizeApplicant($request, $applicant->id);
        $data = $request->validate(['passed' => ['required', 'boolean'], 'reason' => ['nullable', 'string', 'max:1000']]);
        $service->decide($applicant, (bool) $data['passed'], $request->user(), $data['reason'] ?? null);

        return back()->with('success', $data['passed'] ? 'Applicant diluluskan.' : 'Applicant dinyatakan tidak lulus.');
    }

    public function approveReRegistration(Request $request, Applicant $applicant, PmbAdminService $service): RedirectResponse
    {
        $this->authorizeApplicant($request, $applicant->id);
        $service->approveReRegistration($applicant, $request->user());

        return back()->with('success', 'Daftar ulang disetujui.');
    }

    public function convert(Request $request, Applicant $applicant, PmbAdminService $service): RedirectResponse
    {
        $this->authorizeApplicant($request, $applicant->id);
        $enrollment = $service->convert($applicant, $request->user());

        return back()->with('success', 'Mahasiswa dibuat: '.$enrollment->studentProfile->student_number);
    }

    private function authorizeApplicant(Request $request, string $applicantId): void
    {
        abort_unless($request->user()?->hasRole(['super_admin', 'pmb', 'baak']), 403);
        $scope = app(UniversityScope::class);
        abort_unless($scope->direct(Applicant::query(), $request->user())->whereKey($applicantId)->exists(), 404);
    }
}
