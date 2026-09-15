<?php

namespace App\Services\Admission;

use App\Models\AdmissionPath;
use App\Models\Applicant;
use App\Models\ApplicantDocument;
use App\Models\ApplicantPayment;
use App\Models\ApplicantProgramChoice;
use App\Models\ApplicantReRegistration;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Services\NumberSequenceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ApplicantPortalService
{
    public function __construct(
        private readonly AdmissionWorkflowService $workflow,
        private readonly NumberSequenceService $sequences,
    ) {}

    public function register(string $universityId, array $data): Applicant
    {
        return DB::transaction(function () use ($universityId, $data) {
            if (User::query()->where('email', $data['email'])->exists()) {
                throw ValidationException::withMessages(['email' => 'Email sudah terdaftar.']);
            }
            $path = AdmissionPath::query()->where('university_id', $universityId)->whereKey($data['admission_path_id'])->first();
            if (! $path || $path->status !== 'active') {
                throw ValidationException::withMessages(['admission_path_id' => 'Jalur pendaftaran tidak tersedia.']);
            }

            $user = User::query()->create(['name' => trim($data['name']), 'email' => trim($data['email']), 'password' => Hash::make($data['password'])]);
            $role = Role::query()->firstOrCreate(['name' => 'applicant'], ['label' => 'Calon Mahasiswa']);
            $user->roles()->attach($role->id, ['university_id' => $universityId]);

            $this->sequences->ensure($universityId, 'applicant_registration', 'PMB/{year}/{number}');
            $applicant = Applicant::query()->create([
                'university_id' => $universityId,
                'admission_path_id' => $path->id,
                'registration_number' => $this->sequences->next($universityId, 'applicant_registration', ['year' => now()->year]),
                'name' => trim($data['name']),
                'email' => trim($data['email']),
                'phone' => $data['phone'] ?? null,
                'whatsapp' => $data['whatsapp'] ?? $data['phone'] ?? null,
                'status' => 'draft',
            ]);
            $this->audit($user, $applicant, 'admission.registered', ['registration_number' => $applicant->registration_number]);

            return $applicant->fresh();
        });
    }

    public function applicantFor(User $user): Applicant
    {
        $applicant = Applicant::query()->where('email', $user->email)->latest()->first();
        abort_unless($applicant, 404);

        return $applicant;
    }

    public function updateBiodata(Applicant $applicant, array $data, User $actor): Applicant
    {
        return DB::transaction(function () use ($applicant, $data, $actor) {
            $locked = $this->owned($applicant, $actor);
            if (! in_array($locked->status, ['draft', 'submitted'], true)) {
                throw ValidationException::withMessages(['applicant' => 'Biodata tidak dapat diubah pada status ini.']);
            }
            $locked->forceFill(collect($data)->only([
                'name', 'national_id', 'nisn', 'birth_place', 'birth_date', 'gender',
                'phone', 'whatsapp', 'address', 'previous_school', 'graduation_year', 'school_score',
            ])->toArray())->save();

            return $locked->fresh();
        });
    }

    public function setProgramChoices(Applicant $applicant, array $programIds, User $actor): Applicant
    {
        return DB::transaction(function () use ($applicant, $programIds, $actor) {
            $locked = $this->owned($applicant, $actor);
            if (! in_array($locked->status, ['draft', 'submitted'], true)) {
                throw ValidationException::withMessages(['applicant' => 'Pilihan program tidak dapat diubah pada status ini.']);
            }
            $programIds = array_values(array_unique($programIds));
            if ($programIds === [] || count($programIds) > 2) {
                throw ValidationException::withMessages(['program' => 'Pilih 1-2 program studi berbeda.']);
            }

            $locked->programChoices()->delete();
            foreach ($programIds as $index => $programId) {
                ApplicantProgramChoice::query()->create([
                    'applicant_id' => $locked->id, 'study_program_id' => $programId, 'preference' => $index + 1,
                ]);
            }

            return $locked->fresh('programChoices');
        });
    }

    public function uploadDocument(Applicant $applicant, string $kind, string $label, string $path, User $actor): ApplicantDocument
    {
        $locked = $this->owned($applicant, $actor);

        return ApplicantDocument::query()->create([
            'applicant_id' => $locked->id, 'kind' => $kind, 'label' => trim($label), 'file_path' => $path, 'status' => 'pending',
        ]);
    }

    public function submit(Applicant $applicant, User $actor): Applicant
    {
        return DB::transaction(function () use ($applicant, $actor) {
            $locked = $this->owned($applicant, $actor);
            if ($locked->programChoices()->count() < 1) {
                throw ValidationException::withMessages(['program' => 'Minimal satu pilihan program studi.']);
            }
            $this->workflow->transition($locked->fresh(), 'submitted', $actor, 'Berkas diajukan.');
            $this->workflow->transition($locked->fresh(), 'payment_pending', $actor, 'Menunggu pembayaran.');

            return $locked->fresh();
        });
    }

    public function recordPayment(Applicant $applicant, array $data, User $actor): ApplicantPayment
    {
        return DB::transaction(function () use ($applicant, $data, $actor) {
            $locked = $this->owned($applicant, $actor);
            if ($locked->status !== 'payment_pending') {
                throw ValidationException::withMessages(['payment' => 'Pembayaran tidak dapat dicatat pada status ini.']);
            }
            $this->sequences->ensure($locked->university_id, 'applicant_payment', 'PMB-PAY/{year}/{number}');

            return ApplicantPayment::query()->create([
                'applicant_id' => $locked->id,
                'reference_number' => $this->sequences->next($locked->university_id, 'applicant_payment', ['year' => now()->year]),
                'amount' => $data['amount'],
                'method' => $data['method'] ?? 'transfer',
                'proof_path' => $data['proof_path'] ?? null,
                'status' => 'submitted',
            ]);
        });
    }

    public function requestReRegistration(Applicant $applicant, User $actor): Applicant
    {
        return DB::transaction(function () use ($applicant, $actor) {
            $locked = $this->owned($applicant, $actor);
            if ($locked->status !== 'passed') {
                throw ValidationException::withMessages(['applicant' => 'Daftar ulang hanya untuk yang lulus seleksi.']);
            }
            ApplicantReRegistration::query()->firstOrCreate(
                ['applicant_id' => $locked->id],
                ['status' => 'submitted'],
            );
            $this->workflow->transition($locked->fresh(), 're_registration', $actor, 'Mengajukan daftar ulang.');

            return $locked->fresh();
        });
    }

    private function owned(Applicant $applicant, User $actor): Applicant
    {
        $locked = Applicant::query()->lockForUpdate()->findOrFail($applicant->id);
        if (! $actor->hasRole('super_admin') && $locked->email !== $actor->email) {
            throw ValidationException::withMessages(['authorization' => 'Data pendaftaran milik applicant lain.']);
        }

        return $locked;
    }

    private function audit(User $actor, Applicant $applicant, string $event, array $values): void
    {
        AuditLog::query()->create([
            'user_id' => $actor->id, 'event' => $event, 'module' => 'admission',
            'entity_type' => Applicant::class, 'entity_id' => $applicant->id, 'new_values' => $values,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
            'user_agent' => app()->runningInConsole() ? null : request()->userAgent(),
        ]);
    }
}
