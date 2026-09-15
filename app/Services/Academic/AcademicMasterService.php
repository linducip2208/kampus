<?php

namespace App\Services\Academic;

use App\Models\AcademicCalendar;
use App\Models\AcademicRule;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseEquivalence;
use App\Models\Holiday;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcademicMasterService
{
    public function createCalendarEvent(string $universityId, array $data, User $actor): AcademicCalendar
    {
        if (trim($data['title'] ?? '') === '') {
            throw ValidationException::withMessages(['title' => 'Judul agenda wajib diisi.']);
        }
        if (empty($data['starts_on']) || empty($data['ends_on']) || $data['ends_on'] < $data['starts_on']) {
            throw ValidationException::withMessages(['ends_on' => 'Tanggal selesai harus setelah tanggal mulai.']);
        }

        $event = AcademicCalendar::query()->create([
            'university_id' => $universityId,
            'academic_year_id' => $data['academic_year_id'] ?? null,
            'title' => trim($data['title']),
            'kind' => $data['kind'] ?? 'academic',
            'starts_on' => $data['starts_on'],
            'ends_on' => $data['ends_on'],
            'description' => $data['description'] ?? null,
        ]);
        $this->audit($actor, $event, 'academic.calendar_created', ['title' => $event->title]);

        return $event->fresh();
    }

    public function createHoliday(string $universityId, array $data, User $actor): Holiday
    {
        if (Holiday::query()->where('university_id', $universityId)->whereDate('date', $data['date'])->exists()) {
            throw ValidationException::withMessages(['date' => 'Tanggal libur sudah terdaftar.']);
        }

        $holiday = Holiday::query()->create([
            'university_id' => $universityId,
            'name' => trim($data['name']),
            'date' => $data['date'],
            'is_national' => (bool) ($data['is_national'] ?? false),
            'description' => $data['description'] ?? null,
        ]);
        $this->audit($actor, $holiday, 'academic.holiday_created', ['date' => $holiday->date->toDateString()]);

        return $holiday->fresh();
    }

    public function createCategory(string $universityId, array $data): CourseCategory
    {
        if (CourseCategory::query()->where('university_id', $universityId)->where('code', trim($data['code']))->exists()) {
            throw ValidationException::withMessages(['code' => 'Kode kategori sudah dipakai.']);
        }

        return CourseCategory::query()->create([
            'university_id' => $universityId,
            'name' => trim($data['name']),
            'code' => trim($data['code']),
            'description' => $data['description'] ?? null,
        ]);
    }

    public function classifyCourse(Course $course, ?string $categoryId, string $type, User $actor): Course
    {
        return DB::transaction(function () use ($course, $categoryId, $type, $actor) {
            if (! in_array($type, ['wajib', 'pilihan', 'umum', 'praktikum', 'skripsi'], true)) {
                throw ValidationException::withMessages(['course_type' => 'Tipe mata kuliah tidak valid.']);
            }
            $locked = Course::query()->lockForUpdate()->findOrFail($course->id);
            $locked->forceFill(['course_category_id' => $categoryId, 'course_type' => $type])->save();
            $this->audit($actor, $locked, 'academic.course_classified', ['type' => $type]);

            return $locked->fresh();
        });
    }

    public function addEquivalence(Course $old, Course $new, ?string $note, User $actor): CourseEquivalence
    {
        return DB::transaction(function () use ($old, $new, $note, $actor) {
            if ($old->id === $new->id) {
                throw ValidationException::withMessages(['equivalence' => 'Mata kuliah lama dan baru tidak boleh sama.']);
            }
            if ($old->university_id !== $new->university_id) {
                throw ValidationException::withMessages(['equivalence' => 'Ekuivalensi lintas universitas tidak diizinkan.']);
            }
            if (CourseEquivalence::query()->where('old_course_id', $old->id)->where('new_course_id', $new->id)->exists()) {
                throw ValidationException::withMessages(['equivalence' => 'Ekuivalensi sudah terdaftar.']);
            }

            $equivalence = CourseEquivalence::query()->create([
                'old_course_id' => $old->id, 'new_course_id' => $new->id, 'note' => $note,
            ]);
            $this->audit($actor, $equivalence, 'academic.equivalence_created', ['old' => $old->code, 'new' => $new->code]);

            return $equivalence->fresh();
        });
    }

    public function setRule(string $universityId, string $key, mixed $value, ?string $description, User $actor): AcademicRule
    {
        $rule = AcademicRule::query()->updateOrCreate(
            ['university_id' => $universityId, 'key' => trim($key)],
            ['value' => $value, 'description' => $description],
        );
        $this->audit($actor, $rule, 'academic.rule_set', ['key' => $rule->key]);

        return $rule->fresh();
    }

    public function isHoliday(string $universityId, string $date): bool
    {
        return Holiday::query()->where('university_id', $universityId)->whereDate('date', $date)->exists();
    }

    private function audit(User $actor, object $entity, string $event, array $values): void
    {
        AuditLog::query()->create([
            'user_id' => $actor->id, 'event' => $event, 'module' => 'academic',
            'entity_type' => $entity::class, 'entity_id' => $entity->id, 'new_values' => $values,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
            'user_agent' => app()->runningInConsole() ? null : request()->userAgent(),
        ]);
    }
}
