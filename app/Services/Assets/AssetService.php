<?php

namespace App\Services\Assets;

use App\Models\Asset;
use App\Models\AssetLoan;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssetService
{
    public function register(string $universityId, array $data): Asset
    {
        return Asset::query()->create([
            'university_id' => $universityId,
            'name' => trim($data['name']),
            'code' => trim($data['code']),
            'category' => $data['category'] ?? 'equipment',
            'location' => $data['location'] ?? null,
            'condition' => $data['condition'] ?? 'good',
            'purchase_date' => $data['purchase_date'] ?? null,
            'purchase_price' => $data['purchase_price'] ?? 0,
            'status' => 'available',
        ]);
    }

    public function loan(Asset $asset, User $borrower, User $actor, ?string $dueAt = null, ?string $purpose = null): AssetLoan
    {
        return DB::transaction(function () use ($asset, $borrower, $actor, $dueAt, $purpose) {
            $locked = Asset::query()->lockForUpdate()->findOrFail($asset->id);
            if ($locked->status !== 'available') {
                throw ValidationException::withMessages(['asset' => 'Aset tidak tersedia.']);
            }
            $loan = AssetLoan::query()->create([
                'asset_id' => $locked->id,
                'borrowed_by' => $borrower->id,
                'purpose' => $purpose,
                'borrowed_at' => now(),
                'due_at' => $dueAt,
                'status' => 'borrowed',
            ]);
            $locked->forceFill(['status' => 'borrowed'])->save();
            $this->audit($actor, $loan, 'asset.borrowed', ['asset_id' => $locked->id]);

            return $loan->fresh();
        });
    }

    public function returnAsset(AssetLoan $loan, User $actor): AssetLoan
    {
        return DB::transaction(function () use ($loan, $actor) {
            $locked = AssetLoan::query()->lockForUpdate()->findOrFail($loan->id);
            if ($locked->status !== 'borrowed') {
                throw ValidationException::withMessages(['loan' => 'Peminjaman sudah dikembalikan.']);
            }
            $locked->forceFill(['status' => 'returned', 'returned_at' => now()])->save();
            Asset::query()->whereKey($locked->asset_id)->update(['status' => 'available']);
            $this->audit($actor, $locked, 'asset.returned', ['status' => 'returned']);

            return $locked->fresh();
        });
    }

    private function audit(User $actor, AssetLoan $loan, string $event, array $values): void
    {
        AuditLog::query()->create([
            'user_id' => $actor->id, 'event' => $event, 'module' => 'assets',
            'entity_type' => AssetLoan::class, 'entity_id' => $loan->id, 'new_values' => $values,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
            'user_agent' => app()->runningInConsole() ? null : request()->userAgent(),
        ]);
    }
}
