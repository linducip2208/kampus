<?php

namespace App\Services\Finance;

use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\PaymentRefund;
use App\Models\PaymentRefundAllocation;
use App\Models\StudentInvoice;
use App\Models\User;
use App\Services\NumberSequenceService;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PaymentRefundService
{
    public function __construct(private readonly NumberSequenceService $sequences) {}

    /**
     * @param  array<string, int|string>  $allocationAmounts
     */
    public function refund(Payment $payment, array $allocationAmounts, string $reason, User $actor): PaymentRefund
    {
        if ($allocationAmounts === []) {
            throw new InvalidArgumentException('Minimal satu alokasi refund wajib dipilih.');
        }

        return DB::transaction(function () use ($payment, $allocationAmounts, $reason, $actor) {
            $payment = Payment::query()
                ->with('enrollment.studyProgram.department.faculty')
                ->lockForUpdate()
                ->findOrFail($payment->id);
            if ($payment->status !== 'paid') {
                throw new InvalidArgumentException('Hanya payment paid yang dapat direfund.');
            }

            $requested = collect($allocationAmounts)->sortKeys();
            $refundLines = [];
            $refundMinor = 0;

            foreach ($requested as $allocationId => $amount) {
                $allocation = PaymentAllocation::query()
                    ->where('payment_id', $payment->id)
                    ->lockForUpdate()
                    ->findOrFail($allocationId);
                $amountMinor = Money::toMinorUnits($amount);
                $alreadyRefundedMinor = $allocation->refundAllocations()
                    ->whereHas('refund', fn ($query) => $query->where('status', 'completed'))
                    ->get()
                    ->sum(fn (PaymentRefundAllocation $item): int => Money::toMinorUnits($item->amount));
                $availableMinor = Money::toMinorUnits($allocation->amount) - $alreadyRefundedMinor;

                if ($amountMinor <= 0 || $amountMinor > $availableMinor) {
                    throw new InvalidArgumentException('Nominal refund melebihi saldo alokasi pembayaran.');
                }

                $invoice = StudentInvoice::query()->lockForUpdate()->findOrFail($allocation->student_invoice_id);
                if ($invoice->student_enrollment_id !== $payment->student_enrollment_id) {
                    throw new InvalidArgumentException('Refund lintas enrollment tidak diizinkan.');
                }

                $paidMinor = Money::toMinorUnits($invoice->paid_amount);
                if ($amountMinor > $paidMinor) {
                    throw new InvalidArgumentException('Nominal refund melebihi saldo terbayar invoice.');
                }

                $refundLines[] = compact('allocation', 'invoice', 'amountMinor');
                $refundMinor += $amountMinor;
            }

            $universityId = $payment->enrollment->studyProgram->department->faculty->university_id;
            $this->sequences->ensure($universityId, 'payment_refund', 'RFD/{year}/{number}');
            $refund = PaymentRefund::query()->create([
                'payment_id' => $payment->id,
                'refund_number' => $this->sequences->next($universityId, 'payment_refund', ['year' => now()->year]),
                'amount' => Money::fromMinorUnits($refundMinor),
                'reason' => trim($reason),
                'status' => 'completed',
                'processed_by' => $actor->id,
                'completed_at' => now(),
            ]);

            foreach ($refundLines as $line) {
                PaymentRefundAllocation::query()->create([
                    'payment_refund_id' => $refund->id,
                    'payment_allocation_id' => $line['allocation']->id,
                    'amount' => Money::fromMinorUnits($line['amountMinor']),
                ]);

                $invoice = $line['invoice'];
                $newPaidMinor = Money::toMinorUnits($invoice->paid_amount) - $line['amountMinor'];
                $invoice->forceFill([
                    'paid_amount' => Money::fromMinorUnits($newPaidMinor),
                    'status' => $newPaidMinor === 0
                        ? 'issued'
                        : ($newPaidMinor >= Money::toMinorUnits($invoice->total_amount) ? 'paid' : 'partial'),
                ])->save();
            }

            $completedRefundMinor = $payment->refunds()
                ->where('status', 'completed')
                ->get()
                ->sum(fn (PaymentRefund $item): int => Money::toMinorUnits($item->amount));
            if ($completedRefundMinor >= Money::toMinorUnits($payment->amount)) {
                $payment->forceFill(['status' => 'refunded'])->save();
            }

            AuditLog::query()->create([
                'user_id' => $actor->id,
                'event' => 'finance.payment_refunded',
                'module' => 'finance',
                'entity_type' => PaymentRefund::class,
                'entity_id' => $refund->id,
                'new_values' => [
                    'refund_number' => $refund->refund_number,
                    'amount' => $refund->amount,
                    'reason' => $refund->reason,
                    'payment_status' => $payment->status,
                ],
                'ip_address' => app()->runningInConsole() ? null : request()->ip(),
                'user_agent' => app()->runningInConsole() ? null : request()->userAgent(),
            ]);

            return $refund->load(['allocations.paymentAllocation.invoice', 'processedBy']);
        });
    }
}
