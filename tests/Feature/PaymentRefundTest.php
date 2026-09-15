<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\PaymentRefund;
use App\Models\StudentInvoice;
use App\Models\User;
use App\Services\Finance\PaymentRefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class PaymentRefundTest extends TestCase
{
    use RefreshDatabase;

    public function test_refund_uses_ledger_and_reconciles_invoice_without_deleting_allocation(): void
    {
        $this->seed();
        $payment = Payment::query()->where('payment_number', 'PAY/2026/09/000001')->firstOrFail();
        $allocation = $payment->allocations()->firstOrFail();
        $invoice = StudentInvoice::query()->findOrFail($allocation->student_invoice_id);
        $finance = User::query()->where('email', 'finance@kampus.test')->firstOrFail();

        $first = app(PaymentRefundService::class)->refund(
            $payment,
            [$allocation->id => '1250000.25'],
            'Koreksi kelebihan alokasi pembayaran.',
            $finance,
        );

        $this->assertSame('1250000.25', $first->amount);
        $this->assertSame('3249999.75', $invoice->fresh()->paid_amount);
        $this->assertSame('partial', $invoice->fresh()->status);
        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertDatabaseHas('payment_allocations', [
            'id' => $allocation->id,
            'amount' => '4500000.00',
        ]);
        $this->assertDatabaseHas('payment_refund_allocations', [
            'payment_refund_id' => $first->id,
            'payment_allocation_id' => $allocation->id,
            'amount' => '1250000.25',
        ]);

        $second = app(PaymentRefundService::class)->refund(
            $payment->fresh(),
            [$allocation->id => '3249999.75'],
            'Pengembalian sisa pembayaran.',
            $finance,
        );

        $this->assertSame('issued', $invoice->fresh()->status);
        $this->assertSame('0.00', $invoice->fresh()->paid_amount);
        $this->assertSame('refunded', $payment->fresh()->status);
        $this->assertSame('3249999.75', $second->amount);
        $this->assertDatabaseCount('payment_refunds', 2);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'finance.payment_refunded',
            'entity_type' => PaymentRefund::class,
        ]);
    }

    public function test_refund_cannot_exceed_net_payment_allocation(): void
    {
        $this->seed();
        $payment = Payment::query()->where('payment_number', 'PAY/2026/09/000001')->firstOrFail();
        $allocation = $payment->allocations()->firstOrFail();
        $finance = User::query()->where('email', 'finance@kampus.test')->firstOrFail();

        $this->expectException(InvalidArgumentException::class);
        app(PaymentRefundService::class)->refund(
            $payment,
            [$allocation->id => '4500000.01'],
            'Nominal tidak valid.',
            $finance,
        );
    }

    public function test_completed_refund_ledger_cannot_be_updated_or_deleted(): void
    {
        $this->seed();
        $payment = Payment::query()->where('payment_number', 'PAY/2026/09/000001')->firstOrFail();
        $allocation = $payment->allocations()->firstOrFail();
        $finance = User::query()->where('email', 'finance@kampus.test')->firstOrFail();
        $refund = app(PaymentRefundService::class)->refund(
            $payment,
            [$allocation->id => '100000.00'],
            'Refund immutable.',
            $finance,
        );

        try {
            $refund->update(['reason' => 'Diubah tanpa izin.']);
            $this->fail('Refund ledger seharusnya immutable.');
        } catch (\LogicException $exception) {
            $this->assertSame('Financial refund ledger tidak dapat diubah.', $exception->getMessage());
        }

        $this->expectException(\LogicException::class);
        $refund->delete();
    }

    public function test_demo_invoice_paid_amount_is_backed_by_allocation_ledger(): void
    {
        $this->seed();
        $invoice = StudentInvoice::query()->firstOrFail();

        $this->assertSame('4500000.00', $invoice->paid_amount);
        $this->assertEqualsWithDelta(4500000.00, (float) $invoice->allocations()->sum('amount'), 0.001);
    }
}
