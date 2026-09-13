<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Payment;
use App\Models\StudentInvoice;
use App\Models\User;
use App\Services\Finance\PaymentAllocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PermissionAndFinanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_finance_role_cannot_open_academic_course_resource(): void
    {
        $this->seed();
        $finance = User::where('email', 'finance@kampus.test')->firstOrFail();

        $this->assertFalse($finance->can('viewAny', Course::class));
        $this->assertTrue(User::where('email', 'admin@kampus.test')->firstOrFail()->can('viewAny', Course::class));
    }

    public function test_payment_is_allocated_and_invoice_balance_is_reconciled(): void
    {
        $this->seed();
        $invoice = StudentInvoice::query()->firstOrFail();
        $payment = Payment::create([
            'student_enrollment_id' => $invoice->student_enrollment_id,
            'payment_number' => 'PAY/'.Str::upper(Str::random(12)),
            'amount' => 3000000,
            'method' => 'transfer',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        app(PaymentAllocationService::class)->allocate($payment, $invoice, 3000000);

        $this->assertDatabaseHas('payment_allocations', ['payment_id' => $payment->id, 'amount' => 3000000]);
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame(0.0, $invoice->fresh()->outstanding_amount);
    }
}
