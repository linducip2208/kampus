<?php

namespace App\Services\Academic;

use App\Models\AuditLog;
use App\Models\ClassSection;
use App\Models\StudentComponentScore;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GradingSchemeService
{
    /** @param array<int, array{name:string, weight:numeric}> $components */
    public function configure(ClassSection $section, array $components, User $actor): ClassSection
    {
        return DB::transaction(function () use ($section, $components, $actor) {
            $section = ClassSection::query()->with(['lecturers.employee', 'gradingComponents'])->lockForUpdate()->findOrFail($section->id);
            $this->authorize($section, $actor);
            $normalized = collect($components)->map(fn (array $component) => [
                'name' => trim((string) ($component['name'] ?? '')),
                'weight' => round((float) ($component['weight'] ?? 0), 2),
            ])->filter(fn (array $component) => $component['name'] !== '')->values();

            if ($normalized->isEmpty() || $normalized->contains(fn (array $component) => $component['weight'] <= 0)) {
                throw ValidationException::withMessages(['components' => 'Minimal satu komponen dengan bobot lebih dari 0 wajib tersedia.']);
            }
            if ($normalized->pluck('name')->map(fn ($name) => mb_strtolower($name))->duplicates()->isNotEmpty()) {
                throw ValidationException::withMessages(['components' => 'Nama komponen nilai tidak boleh duplikat.']);
            }
            if (abs((float) $normalized->sum('weight') - 100.0) > 0.001) {
                throw ValidationException::withMessages(['components' => 'Total bobot komponen harus tepat 100%.']);
            }

            $componentIds = $section->gradingComponents->pluck('id');
            if ($componentIds->isNotEmpty() && StudentComponentScore::query()->whereIn('grading_component_id', $componentIds)->exists()) {
                throw ValidationException::withMessages(['components' => 'Skema tidak dapat diganti setelah nilai komponen mulai diisi.']);
            }

            $old = $section->gradingComponents->map->only(['name', 'weight'])->values()->all();
            $section->gradingComponents()->delete();
            foreach ($normalized as $component) {
                $section->gradingComponents()->create($component);
            }
            AuditLog::create([
                'user_id' => $actor->id, 'event' => 'grade.scheme_configured', 'module' => 'grades',
                'entity_type' => ClassSection::class, 'entity_id' => $section->id,
                'old_values' => ['components' => $old], 'new_values' => ['components' => $normalized->all()],
                'ip_address' => app()->runningInConsole() ? null : request()->ip(),
                'user_agent' => app()->runningInConsole() ? null : request()->userAgent(),
            ]);

            return $section->fresh('gradingComponents');
        });
    }

    private function authorize(ClassSection $section, User $actor): void
    {
        if ($actor->hasRole('super_admin')) {
            return;
        }
        $lecturerId = $actor->employee?->lecturerProfile?->id;
        if (! $lecturerId || ! $section->lecturers->contains('id', $lecturerId)) {
            throw ValidationException::withMessages(['authorization' => 'Hanya dosen pengampu yang dapat mengatur skema nilai kelas ini.']);
        }
    }
}
