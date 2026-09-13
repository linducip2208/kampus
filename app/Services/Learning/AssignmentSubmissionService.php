<?php

namespace App\Services\Learning;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\StudentEnrollment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignmentSubmissionService
{
    public function submit(Assignment $assignment, StudentEnrollment $enrollment, array $payload): AssignmentSubmission
    {
        return DB::transaction(function () use ($assignment, $enrollment, $payload) {
            $locked = Assignment::query()->lockForUpdate()->findOrFail($assignment->id);
            if ($locked->status !== 'open') throw ValidationException::withMessages(['assignment' => 'Assignment belum dibuka atau sudah ditutup.']);
            if ($locked->opens_at?->isFuture()) throw ValidationException::withMessages(['assignment' => 'Assignment belum memasuki waktu mulai.']);
            $isLate = $locked->due_at?->isPast() ?? false;
            if ($isLate && ! $locked->allow_late) throw ValidationException::withMessages(['assignment' => 'Batas waktu assignment sudah berakhir.']);
            $registered = $locked->classSection->studyPlanItems()->whereHas('studyPlan', fn ($query) => $query->where('student_enrollment_id', $enrollment->id))->exists();
            if (! $registered) throw ValidationException::withMessages(['enrollment' => 'Mahasiswa tidak terdaftar pada kelas assignment ini.']);
            $submission = AssignmentSubmission::query()->where('assignment_id', $locked->id)->where('student_enrollment_id', $enrollment->id)->lockForUpdate()->first();
            $data = ['assignment_id' => $locked->id, 'student_enrollment_id' => $enrollment->id, 'file_path' => $payload['file_path'] ?? $submission?->file_path, 'answer_text' => $payload['answer_text'] ?? $submission?->answer_text, 'submitted_at' => now(), 'is_late' => $isLate, 'status' => 'submitted'];
            if ($submission) { $submission->forceFill($data)->save(); return $submission->fresh(); }
            return AssignmentSubmission::create($data);
        });
    }
}
