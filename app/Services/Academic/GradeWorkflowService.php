<?php

namespace App\Services\Academic;

use App\Models\AuditLog;
use App\Models\GradeRevisionRequest;
use App\Models\GradeScale;
use App\Models\GradingComponent;
use App\Models\StudentComponentScore;
use App\Models\StudentGrade;
use App\Models\StudyPlanItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GradeWorkflowService
{
    public function saveComponentScore(StudyPlanItem $item, GradingComponent $component, string|int|float $score, User $actor, ?string $feedback = null): StudentComponentScore
    {
        return DB::transaction(function () use ($item, $component, $score, $actor, $feedback) {
            $item = $this->lockedItem($item);
            $this->authorizeLecturer($item, $actor);
            if ($component->class_section_id !== $item->class_section_id) {
                throw ValidationException::withMessages(['component' => 'Komponen nilai tidak berasal dari kelas mahasiswa.']);
            }

            $numericScore = (float) $score;
            if ($numericScore < 0 || $numericScore > 100) {
                throw ValidationException::withMessages(['score' => 'Nilai komponen harus antara 0 dan 100.']);
            }

            $grade = $item->grade()->lockForUpdate()->first();
            if ($grade && $grade->status !== 'draft') {
                throw ValidationException::withMessages(['grade' => 'Komponen hanya dapat diubah saat nilai berstatus draft.']);
            }

            $component = GradingComponent::query()->lockForUpdate()->findOrFail($component->id);
            $componentScore = StudentComponentScore::query()->updateOrCreate(
                ['study_plan_item_id' => $item->id, 'grading_component_id' => $component->id],
                ['score' => number_format($numericScore, 2, '.', ''), 'graded_by' => $actor->id, 'feedback' => $feedback]
            );
            $this->audit('grade.component_saved', $componentScore, $actor, null, ['score' => $componentScore->score]);

            return $componentScore->fresh();
        });
    }

    public function submit(StudyPlanItem $item, User $actor): StudentGrade
    {
        return DB::transaction(function () use ($item, $actor) {
            $item = $this->lockedItem($item);
            $this->authorizeLecturer($item, $actor);
            $components = GradingComponent::query()->where('class_section_id', $item->class_section_id)->lockForUpdate()->get();
            if ($components->isEmpty() || abs((float) $components->sum('weight') - 100.0) > 0.001) {
                throw ValidationException::withMessages(['components' => 'Bobot komponen nilai harus lengkap dan berjumlah tepat 100%.']);
            }

            $scores = StudentComponentScore::query()->where('study_plan_item_id', $item->id)->whereIn('grading_component_id', $components->pluck('id'))->lockForUpdate()->get()->keyBy('grading_component_id');
            if ($scores->count() !== $components->count()) {
                throw ValidationException::withMessages(['scores' => 'Semua komponen nilai mahasiswa harus diisi sebelum submit.']);
            }

            $finalScore = round($components->sum(fn ($component) => (float) $scores[$component->id]->score * (float) $component->weight / 100), 2);
            $scale = $this->scaleFor($item, $finalScore);
            $grade = $item->grade()->lockForUpdate()->first() ?? new StudentGrade(['study_plan_item_id' => $item->id]);
            if ($grade->exists && $grade->status !== 'draft') {
                throw ValidationException::withMessages(['grade' => 'Nilai yang sudah disubmit tidak dapat disubmit ulang.']);
            }
            $grade->forceFill([
                'final_score' => $finalScore,
                'grade_scale_id' => $scale->id,
                'status' => 'submitted',
                'submitted_by' => $actor->id,
                'submitted_at' => now(),
            ])->save();
            $this->audit('grade.submitted', $grade, $actor, ['status' => 'draft'], ['status' => 'submitted', 'final_score' => $finalScore]);

            return $grade->fresh('gradeScale');
        });
    }

    public function approve(StudentGrade $grade, User $actor): StudentGrade
    {
        return $this->transition($grade, 'submitted', 'approved', $actor, ['approved_by' => $actor->id, 'approved_at' => now()]);
    }

    public function publish(StudentGrade $grade, User $actor): StudentGrade
    {
        return $this->transition($grade, 'approved', 'published', $actor, ['published_by' => $actor->id, 'published_at' => now()]);
    }

    public function lock(StudentGrade $grade, User $actor): StudentGrade
    {
        return $this->transition($grade, 'published', 'locked', $actor, ['locked_at' => now()]);
    }

    public function requestRevision(StudentGrade $grade, string|int|float $newScore, string $reason, User $actor): GradeRevisionRequest
    {
        return DB::transaction(function () use ($grade, $newScore, $reason, $actor) {
            $grade = StudentGrade::query()->with('studyPlanItem.classSection.lecturers.employee')->lockForUpdate()->findOrFail($grade->id);
            $this->authorizeLecturer($grade->studyPlanItem, $actor);
            if ($grade->status !== 'locked') {
                throw ValidationException::withMessages(['grade' => 'Revisi khusus digunakan untuk nilai yang sudah dikunci.']);
            }
            $score = (float) $newScore;
            if ($score < 0 || $score > 100 || blank($reason)) {
                throw ValidationException::withMessages(['revision' => 'Nilai baru harus 0–100 dan alasan wajib diisi.']);
            }
            if ($grade->revisionRequests()->where('status', 'pending')->lockForUpdate()->exists()) {
                throw ValidationException::withMessages(['revision' => 'Masih ada permintaan revisi yang menunggu persetujuan.']);
            }

            $request = $grade->revisionRequests()->create([
                'old_score' => $grade->final_score,
                'new_score' => number_format($score, 2, '.', ''),
                'reason' => $reason,
                'status' => 'pending',
                'requested_by' => $actor->id,
            ]);
            $this->audit('grade.revision_requested', $request, $actor, ['score' => $grade->final_score], ['score' => $score, 'reason' => $reason]);

            return $request->fresh();
        });
    }

    public function approveRevision(GradeRevisionRequest $request, User $actor): StudentGrade
    {
        return DB::transaction(function () use ($request, $actor) {
            $this->authorizeApprover($actor);
            $request = GradeRevisionRequest::query()->with('grade.studyPlanItem')->lockForUpdate()->findOrFail($request->id);
            if ($request->status !== 'pending') {
                throw ValidationException::withMessages(['revision' => 'Permintaan revisi sudah diproses.']);
            }
            $grade = StudentGrade::query()->lockForUpdate()->findOrFail($request->student_grade_id);
            if ($grade->status !== 'locked') {
                throw ValidationException::withMessages(['grade' => 'Nilai tidak lagi berada pada status locked.']);
            }
            $scale = $this->scaleFor($request->grade->studyPlanItem, (float) $request->new_score);
            $old = ['final_score' => $grade->final_score, 'grade_scale_id' => $grade->grade_scale_id];
            $grade->forceFill(['final_score' => $request->new_score, 'grade_scale_id' => $scale->id])->save();
            $request->forceFill(['status' => 'approved', 'approved_by' => $actor->id, 'approved_at' => now()])->save();
            $this->audit('grade.revision_approved', $grade, $actor, $old, ['final_score' => $grade->final_score, 'grade_scale_id' => $scale->id]);

            return $grade->fresh('gradeScale');
        });
    }

    private function transition(StudentGrade $grade, string $from, string $to, User $actor, array $attributes): StudentGrade
    {
        return DB::transaction(function () use ($grade, $from, $to, $actor, $attributes) {
            $this->authorizeApprover($actor);
            $grade = StudentGrade::query()->lockForUpdate()->findOrFail($grade->id);
            if ($grade->status !== $from) {
                throw ValidationException::withMessages(['status' => "Nilai harus berstatus {$from} sebelum {$to}."]);
            }
            $grade->forceFill(['status' => $to] + $attributes)->save();
            $this->audit("grade.{$to}", $grade, $actor, ['status' => $from], ['status' => $to]);

            return $grade->fresh();
        });
    }

    private function lockedItem(StudyPlanItem $item): StudyPlanItem
    {
        return StudyPlanItem::query()->with(['classSection.lecturers.employee', 'classSection.offering.course'])->lockForUpdate()->findOrFail($item->id);
    }

    private function scaleFor(StudyPlanItem $item, float $score): GradeScale
    {
        $universityId = $item->classSection->offering->course->university_id;

        return GradeScale::query()->where('university_id', $universityId)
            ->where('minimum_score', '<=', $score)->where('maximum_score', '>=', $score)
            ->orderByDesc('grade_point')->firstOrFail();
    }

    private function authorizeLecturer(StudyPlanItem $item, User $actor): void
    {
        if ($actor->hasRole('super_admin')) {
            return;
        }
        $lecturerId = $actor->employee?->lecturerProfile?->id;
        if (! $lecturerId || ! $item->classSection->lecturers->contains('id', $lecturerId)) {
            throw ValidationException::withMessages(['authorization' => 'Hanya dosen pengampu yang dapat mengelola nilai kelas ini.']);
        }
    }

    private function authorizeApprover(User $actor): void
    {
        if (! $actor->hasRole(['super_admin', 'baak', 'rektor'])) {
            throw ValidationException::withMessages(['authorization' => 'Akun ini tidak berwenang menyetujui atau memublikasikan nilai.']);
        }
    }

    private function audit(string $event, object $entity, User $actor, ?array $oldValues, array $newValues): void
    {
        AuditLog::create([
            'user_id' => $actor->id, 'event' => $event, 'module' => 'grades',
            'entity_type' => $entity::class, 'entity_id' => $entity->id,
            'old_values' => $oldValues, 'new_values' => $newValues,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
            'user_agent' => app()->runningInConsole() ? null : request()->userAgent(),
        ]);
    }
}
