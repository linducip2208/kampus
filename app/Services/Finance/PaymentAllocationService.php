<?php

namespace App\Services\Finance;

use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\StudentInvoice;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PaymentAllocationService
{
    public function allocate(Payment $payment, StudentInvoice $invoice, int|float|string $amount, ?User $actor = null): PaymentAllocation
    {
        return DB::transaction(function () use ($payment, $invoice, $amount, $actor) {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $invoice = StudentInvoice::query()->lockForUpdate()->findOrFail($invoice->id);

            if ($payment->status !== 'paid') {
                throw new InvalidArgumentException('Payment harus berstatus paid sebelum dialokasikan.');
            }

            $allocationMinor = Money::toMinorUnits($amount);
            $outstandingMinor = Money::toMinorUnits($invoice->outstanding_amount);
            $paymentMinor = Money::toMinorUnits($payment->amount);

            if ($allocationMinor <= 0 || $allocationMinor > $outstandingMinor) {
                throw new InvalidArgumentException('Nominal alokasi melebihi sisa invoice.');
            }

            if ($payment->student_enrollment_id !== $invoice->student_enrollment_id) {
                throw new InvalidArgumentException('Pembayaran dan invoice bukan milik enrollment yang sama.');
            }

            $allocatedMinor = $payment->allocations
                ->reduce(fn (int $total, PaymentAllocation $allocation): int => $total + Money::toMinorUnits($allocation->amount), 0);

            if ($allocatedMinor + $allocationMinor > $paymentMinor) {
                throw new InvalidArgumentException('Nominal alokasi melebihi payment.');
            }

            $allocation = PaymentAllocation::query()->firstOrNew([
                'payment_id' => $payment->id,
                'student_invoice_id' => $invoice->id,
            ]);
            $existingMinor = $allocation->exists ? Money::toMinorUnits($allocation->amount) : 0;
            $oldAllocation = Money::fromMinorUnits($existingMinor);
            $allocation->amount = Money::fromMinorUnits($existingMinor + $allocationMinor);
            $allocation->save();

            $oldPaid = (string) $invoice->paid_amount;
            $paidMinor = Money::toMinorUnits($invoice->paid_amount) + $allocationMinor;
            $invoice->paid_amount = Money::fromMinorUnits($paidMinor);
            $invoice->status = $paidMinor >= Money::toMinorUnits($invoice->total_amount) ? 'paid' : 'partial';
            $invoice->save();

            AuditLog::create([
                'user_id' => $actor?->id,
                'event' => 'finance.payment_allocated',
                'module' => 'finance',
                'entity_type' => PaymentAllocation::class,
                'entity_id' => $allocation->id,
                'old_values' => [
                    'allocation_amount' => $oldAllocation,
                    'invoice_paid_amount' => $oldPaid,
                ],
                'new_values' => [
                    'allocation_amount' => $allocation->amount,
                    'invoice_paid_amount' => $invoice->paid_amount,
                    'invoice_status' => $invoice->status,
                ],
                'ip_address' => app()->runningInConsole() ? null : request()->ip(),
                'user_agent' => app()->runningInConsole() ? null : request()->userAgent(),
            ]);

            return $allocation;
        });
    }
}
