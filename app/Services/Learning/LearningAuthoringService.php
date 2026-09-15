<?php

namespace App\Services\Learning;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\AuditLog;
use App\Models\ClassSection;
use App\Models\CourseContent;
use App\Models\CourseModule;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LearningAuthoringService
{
    public function createModule(ClassSection $section, array $data, User $actor): CourseModule
    {
        return DB::transaction(function () use ($section, $data, $actor) {
            $section = $this->section($section, $actor);
            $position = ((int) CourseModule::withTrashed()->where('class_section_id', $section->id)->lockForUpdate()->max('position')) + 1;
            $module = CourseModule::query()->create([
                'class_section_id' => $section->id,
                'title' => $data['title'],
                'position' => $position,
                'status' => $data['status'],
            ]);
            $this->audit($actor, $module, 'lms.module_created', ['status' => $module->status]);

            return $module;
        });
    }

    public function createContent(CourseModule $module, array $data, User $actor): CourseContent
    {
        return DB::transaction(function () use ($module, $data, $actor) {
            $module = CourseModule::query()->with('classSection.lecturers.employee')->lockForUpdate()->findOrFail($module->id);
            $this->authorizeLecturer($module->classSection, $actor);
            if ($data['type'] === 'url' && blank($data['external_url'] ?? null)) {
                throw ValidationException::withMessages(['external_url' => 'URL wajib untuk konten bertipe tautan.']);
            }
            if ($data['type'] === 'text' && blank($data['body'] ?? null)) {
                throw ValidationException::withMessages(['body' => 'Isi materi wajib untuk konten teks.']);
            }

            $position = ((int) CourseContent::withTrashed()->where('course_module_id', $module->id)->lockForUpdate()->max('position')) + 1;
            $content = CourseContent::query()->create([
                'course_module_id' => $module->id,
                'type' => $data['type'],
                'title' => $data['title'],
                'body' => $data['body'] ?? null,
                'external_url' => $data['external_url'] ?? null,
                'position' => $position,
                'published_at' => ($data['publish_now'] ?? false) ? now() : null,
            ]);
            $this->audit($actor, $content, 'lms.content_created', ['published_at' => $content->published_at?->toIso8601String()]);

            return $content;
        });
    }

    public function createAssignment(ClassSection $section, array $data, User $actor): Assignment
    {
        return DB::transaction(function () use ($section, $data, $actor) {
            $section = $this->section($section, $actor);
            if (! empty($data['opens_at']) && ! empty($data['due_at']) && $data['due_at'] <= $data['opens_at']) {
                throw ValidationException::withMessages(['due_at' => 'Tenggat harus setelah waktu mulai.']);
            }

            $assignment = Assignment::query()->create([
                'class_section_id' => $section->id,
                'title' => $data['title'],
                'instructions' => $data['instructions'] ?? null,
                'status' => $data['status'],
                'opens_at' => $data['opens_at'] ?? null,
                'due_at' => $data['due_at'] ?? null,
                'allow_late' => $data['allow_late'] ?? false,
                'max_attempts' => $data['max_attempts'],
                'max_score' => $data['max_score'],
            ]);
            $this->audit($actor, $assignment, 'assignment.created', ['status' => $assignment->status]);

            return $assignment;
        });
    }

    public function gradeSubmission(AssignmentSubmission $submission, string|int|float $score, ?string $feedback, User $actor): AssignmentSubmission
    {
        return DB::transaction(function () use ($submission, $score, $feedback, $actor) {
            $submission = AssignmentSubmission::query()->with('assignment.classSection.lecturers.employee')->lockForUpdate()->findOrFail($submission->id);
            $this->authorizeLecturer($submission->assignment->classSection, $actor);
            $numericScore = (float) $score;
            if ($numericScore < 0 || $numericScore > (float) $submission->assignment->max_score) {
                throw ValidationException::withMessages(['score' => 'Nilai harus berada dalam rentang assignment.']);
            }

            $submission->forceFill([
                'score' => number_format($numericScore, 2, '.', ''),
                'feedback' => $feedback,
                'status' => 'graded',
                'graded_by' => $actor->id,
                'graded_at' => now(),
            ])->save();
            $this->audit($actor, $submission, 'assignment.graded', ['score' => $submission->score]);

            return $submission->fresh();
        });
    }

    private function section(ClassSection $section, User $actor): ClassSection
    {
        $section = ClassSection::query()->with('lecturers.employee')->lockForUpdate()->findOrFail($section->id);
        $this->authorizeLecturer($section, $actor);

        return $section;
    }

    private function authorizeLecturer(ClassSection $section, User $actor): void
    {
        $lecturerId = $actor->employee?->lecturerProfile?->id;
        if (! $lecturerId || ! $section->lecturers->contains('id', $lecturerId)) {
            throw ValidationException::withMessages(['authorization' => 'Hanya dosen pengampu yang dapat mengelola pembelajaran kelas ini.']);
        }
    }

    private function audit(User $actor, object $entity, string $event, array $values): void
    {
        AuditLog::query()->create([
            'user_id' => $actor->id,
            'event' => $event,
            'module' => 'learning',
            'entity_type' => $entity::class,
            'entity_id' => $entity->id,
            'new_values' => $values,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
            'user_agent' => app()->runningInConsole() ? null : request()->userAgent(),
        ]);
    }
}
