<?php

namespace Tests\Feature;

use App\Models\StudentInvoice;
use App\Models\University;
use App\Models\User;
use App\Services\Finance\FinanceAdjustmentService;
use App\Services\Integrations\OpsFoundationService;
use App\Services\Payments\PaymentGatewayService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FinanceGatewayTest extends TestCase
{
    use RefreshDatabase;

    public function test_discount_penalty_waive_reconciliation(): void
    {
        $this->seed();
        $finance = User::query()->where('email', 'finance@kampus.test')->firstOrFail();
        $university = University::query()->firstOrFail();
        $invoice = StudentInvoice::query()->firstOrFail();

        $discount = app(FinanceAdjustmentService::class)->grantDiscount($invoice, 'early_bird', '500000.00', 'Pelunasan awal.', $finance);
        $this->assertSame('7000000.00', $invoice->fresh()->total_amount);

        $penalty = app(FinanceAdjustmentService::class)->applyPenalty($invoice->fresh(), 'late', '100000.00', 'Terlambat.', $finance);
        $this->assertSame('7100000.00', $invoice->fresh()->total_amount);

        $waived = app(FinanceAdjustmentService::class)->waivePenalty($penalty, $finance);
        $this->assertSame('waived', $waived->status);
        $this->assertSame('7000000.00', $invoice->fresh()->total_amount);

        $summary = app(FinanceAdjustmentService::class)->reconciliationSummary($university->id);
        $this->assertArrayHasKey('outstanding_amount', $summary);
        $this->assertDatabaseHas('audit_logs', ['event' => 'finance.discount_granted']);
    }

    public function test_gateway_create_idempotent_and_callback_pays_invoice(): void
    {
        $this->seed();
        $finance = User::query()->where('email', 'finance@kampus.test')->firstOrFail();
        $university = University::query()->firstOrFail();
        $invoice = StudentInvoice::query()->firstOrFail();

        $endpoint = app(OpsFoundationService::class)->registerEndpoint($university->id, [
            'name' => 'Midtrans Sandbox', 'kind' => 'payment_midtrans', 'base_url' => 'fake://midtrans.local',
            'settings' => ['mode' => 'sandbox'],
        ]);

        $first = app(PaymentGatewayService::class)->createForInvoice($invoice, $endpoint, $finance, 'idem-key-001');
        $second = app(PaymentGatewayService::class)->createForInvoice($invoice->fresh(), $endpoint, $finance, 'idem-key-001');
        $this->assertSame($first->id, $second->id);
        $this->assertSame('pending', $first->status);

        $paid = app(PaymentGatewayService::class)->handleCallback($endpoint, [
            'order_id' => $first->external_id, 'status_code' => '200', 'gross_amount' => '4500000.00',
        ], 'sandbox-signature', $finance);
        $this->assertSame('paid', $paid->status);
        $this->assertNotNull($paid->payment_id);

        // Callback replay stays idempotent.
        $replay = app(PaymentGatewayService::class)->handleCallback($endpoint, [
            'order_id' => $first->external_id, 'status_code' => '200', 'gross_amount' => '4500000.00',
        ], 'sandbox-signature', $finance);
        $this->assertSame($paid->id, $replay->id);

        try {
            app(PaymentGatewayService::class)->handleCallback($endpoint, ['order_id' => 'unknown'], 'bad', $finance);
            $this->fail('Callback invalid seharusnya ditolak.');
        } catch (ValidationException|ModelNotFoundException $e) {
            $this->assertTrue(true);
        }
    }

    public function test_finance_and_integration_pages_render(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@kampus.test')->firstOrFail();
        $this->actingAs($admin)->get(route('admin.finance.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.integrations.index'))->assertOk();
    }
}
