<?php

namespace App\Services\Learning;

use App\Models\AuditLog;
use App\Models\ClassSection;
use App\Models\CourseAnnouncement;
use App\Models\Discussion;
use App\Models\DiscussionPost;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LearningCommunicationService
{
    public function announce(ClassSection $section, array $data, User $actor): CourseAnnouncement
    {
        return DB::transaction(function () use ($section, $data, $actor) {
            $section = $this->lockedSection($section);
            $this->authorizeLecturer($section, $actor);

            $announcement = CourseAnnouncement::query()->create([
                'class_section_id' => $section->id,
                'author_id' => $actor->id,
                'title' => $data['title'],
                'body' => $data['body'],
                'is_pinned' => $data['is_pinned'] ?? false,
                'published_at' => ($data['publish_now'] ?? false) ? now() : null,
            ]);
            $this->audit($actor, $announcement, 'lms.announcement_created', [
                'published_at' => $announcement->published_at?->toIso8601String(),
            ]);

            return $announcement;
        });
    }

    public function createDiscussion(ClassSection $section, array $data, User $actor): Discussion
    {
        return DB::transaction(function () use ($section, $data, $actor) {
            $section = $this->lockedSection($section);
            $this->authorizeParticipant($section, $actor);

            $discussion = Discussion::query()->create([
                'class_section_id' => $section->id,
                'created_by' => $actor->id,
                'title' => $data['title'],
                'body' => $data['body'],
                'status' => 'open',
            ]);
            $this->audit($actor, $discussion, 'lms.discussion_created', ['status' => 'open']);

            return $discussion;
        });
    }

    public function reply(Discussion $discussion, string $body, User $actor, ?DiscussionPost $parent = null): DiscussionPost
    {
        return DB::transaction(function () use ($discussion, $body, $actor, $parent) {
            $discussion = Discussion::query()->with('classSection.lecturers.employee')->lockForUpdate()->findOrFail($discussion->id);
            $this->authorizeParticipant($discussion->classSection, $actor);
            if ($discussion->status !== 'open') {
                throw ValidationException::withMessages(['discussion' => 'Diskusi sudah dikunci.']);
            }
            if ($parent && ($parent->discussion_id !== $discussion->id || $parent->trashed())) {
                throw ValidationException::withMessages(['parent_id' => 'Balasan induk tidak valid.']);
            }

            $post = DiscussionPost::query()->create([
                'discussion_id' => $discussion->id,
                'user_id' => $actor->id,
                'parent_id' => $parent?->id,
                'body' => trim($body),
            ]);
            $discussion->touch();
            $this->audit($actor, $post, 'lms.discussion_replied', ['discussion_id' => $discussion->id]);

            return $post->load('author');
        });
    }

    public function setLocked(Discussion $discussion, bool $locked, User $actor): Discussion
    {
        return DB::transaction(function () use ($discussion, $locked, $actor) {
            $discussion = Discussion::query()->with('classSection.lecturers.employee')->lockForUpdate()->findOrFail($discussion->id);
            $this->authorizeLecturer($discussion->classSection, $actor);
            $oldStatus = $discussion->status;
            $discussion->forceFill(['status' => $locked ? 'locked' : 'open'])->save();
            $this->audit($actor, $discussion, 'lms.discussion_status_changed', [
                'old_status' => $oldStatus,
                'status' => $discussion->status,
            ]);

            return $discussion->fresh();
        });
    }

    public function authorizeParticipant(ClassSection $section, User $actor): void
    {
        if ($this->isLecturer($section, $actor) || $this->isEnrolledStudent($section, $actor)) {
            return;
        }

        throw ValidationException::withMessages(['authorization' => 'Anda tidak terdaftar pada kelas diskusi ini.']);
    }

    private function lockedSection(ClassSection $section): ClassSection
    {
        return ClassSection::query()->with('lecturers.employee')->lockForUpdate()->findOrFail($section->id);
    }

    private function authorizeLecturer(ClassSection $section, User $actor): void
    {
        if (! $this->isLecturer($section, $actor)) {
            throw ValidationException::withMessages(['authorization' => 'Hanya dosen pengampu yang dapat melakukan tindakan ini.']);
        }
    }

    private function isLecturer(ClassSection $section, User $actor): bool
    {
        $lecturerId = $actor->employee?->lecturerProfile?->id;

        return (bool) $lecturerId && $section->lecturers->contains('id', $lecturerId);
    }

    private function isEnrolledStudent(ClassSection $section, User $actor): bool
    {
        $enrollmentIds = $actor->studentProfile?->enrollments()->pluck('student_enrollments.id');
        if (! $enrollmentIds || $enrollmentIds->isEmpty()) {
            return false;
        }

        return DB::table('study_plan_items')
            ->join('study_plans', 'study_plans.id', '=', 'study_plan_items.study_plan_id')
            ->where('study_plan_items.class_section_id', $section->id)
            ->whereIn('study_plans.student_enrollment_id', $enrollmentIds)
            ->whereIn('study_plans.status', ['approved', 'finalized', 'locked'])
            ->exists();
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
