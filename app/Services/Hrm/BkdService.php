<?php

namespace App\Services\Hrm;

use App\Models\AuditLog;
use App\Models\LecturerProfile;
use App\Models\LecturerWorkload;
use App\Models\User;
use App\Models\WorkloadActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BkdService
{
    public const CATEGORIES = ['teaching', 'research', 'service', 'supporting'];

    public const MIN_SKS = 12;

    public function openWorkload(LecturerProfile $lecturer, string $semesterId): LecturerWorkload
    {
        return LecturerWorkload::query()->firstOrCreate(
            ['lecturer_profile_id' => $lecturer->id, 'semester_id' => $semesterId],
            ['target_sks' => self::MIN_SKS, 'status' => 'draft'],
        );
    }

    public function addActivity(LecturerWorkload $workload, array $data, User $actor): WorkloadActivity
    {
        return DB::transaction(function () use ($workload, $data, $actor) {
            $locked = LecturerWorkload::query()->lockForUpdate()->findOrFail($workload->id);
            if ($locked->status !== 'draft') {
                throw ValidationException::withMessages(['workload' => 'Aktivitas hanya dapat ditambah pada status draft.']);
            }
            if (! in_array($data['category'], self::CATEGORIES, true)) {
                throw ValidationException::withMessages(['category' => 'Kategori BKD tidak valid.']);
            }
            if ((float) ($data['sks'] ?? 0) <= 0) {
                throw ValidationException::withMessages(['sks' => 'Beban SKS harus lebih dari nol.']);
            }

            $activity = WorkloadActivity::query()->create([
                'lecturer_workload_id' => $locked->id,
                'category' => $data['category'],
                'title' => trim($data['title']),
                'sks' => $data['sks'],
                'evidence_path' => $data['evidence_path'] ?? null,
                'description' => $data['description'] ?? null,
            ]);
            $this->audit($actor, $locked, 'bkd.activity_added', ['category' => $activity->category, 'sks' => $activity->sks]);

            return $activity->fresh();
        });
    }

    public function submit(LecturerWorkload $workload, User $actor): LecturerWorkload
    {
        return DB::transaction(function () use ($workload, $actor) {
            $locked = LecturerWorkload::query()->with('activities')->lockForUpdate()->findOrFail($workload->id);
            if ($locked->status !== 'draft') {
                throw ValidationException::withMessages(['workload' => 'BKD sudah diajukan.']);
            }
            if ($locked->activities->isEmpty()) {
                throw ValidationException::withMessages(['workload' => 'Minimal satu aktivitas wajib diisi.']);
            }
            $locked->forceFill(['status' => 'submitted', 'submitted_at' => now()])->save();
            $this->audit($actor, $locked, 'bkd.submitted', ['status' => 'submitted']);

            return $locked->fresh();
        });
    }

    public function approve(LecturerWorkload $workload, User $actor): LecturerWorkload
    {
        return DB::transaction(function () use ($workload, $actor) {
            $locked = LecturerWorkload::query()->with('activities')->lockForUpdate()->findOrFail($workload->id);
            if ($locked->status !== 'submitted') {
                throw ValidationException::withMessages(['workload' => 'BKD belum diajukan.']);
            }
            if ($this->totalSks($locked) < self::MIN_SKS) {
                throw ValidationException::withMessages(['workload' => 'Total SKS belum memenuhi minimum '.self::MIN_SKS.'.']);
            }
            $locked->forceFill(['status' => 'approved', 'decided_by' => $actor->id, 'decided_at' => now()])->save();
            $this->audit($actor, $locked, 'bkd.approved', ['total_sks' => $this->totalSks($locked)]);

            return $locked->fresh();
        });
    }

    public function reject(LecturerWorkload $workload, User $actor, string $reason): LecturerWorkload
    {
        return DB::transaction(function () use ($workload, $actor, $reason) {
            $locked = LecturerWorkload::query()->lockForUpdate()->findOrFail($workload->id);
            if ($locked->status !== 'submitted') {
                throw ValidationException::withMessages(['workload' => 'BKD belum diajukan.']);
            }
            $locked->forceFill(['status' => 'rejected', 'decided_by' => $actor->id, 'decided_at' => now(), 'reject_reason' => trim($reason)])->save();
            $this->audit($actor, $locked, 'bkd.rejected', ['status' => 'rejected']);

            return $locked->fresh();
        });
    }

    /**
     * @return array{total_sks: string, by_category: array<string, string>, target_sks: string, fulfilled: bool}
     */
    public function summary(LecturerWorkload $workload): array
    {
        $workload->loadMissing('activities');
        $byCategory = [];
        foreach (self::CATEGORIES as $category) {
            $sum = $workload->activities->where('category', $category)->sum(fn ($item) => (float) $item->sks);
            $byCategory[$category] = number_format($sum, 2, '.', '');
        }
        $total = array_sum(array_map('floatval', $byCategory));

        return [
            'total_sks' => number_format($total, 2, '.', ''),
            'by_category' => $byCategory,
            'target_sks' => $workload->target_sks,
            'fulfilled' => $total >= (float) $workload->target_sks,
        ];
    }

    private function totalSks(LecturerWorkload $workload): float
    {
        return (float) $workload->activities->sum(fn ($item) => (float) $item->sks);
    }

    private function audit(User $actor, LecturerWorkload $workload, string $event, array $values): void
    {
        AuditLog::query()->create([
            'user_id' => $actor->id, 'event' => $event, 'module' => 'bkd',
            'entity_type' => LecturerWorkload::class, 'entity_id' => $workload->id, 'new_values' => $values,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
            'user_agent' => app()->runningInConsole() ? null : request()->userAgent(),
        ]);
    }
}
