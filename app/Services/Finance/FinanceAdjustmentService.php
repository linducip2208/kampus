<?php

namespace App\Services\Finance;

use App\Models\AuditLog;
use App\Models\InvoiceDiscount;
use App\Models\InvoicePenalty;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\StudentInvoice;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinanceAdjustmentService
{
    public function grantDiscount(StudentInvoice $invoice, string $kind, string $amount, ?string $reason, User $actor, ?string $awardId = null): InvoiceDiscount
    {
        return DB::transaction(function () use ($invoice, $kind, $amount, $reason, $actor, $awardId) {
            $locked = StudentInvoice::query()->lockForUpdate()->findOrFail($invoice->id);
            if (! in_array($kind, ['scholarship', 'early_bird', 'staff', 'sibling', 'other'], true)) {
                throw ValidationException::withMessages(['kind' => 'Jenis diskon tidak valid.']);
            }
            $amountMinor = Money::toMinorUnits($amount);
            if ($amountMinor <= 0) {
                throw ValidationException::withMessages(['amount' => 'Nominal diskon harus lebih dari nol.']);
            }
            if ($amountMinor > Money::toMinorUnits($locked->total_amount)) {
                throw ValidationException::withMessages(['amount' => 'Diskon melebihi total tagihan.']);
            }

            $discount = InvoiceDiscount::query()->create([
                'student_invoice_id' => $locked->id,
                'scholarship_award_id' => $awardId,
                'kind' => $kind,
                'amount' => Money::fromMinorUnits($amountMinor),
                'reason' => $reason,
                'granted_by' => $actor->id,
                'granted_at' => now(),
            ]);
            $newTotalMinor = Money::toMinorUnits($locked->total_amount) - $amountMinor;
            $locked->forceFill([
                'total_amount' => Money::fromMinorUnits($newTotalMinor),
                'status' => $this->resolveStatus($newTotalMinor, Money::toMinorUnits($locked->paid_amount)),
            ])->save();
            $this->audit($actor, $locked, 'finance.discount_granted', ['amount' => $discount->amount, 'kind' => $kind]);

            return $discount->fresh();
        });
    }

    public function applyPenalty(StudentInvoice $invoice, string $kind, string $amount, ?string $reason, User $actor): InvoicePenalty
    {
        return DB::transaction(function () use ($invoice, $kind, $amount, $reason, $actor) {
            $locked = StudentInvoice::query()->lockForUpdate()->findOrFail($invoice->id);
            $amountMinor = Money::toMinorUnits($amount);
            if ($amountMinor <= 0) {
                throw ValidationException::withMessages(['amount' => 'Nominal denda harus lebih dari nol.']);
            }

            $penalty = InvoicePenalty::query()->create([
                'student_invoice_id' => $locked->id,
                'kind' => $kind,
                'amount' => Money::fromMinorUnits($amountMinor),
                'reason' => $reason,
                'status' => 'applied',
                'applied_by' => $actor->id,
                'applied_at' => now(),
            ]);
            $newTotalMinor = Money::toMinorUnits($locked->total_amount) + $amountMinor;
            $locked->forceFill([
                'total_amount' => Money::fromMinorUnits($newTotalMinor),
                'status' => $this->resolveStatus($newTotalMinor, Money::toMinorUnits($locked->paid_amount)),
            ])->save();
            $this->audit($actor, $locked, 'finance.penalty_applied', ['amount' => $penalty->amount, 'kind' => $kind]);

            return $penalty->fresh();
        });
    }

    public function waivePenalty(InvoicePenalty $penalty, User $actor): InvoicePenalty
    {
        return DB::transaction(function () use ($penalty, $actor) {
            $locked = InvoicePenalty::query()->with('invoice')->lockForUpdate()->findOrFail($penalty->id);
            if ($locked->status !== 'applied') {
                throw ValidationException::withMessages(['penalty' => 'Denda sudah diproses.']);
            }
            $locked->forceFill(['status' => 'waived', 'waived_by' => $actor->id, 'waived_at' => now()])->save();

            $invoice = StudentInvoice::query()->lockForUpdate()->findOrFail($locked->student_invoice_id);
            $newTotalMinor = Money::toMinorUnits($invoice->total_amount) - Money::toMinorUnits($locked->amount);
            $invoice->forceFill([
                'total_amount' => Money::fromMinorUnits(max(0, $newTotalMinor)),
                'status' => $this->resolveStatus(max(0, $newTotalMinor), Money::toMinorUnits($invoice->paid_amount)),
            ])->save();
            $this->audit($actor, $invoice, 'finance.penalty_waived', ['amount' => $locked->amount]);

            return $locked->fresh();
        });
    }

    /**
     * @return array{invoices_outstanding: int, outstanding_amount: string, unallocated_payments: int, pending_gateway: int}
     */
    public function reconciliationSummary(string $universityId): array
    {
        $invoiceIds = StudentInvoice::query()->whereHas('enrollment.studyProgram.department.faculty', fn ($query) => $query->where('university_id', $universityId))->pluck('id');
        $outstanding = StudentInvoice::query()->whereIn('id', $invoiceIds)->whereIn('status', ['issued', 'partial'])->get();
        $outstandingMinor = $outstanding->sum(fn (StudentInvoice $invoice): int => Money::toMinorUnits($invoice->outstanding_amount));
        $unallocated = Payment::query()->whereHas('enrollment.studyProgram.department.faculty', fn ($query) => $query->where('university_id', $universityId))
            ->whereDoesntHave('allocations')->count();
        $pendingGateway = PaymentTransaction::query()->where('university_id', $universityId)->where('status', 'pending')->count();

        return [
            'invoices_outstanding' => $outstanding->count(),
            'outstanding_amount' => Money::fromMinorUnits($outstandingMinor),
            'unallocated_payments' => $unallocated,
            'pending_gateway' => $pendingGateway,
        ];
    }

    private function resolveStatus(int $totalMinor, int $paidMinor): string
    {
        if ($paidMinor <= 0) {
            return 'issued';
        }

        return $paidMinor >= $totalMinor ? 'paid' : 'partial';
    }

    private function audit(User $actor, StudentInvoice $invoice, string $event, array $values): void
    {
        AuditLog::query()->create([
            'user_id' => $actor->id, 'event' => $event, 'module' => 'finance',
            'entity_type' => StudentInvoice::class, 'entity_id' => $invoice->id, 'new_values' => $values,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
            'user_agent' => app()->runningInConsole() ? null : request()->userAgent(),
        ]);
    }
}
