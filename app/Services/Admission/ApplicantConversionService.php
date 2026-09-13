<?php

namespace App\Services\Admission;

use App\Models\Applicant;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\StudentEnrollment;
use App\Models\StudentProfile;
use App\Models\StudentStatusHistory;
use App\Models\User;
use App\Services\NumberSequenceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ApplicantConversionService
{
    public function __construct(private readonly NumberSequenceService $sequences) {}

    public function convert(Applicant $applicant, ?User $actor = null): StudentEnrollment
    {
        return DB::transaction(function () use ($applicant, $actor) {
            $locked = Applicant::query()->with(['admissionPath', 'programChoices.studyProgram'])->lockForUpdate()->findOrFail($applicant->id);
            if ($locked->converted_student_profile_id) return StudentEnrollment::query()->where('student_profile_id', $locked->converted_student_profile_id)->latest()->firstOrFail();
            if ($locked->status !== 're_registration' || $locked->reRegistration?->status !== 'approved') {
                throw ValidationException::withMessages(['applicant' => 'Applicant harus berstatus re-registration dan registrasi ulang harus approved.']);
            }
            $choice = $locked->programChoices->sortBy('preference')->first();
            if (! $choice?->studyProgram) throw ValidationException::withMessages(['program' => 'Applicant belum memiliki pilihan program studi.']);
            $user = User::query()->where('email', $locked->email)->first();
            if ($user?->studentProfile) throw ValidationException::withMessages(['email' => 'Email applicant sudah terhubung ke mahasiswa.']);
            $user ??= User::create(['name' => $locked->name, 'email' => $locked->email, 'password' => Hash::make(Str::random(32))]);
            $role = Role::query()->where('name', 'mahasiswa')->first();
            if ($role && ! $user->roles()->whereKey($role->id)->exists()) $user->roles()->attach($role->id, ['university_id' => $locked->university_id]);
            $sequenceKey = 'nim:'.$choice->studyProgram->code;
            $this->sequences->ensure($locked->university_id, $sequenceKey, '{year}-{program}-{number}');
            $studentNumber = $this->sequences->next($locked->university_id, $sequenceKey, ['year' => now()->year, 'program' => $choice->studyProgram->code]);
            $student = StudentProfile::create(['user_id' => $user->id, 'student_number' => $studentNumber, 'full_name' => $locked->name, 'email' => $locked->email, 'phone' => $locked->phone, 'national_id' => $locked->national_id, 'gender' => $locked->gender, 'birth_date' => $locked->birth_date, 'birth_place' => $locked->birth_place, 'address' => $locked->address]);
            $curriculum = $choice->studyProgram->curricula()->where('is_active', true)->latest('year')->first();
            $enrollment = StudentEnrollment::create(['student_profile_id' => $student->id, 'study_program_id' => $choice->study_program_id, 'curriculum_id' => $curriculum?->id, 'cohort' => now()->year, 'status' => 'active', 'admission_type' => $locked->admissionPath?->code ?? 'regular', 'enrolled_on' => now()->toDateString()]);
            StudentStatusHistory::create(['student_enrollment_id' => $enrollment->id, 'to_status' => 'active', 'reason' => 'Konversi applicant menjadi mahasiswa', 'changed_by' => $actor?->id, 'changed_at' => now()]);
            $locked->forceFill(['converted_student_profile_id' => $student->id, 'status' => 'student_created'])->save();
            AuditLog::create(['user_id' => $actor?->id, 'event' => 'admission.converted', 'module' => 'admission', 'entity_type' => Applicant::class, 'entity_id' => $locked->id, 'new_values' => ['student_profile_id' => $student->id, 'enrollment_id' => $enrollment->id], 'ip_address' => request()?->ip(), 'user_agent' => request()?->userAgent()]);
            return $enrollment->fresh(['studentProfile', 'studyProgram']);
        });
    }
}
