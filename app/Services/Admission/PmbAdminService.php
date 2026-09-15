<?php

namespace App\Services\Admission;

use App\Models\AdmissionExam;
use App\Models\Applicant;
use App\Models\ApplicantInterview;
use App\Models\ApplicantPayment;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PmbAdminService
{
    public function __construct(
        private readonly AdmissionWorkflowService $workflow,
        private readonly ApplicantConversionService $conversion,
    ) {}

    public function verifyPayment(ApplicantPayment $payment, User $actor): ApplicantPayment
    {
        return DB::transaction(function () use ($payment, $actor) {
            $locked = ApplicantPayment::query()->with('applicant')->lockForUpdate()->findOrFail($payment->id);
            if ($locked->status !== 'submitted') {
                throw ValidationException::withMessages(['payment' => 'Pembayaran sudah diproses.']);
            }
            $locked->forceFill(['status' => 'verified', 'verified_by' => $actor->id, 'verified_at' => now()])->save();
            $this->workflow->transition($locked->applicant, 'payment_verified', $actor, 'Pembayaran diverifikasi.');

            return $locked->fresh();
        });
    }

    public function verifyDocuments(Applicant $applicant, User $actor): Applicant
    {
        return DB::transaction(function () use ($applicant, $actor) {
            $locked = Applicant::query()->with('documents')->lockForUpdate()->findOrFail($applicant->id);
            if ($locked->status !== 'payment_verified') {
                throw ValidationException::withMessages(['applicant' => 'Dokumen hanya diverifikasi setelah pembayaran.']);
            }
            if ($locked->documents->isEmpty()) {
                throw ValidationException::withMessages(['documents' => 'Applicant belum mengunggah dokumen.']);
            }
            foreach ($locked->documents()->where('status', 'pending')->get() as $document) {
                $document->forceFill(['status' => 'verified', 'verified_by' => $actor->id, 'verified_at' => now()])->save();
            }
            $this->workflow->transition($locked->fresh(), 'document_verification', $actor, 'Dokumen terverifikasi.');

            return $locked->fresh();
        });
    }

    public function scheduleExam(Applicant $applicant, string $title, string $scheduledAt, User $actor): AdmissionExam
    {
        return DB::transaction(function () use ($applicant, $title, $scheduledAt, $actor) {
            $locked = Applicant::query()->lockForUpdate()->findOrFail($applicant->id);
            if ($locked->status !== 'document_verification') {
                throw ValidationException::withMessages(['applicant' => 'Ujian dijadwalkan setelah verifikasi dokumen.']);
            }
            $exam = AdmissionExam::query()->create(['applicant_id' => $locked->id, 'title' => trim($title), 'scheduled_at' => $scheduledAt]);
            $this->workflow->transition($locked->fresh(), 'exam_scheduled', $actor, 'Ujian dijadwalkan.');

            return $exam->fresh();
        });
    }

    public function scoreExam(AdmissionExam $exam, float $score, User $actor): AdmissionExam
    {
        return DB::transaction(function () use ($exam, $score, $actor) {
            $locked = AdmissionExam::query()->with('applicant')->lockForUpdate()->findOrFail($exam->id);
            if ($score < 0 || $score > 100) {
                throw ValidationException::withMessages(['score' => 'Nilai 0-100.']);
            }
            $locked->forceFill(['score' => number_format($score, 2, '.', '')])->save();
            $locked->applicant->forceFill(['selection_score' => number_format($score, 2, '.', '')])->save();
            $this->workflow->transition($locked->applicant->fresh(), 'exam', $actor, 'Ujian dinilai.');

            return $locked->fresh();
        });
    }

    public function interview(Applicant $applicant, string $scheduledAt, float $score, User $actor): ApplicantInterview
    {
        return DB::transaction(function () use ($applicant, $scheduledAt, $score, $actor) {
            $locked = Applicant::query()->lockForUpdate()->findOrFail($applicant->id);
            if ($locked->status !== 'exam') {
                throw ValidationException::withMessages(['applicant' => 'Wawancara setelah ujian.']);
            }
            $interview = ApplicantInterview::query()->create([
                'applicant_id' => $locked->id, 'scheduled_at' => $scheduledAt, 'score' => number_format($score, 2, '.', ''),
            ]);
            $this->workflow->transition($locked->fresh(), 'interview', $actor, 'Wawancara selesai.');

            return $interview->fresh();
        });
    }

    public function decide(Applicant $applicant, bool $passed, User $actor, ?string $reason = null): Applicant
    {
        return DB::transaction(function () use ($applicant, $passed, $actor, $reason) {
            $locked = Applicant::query()->lockForUpdate()->findOrFail($applicant->id);
            if ($locked->status !== 'interview') {
                throw ValidationException::withMessages(['applicant' => 'Keputusan setelah wawancara.']);
            }

            return $this->workflow->transition($locked, $passed ? 'passed' : 'failed', $actor, $reason ?? ($passed ? 'Lulus seleksi.' : 'Tidak lulus.'));
        });
    }

    public function approveReRegistration(Applicant $applicant, User $actor): Applicant
    {
        return DB::transaction(function () use ($applicant, $actor) {
            $locked = Applicant::query()->with('reRegistration')->lockForUpdate()->findOrFail($applicant->id);
            if ($locked->status !== 're_registration' || ! $locked->reRegistration) {
                throw ValidationException::withMessages(['applicant' => 'Belum mengajukan daftar ulang.']);
            }
            $locked->reRegistration->forceFill(['status' => 'approved', 'verified_by' => $actor->id, 'verified_at' => now()])->save();

            return $locked->fresh();
        });
    }

    public function convert(Applicant $applicant, ?User $actor = null): StudentEnrollment
    {
        return $this->conversion->convert($applicant, $actor);
    }
}
