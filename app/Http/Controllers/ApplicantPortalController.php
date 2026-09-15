<?php

namespace App\Http\Controllers;

use App\Models\StudyProgram;
use App\Services\Admission\ApplicantPortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApplicantPortalController extends Controller
{
    private function authorize(Request $request): void
    {
        abort_unless($request->user()?->hasRole(['applicant', 'super_admin']), 403);
    }

    public function dashboard(Request $request, ApplicantPortalService $service): View
    {
        $this->authorize($request);
        $applicant = $service->applicantFor($request->user())->load([
            'admissionPath', 'programChoices.studyProgram', 'documents',
            'payments', 'exams', 'interviews', 'reRegistration', 'statusHistories',
        ]);
        $universityId = $applicant->university_id;
        $programs = StudyProgram::query()->whereHas('department.faculty', fn ($query) => $query->where('university_id', $universityId))->with('department')->get();

        return view('admission.dashboard', compact('applicant', 'programs'));
    }

    public function biodata(Request $request, ApplicantPortalService $service): RedirectResponse
    {
        $this->authorize($request);
        $applicant = $service->applicantFor($request->user());
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'national_id' => ['nullable', 'string', 'max:30'],
            'nisn' => ['nullable', 'string', 'max:30'],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:male,female'],
            'phone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:1000'],
            'previous_school' => ['nullable', 'string', 'max:255'],
            'graduation_year' => ['nullable', 'integer', 'min:1990', 'max:2100'],
            'school_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);
        $service->updateBiodata($applicant, $data, $request->user());

        return back()->with('success', 'Biodata disimpan.');
    }

    public function choices(Request $request, ApplicantPortalService $service): RedirectResponse
    {
        $this->authorize($request);
        $applicant = $service->applicantFor($request->user());
        $data = $request->validate(['study_program_ids' => ['required', 'array', 'min:1', 'max:2'], 'study_program_ids.*' => ['exists:study_programs,id']]);
        $service->setProgramChoices($applicant, $data['study_program_ids'], $request->user());

        return back()->with('success', 'Pilihan program disimpan.');
    }

    public function document(Request $request, ApplicantPortalService $service): RedirectResponse
    {
        $this->authorize($request);
        $applicant = $service->applicantFor($request->user());
        $data = $request->validate([
            'kind' => ['required', 'string', 'max:50'],
            'label' => ['required', 'string', 'max:255'],
            'file_path' => ['required', 'string', 'max:500'],
        ]);
        $service->uploadDocument($applicant, $data['kind'], $data['label'], $data['file_path'], $request->user());

        return back()->with('success', 'Dokumen diunggah.');
    }

    public function submit(Request $request, ApplicantPortalService $service): RedirectResponse
    {
        $this->authorize($request);
        $service->submit($service->applicantFor($request->user()), $request->user());

        return back()->with('success', 'Berkas diajukan, lanjut ke pembayaran.');
    }

    public function payment(Request $request, ApplicantPortalService $service): RedirectResponse
    {
        $this->authorize($request);
        $applicant = $service->applicantFor($request->user());
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'method' => ['nullable', 'string', 'max:50'],
            'proof_path' => ['nullable', 'string', 'max:500'],
        ]);
        $service->recordPayment($applicant, $data, $request->user());

        return back()->with('success', 'Bukti pembayaran dikirim, menunggu verifikasi.');
    }

    public function reRegistration(Request $request, ApplicantPortalService $service): RedirectResponse
    {
        $this->authorize($request);
        $service->requestReRegistration($service->applicantFor($request->user()), $request->user());

        return back()->with('success', 'Daftar ulang diajukan.');
    }
}
