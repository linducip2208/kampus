<?php

namespace App\Services\Finance;

use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\StudentInvoice;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PaymentAllocationService
{
    public function allocate(Payment $payment, StudentInvoice $invoice, float $amount): PaymentAllocation
    {
        return DB::transaction(function () use ($payment, $invoice, $amount) {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $invoice = StudentInvoice::query()->lockForUpdate()->findOrFail($invoice->id);
            if ($amount <= 0 || $amount > (float) $invoice->outstanding_amount) throw new InvalidArgumentException('Nominal alokasi melebihi sisa invoice.');
            if ($payment->student_enrollment_id !== $invoice->student_enrollment_id) throw new InvalidArgumentException('Pembayaran dan invoice bukan milik enrollment yang sama.');
            $allocated = (float) $payment->allocations()->sum('amount');
            if ($allocated + $amount > (float) $payment->amount) throw new InvalidArgumentException('Nominal alokasi melebihi payment.');
            $allocation = PaymentAllocation::create(['payment_id' => $payment->id, 'student_invoice_id' => $invoice->id, 'amount' => $amount]);
            $invoice->paid_amount = (float) $invoice->paid_amount + $amount;
            $invoice->status = $invoice->paid_amount >= $invoice->total_amount ? 'paid' : 'partial';
            $invoice->save();
            return $allocation;
        });
    }
}
