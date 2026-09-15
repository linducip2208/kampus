<?php

namespace App\Services\Finance;

use App\Models\AuditLog;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\User;
use App\Services\NumberSequenceService;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccountingService
{
    public function __construct(private readonly NumberSequenceService $sequences) {}

    public function createAccount(string $universityId, array $data): ChartOfAccount
    {
        if (! in_array($data['type'], ['asset', 'liability', 'equity', 'revenue', 'expense'], true)) {
            throw ValidationException::withMessages(['type' => 'Tipe akun tidak valid.']);
        }

        return ChartOfAccount::query()->create([
            'university_id' => $universityId,
            'code' => trim($data['code']),
            'name' => trim($data['name']),
            'type' => $data['type'],
            'parent_id' => $data['parent_id'] ?? null,
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<int, array{account_id: string, debit: int|string, credit: int|string, memo?: string}>  $lines
     */
    public function postJournal(string $universityId, string $description, array $lines, User $actor, ?string $entryDate = null): JournalEntry
    {
        if (count($lines) < 2) {
            throw ValidationException::withMessages(['lines' => 'Jurnal minimal dua baris.']);
        }

        return DB::transaction(function () use ($universityId, $description, $lines, $actor, $entryDate) {
            $debitTotal = 0;
            $creditTotal = 0;
            foreach ($lines as $line) {
                $debitTotal += Money::toMinorUnits($line['debit'] ?? '0.00');
                $creditTotal += Money::toMinorUnits($line['credit'] ?? '0.00');
                if (! ChartOfAccount::query()->where('university_id', $universityId)->whereKey($line['account_id'])->exists()) {
                    throw ValidationException::withMessages(['lines' => 'Akun tidak ditemukan dalam universitas.']);
                }
            }
            if ($debitTotal !== $creditTotal || $debitTotal <= 0) {
                throw ValidationException::withMessages(['lines' => 'Jurnal harus balance dan lebih dari nol.']);
            }

            $this->sequences->ensure($universityId, 'journal', 'JRNL/{year}/{number}');
            $entry = JournalEntry::query()->create([
                'university_id' => $universityId,
                'entry_number' => $this->sequences->next($universityId, 'journal', ['year' => now()->year]),
                'entry_date' => $entryDate ?? now()->toDateString(),
                'description' => trim($description),
                'status' => 'posted',
                'created_by' => $actor->id,
            ]);
            foreach ($lines as $line) {
                JournalLine::query()->create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $line['account_id'],
                    'debit' => $line['debit'] ?? '0.00',
                    'credit' => $line['credit'] ?? '0.00',
                    'memo' => $line['memo'] ?? null,
                ]);
            }

            AuditLog::query()->create([
                'user_id' => $actor->id, 'event' => 'accounting.journal_posted', 'module' => 'accounting',
                'entity_type' => JournalEntry::class, 'entity_id' => $entry->id,
                'new_values' => ['entry_number' => $entry->entry_number, 'total' => Money::fromMinorUnits($debitTotal)],
                'ip_address' => app()->runningInConsole() ? null : request()->ip(),
                'user_agent' => app()->runningInConsole() ? null : request()->userAgent(),
            ]);

            return $entry->load('lines');
        });
    }
}
