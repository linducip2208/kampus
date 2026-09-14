<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Semester;
use App\Models\StudentEnrollment;
use App\Models\StudentInvoice;
use App\Models\User;
use App\Services\Finance\PaymentAllocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use InvalidArgumentException;
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
            'amount' => '3000000.00',
            'method' => 'transfer',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        app(PaymentAllocationService::class)->allocate($payment, $invoice, '3000000.00');

        $this->assertDatabaseHas('payment_allocations', ['payment_id' => $payment->id, 'amount' => '3000000.00']);
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['event' => 'finance.payment_allocated', 'entity_type' => PaymentAllocation::class]);
        $this->assertSame('0.00', $invoice->fresh()->outstanding_amount);
    }

    public function test_pending_payment_cannot_be_allocated(): void
    {
        $this->seed();
        $invoice = StudentInvoice::query()->firstOrFail();
        $payment = Payment::create([
            'student_enrollment_id' => $invoice->student_enrollment_id,
            'payment_number' => 'PAY/'.Str::upper(Str::random(12)),
            'amount' => '1000000.00',
            'method' => 'transfer',
            'status' => 'pending',
        ]);

        $this->expectException(InvalidArgumentException::class);
        app(PaymentAllocationService::class)->allocate($payment, $invoice, '1000000.00');
    }

    public function test_allocation_rejects_amount_above_payment(): void
    {
        $this->seed();
        $invoice = StudentInvoice::query()->firstOrFail();
        $payment = Payment::create([
            'student_enrollment_id' => $invoice->student_enrollment_id,
            'payment_number' => 'PAY/'.Str::upper(Str::random(12)),
            'amount' => '1000000.00',
            'method' => 'transfer',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        app(PaymentAllocationService::class)->allocate($payment, $invoice, '1000000.00');

        $this->expectException(InvalidArgumentException::class);
        app(PaymentAllocationService::class)->allocate($payment, $invoice, '0.01');
    }

    public function test_decimal_allocations_are_accumulated_without_float_rounding(): void
    {
        $this->seed();
        $enrollment = StudentEnrollment::query()->firstOrFail();
        $semester = Semester::query()->firstOrFail();
        $invoice = StudentInvoice::create([
            'student_enrollment_id' => $enrollment->id,
            'semester_id' => $semester->id,
            'invoice_number' => 'INV/'.Str::upper(Str::random(12)),
            'total_amount' => '3000000.50',
            'paid_amount' => '0.00',
            'status' => 'issued',
        ]);
        $payment = Payment::create([
            'student_enrollment_id' => $enrollment->id,
            'payment_number' => 'PAY/'.Str::upper(Str::random(12)),
            'amount' => '3000000.50',
            'method' => 'transfer',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        app(PaymentAllocationService::class)->allocate($payment, $invoice, '1000000.25');
        app(PaymentAllocationService::class)->allocate($payment, $invoice, '2000000.25');

        $this->assertSame('3000000.50', $invoice->fresh()->paid_amount);
        $this->assertSame('0.00', $invoice->fresh()->outstanding_amount);
        $this->assertDatabaseHas('payment_allocations', ['payment_id' => $payment->id, 'amount' => '3000000.50']);
    }
}
