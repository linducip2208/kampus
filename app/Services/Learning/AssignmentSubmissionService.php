<?php

namespace App\Services\Learning;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\AuditLog;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignmentSubmissionService
{
    public function submit(Assignment $assignment, StudentEnrollment $enrollment, array $payload, ?User $actor = null): AssignmentSubmission
    {
        return DB::transaction(function () use ($assignment, $enrollment, $payload, $actor) {
            $locked = Assignment::query()->with('classSection')->lockForUpdate()->findOrFail($assignment->id);
            if ($locked->status !== 'open') {
                throw ValidationException::withMessages(['assignment' => 'Assignment belum dibuka atau sudah ditutup.']);
            }
            if ($locked->opens_at?->isFuture()) {
                throw ValidationException::withMessages(['assignment' => 'Assignment belum memasuki waktu mulai.']);
            }
            $isLate = $locked->due_at?->isPast() ?? false;
            if ($isLate && ! $locked->allow_late) {
                throw ValidationException::withMessages(['assignment' => 'Batas waktu assignment sudah berakhir.']);
            }

            $registered = $locked->classSection->studyPlanItems()
                ->whereHas('studyPlan', fn ($query) => $query
                    ->where('student_enrollment_id', $enrollment->id)
                    ->whereIn('status', ['approved', 'finalized', 'locked']))
                ->exists();
            if (! $registered) {
                throw ValidationException::withMessages(['enrollment' => 'Mahasiswa tidak terdaftar aktif pada kelas assignment ini.']);
            }

            $submission = AssignmentSubmission::query()
                ->where('assignment_id', $locked->id)
                ->where('student_enrollment_id', $enrollment->id)
                ->lockForUpdate()
                ->first();

            if ($submission?->status === 'graded') {
                throw ValidationException::withMessages(['submission' => 'Tugas yang sudah dinilai tidak dapat dikirim ulang.']);
            }
            if (($submission?->attempts_count ?? 0) >= $locked->max_attempts) {
                throw ValidationException::withMessages(['submission' => 'Batas percobaan pengumpulan tugas sudah tercapai.']);
            }

            $data = [
                'assignment_id' => $locked->id,
                'student_enrollment_id' => $enrollment->id,
                'file_path' => $payload['file_path'] ?? $submission?->file_path,
                'answer_text' => $payload['answer_text'] ?? $submission?->answer_text,
                'submitted_at' => now(),
                'is_late' => $isLate,
                'attempts_count' => ($submission?->attempts_count ?? 0) + 1,
                'status' => 'submitted',
            ];

            if ($submission) {
                $submission->forceFill($data)->save();
            } else {
                $submission = AssignmentSubmission::query()->create($data);
            }

            AuditLog::query()->create([
                'user_id' => $actor?->id,
                'event' => 'assignment.submitted',
                'module' => 'learning',
                'entity_type' => AssignmentSubmission::class,
                'entity_id' => $submission->id,
                'new_values' => ['assignment_id' => $locked->id, 'attempt' => $submission->attempts_count, 'is_late' => $isLate],
                'ip_address' => app()->runningInConsole() ? null : request()->ip(),
                'user_agent' => app()->runningInConsole() ? null : request()->userAgent(),
            ]);

            return $submission->fresh();
        });
    }
}
