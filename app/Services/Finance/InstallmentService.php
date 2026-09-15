<?php

namespace App\Services\Finance;

use App\Models\AuditLog;
use App\Models\InvoiceInstallment;
use App\Models\StudentInvoice;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InstallmentService
{
    public function createPlan(StudentInvoice $invoice, int $count, string $firstDueOn, User $actor): array
    {
        return DB::transaction(function () use ($invoice, $count, $firstDueOn, $actor) {
            $locked = StudentInvoice::query()->lockForUpdate()->findOrFail($invoice->id);
            if ($count < 2 || $count > 12) {
                throw ValidationException::withMessages(['count' => 'Jumlah cicilan 2-12.']);
            }
            if ($locked->installments()->exists()) {
                throw ValidationException::withMessages(['plan' => 'Rencana cicilan sudah tersedia.']);
            }

            $totalMinor = Money::toMinorUnits($locked->total_amount);
            $base = intdiv($totalMinor, $count);
            $remainder = $totalMinor - ($base * $count);
            $plans = [];
            for ($i = 1; $i <= $count; $i++) {
                $amountMinor = $base + ($i === $count ? $remainder : 0);
                $plans[] = InvoiceInstallment::query()->create([
                    'student_invoice_id' => $locked->id,
                    'sequence' => $i,
                    'amount' => Money::fromMinorUnits($amountMinor),
                    'due_on' => now()->parse($firstDueOn)->addMonths($i - 1)->toDateString(),
                    'status' => 'pending',
                ]);
            }

            AuditLog::query()->create([
                'user_id' => $actor->id, 'event' => 'finance.installment_planned', 'module' => 'finance',
                'entity_type' => StudentInvoice::class, 'entity_id' => $locked->id,
                'new_values' => ['count' => $count],
                'ip_address' => app()->runningInConsole() ? null : request()->ip(),
                'user_agent' => app()->runningInConsole() ? null : request()->userAgent(),
            ]);

            return $plans;
        });
    }

    public function markPaid(InvoiceInstallment $installment, User $actor): InvoiceInstallment
    {
        return DB::transaction(function () use ($installment) {
            $locked = InvoiceInstallment::query()->lockForUpdate()->findOrFail($installment->id);
            if ($locked->status === 'paid') {
                throw ValidationException::withMessages(['installment' => 'Cicilan sudah lunas.']);
            }
            $locked->forceFill(['status' => 'paid', 'paid_at' => now()])->save();

            return $locked->fresh();
        });
    }
}
