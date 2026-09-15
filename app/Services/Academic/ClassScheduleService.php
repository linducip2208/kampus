<?php

namespace App\Services\Academic;

use App\Models\AuditLog;
use App\Models\ClassSchedule;
use App\Models\ClassSection;
use App\Models\Semester;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClassScheduleService
{
    /** @param array{day_of_week:int, starts_at:string, ends_at:string, room?:string|null} $attributes */
    public function create(ClassSection $section, array $attributes, ?User $actor = null): ClassSchedule
    {
        return DB::transaction(function () use ($section, $attributes, $actor) {
            $section = $this->lockSectionAndSemester($section);
            $attributes = $this->validatedAttributes($section, $attributes);

            $schedule = $section->schedules()->create($attributes);
            $this->audit('academic.schedule_created', $schedule, null, $schedule->only($this->auditedFields()), $actor);

            return $schedule->fresh();
        });
    }

    /** @param array{day_of_week:int, starts_at:string, ends_at:string, room?:string|null} $attributes */
    public function update(ClassSchedule $schedule, array $attributes, ?User $actor = null): ClassSchedule
    {
        return DB::transaction(function () use ($schedule, $attributes, $actor) {
            $schedule = ClassSchedule::query()->lockForUpdate()->findOrFail($schedule->id);
            $section = $this->lockSectionAndSemester($schedule->classSection);
            $attributes = $this->validatedAttributes($section, $attributes, $schedule->id);
            $oldValues = $schedule->only($this->auditedFields());

            $schedule->forceFill($attributes)->save();
            $this->audit('academic.schedule_updated', $schedule, $oldValues, $schedule->only($this->auditedFields()), $actor);

            return $schedule->fresh();
        });
    }

    private function lockSectionAndSemester(ClassSection $section): ClassSection
    {
        $section = ClassSection::query()
            ->with('offering')
            ->lockForUpdate()
            ->findOrFail($section->id);

        if (! $section->offering?->semester_id) {
            throw ValidationException::withMessages(['class_section_id' => 'Kelas belum terhubung ke semester.']);
        }

        // Serializes schedule writes in one semester so two concurrent inserts
        // cannot both pass collision validation.
        Semester::query()->lockForUpdate()->findOrFail($section->offering->semester_id);

        return $section;
    }

    /**
     * @param  array{day_of_week:int, starts_at:string, ends_at:string, room?:string|null}  $attributes
     * @return array{day_of_week:int, starts_at:string, ends_at:string, room:string|null}
     */
    private function validatedAttributes(ClassSection $section, array $attributes, ?string $exceptId = null): array
    {
        $day = (int) ($attributes['day_of_week'] ?? 0);
        $start = $this->normalizeTime((string) ($attributes['starts_at'] ?? ''), 'starts_at');
        $end = $this->normalizeTime((string) ($attributes['ends_at'] ?? ''), 'ends_at');
        $room = trim((string) ($attributes['room'] ?? $section->room ?? '')) ?: null;

        if ($day < 1 || $day > 7) {
            throw ValidationException::withMessages(['day_of_week' => 'Hari jadwal harus berada antara Senin dan Minggu.']);
        }
        if ($start >= $end) {
            throw ValidationException::withMessages(['ends_at' => 'Waktu selesai harus setelah waktu mulai.']);
        }

        $conflicts = ClassSchedule::query()
            ->with('classSection.lecturers:id')
            ->whereHas('classSection.offering', fn ($query) => $query->where('semester_id', $section->offering->semester_id))
            ->where('day_of_week', $day)
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->lockForUpdate()
            ->get()
            ->filter(fn (ClassSchedule $candidate) => $this->scheduleTime($candidate->starts_at) < $end
                && $this->scheduleTime($candidate->ends_at) > $start);

        if ($conflicts->contains('class_section_id', $section->id)) {
            throw ValidationException::withMessages(['starts_at' => 'Kelas sudah memiliki jadwal lain yang bertabrakan.']);
        }

        if ($room !== null && $conflicts->contains(fn (ClassSchedule $candidate) => strcasecmp(trim((string) $candidate->room), $room) === 0)) {
            throw ValidationException::withMessages(['room' => "Ruangan {$room} sudah digunakan pada waktu tersebut."]);
        }

        $lecturerIds = $section->lecturers()->pluck('lecturer_profiles.id');
        if ($lecturerIds->isNotEmpty() && $conflicts->contains(
            fn (ClassSchedule $candidate) => $candidate->classSection->lecturers->pluck('id')->intersect($lecturerIds)->isNotEmpty()
        )) {
            throw ValidationException::withMessages(['lecturer' => 'Dosen sudah mengajar kelas lain pada waktu tersebut.']);
        }

        return ['day_of_week' => $day, 'starts_at' => $start, 'ends_at' => $end, 'room' => $room];
    }

    private function normalizeTime(string $value, string $field): string
    {
        foreach (['H:i:s', 'H:i'] as $format) {
            try {
                return CarbonImmutable::createFromFormat($format, $value)->format('H:i:s');
            } catch (\Throwable) {
                // Try the next supported format.
            }
        }

        throw ValidationException::withMessages([$field => 'Format waktu tidak valid.']);
    }

    private function scheduleTime(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('H:i:s');
        }

        return CarbonImmutable::parse((string) $value)->format('H:i:s');
    }

    /** @return array<int, string> */
    private function auditedFields(): array
    {
        return ['class_section_id', 'day_of_week', 'starts_at', 'ends_at', 'room'];
    }

    private function audit(string $event, ClassSchedule $schedule, ?array $oldValues, array $newValues, ?User $actor): void
    {
        AuditLog::create([
            'user_id' => $actor?->id,
            'event' => $event,
            'module' => 'academic',
            'entity_type' => ClassSchedule::class,
            'entity_id' => $schedule->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
            'user_agent' => app()->runningInConsole() ? null : request()->userAgent(),
        ]);
    }
}
