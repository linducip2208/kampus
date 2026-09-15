<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InvoicePenalty;
use App\Models\Payment;
use App\Models\PaymentRefund;
use App\Models\PaymentTransaction;
use App\Models\StudentInvoice;
use App\Services\Finance\FinanceAdjustmentService;
use App\Services\Security\UniversityScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinanceController extends Controller
{
    public function index(Request $request, UniversityScope $scope, FinanceAdjustmentService $adjustments): View
    {
        abort_unless($request->user()?->hasRole(['super_admin', 'finance', 'auditor']), 403);
        $universityId = $request->user()->roles()->firstOrFail()->pivot->university_id;

        $invoices = $scope->invoices(StudentInvoice::query()->with(['enrollment.studentProfile', 'discounts', 'penalties'])->latest(), $request->user())->paginate(15, ['*'], 'invoice_page');
        $payments = $scope->payments(Payment::query()->with('enrollment.studentProfile')->latest(), $request->user())->paginate(15, ['*'], 'payment_page');
        $refunds = PaymentRefund::query()->whereHas('payment.enrollment.studyProgram.department.faculty', fn ($query) => $query->where('university_id', $universityId))->with('payment')->latest()->paginate(10, ['*'], 'refund_page');
        $transactions = PaymentTransaction::query()->where('university_id', $universityId)->latest()->paginate(10, ['*'], 'gateway_page');
        $summary = $adjustments->reconciliationSummary($universityId);

        return view('admin.finance', compact('invoices', 'payments', 'refunds', 'transactions', 'summary'));
    }

    public function discount(Request $request, StudentInvoice $invoice, FinanceAdjustmentService $service): RedirectResponse
    {
        $this->authorizeInvoice($request, $invoice->id);
        $data = $request->validate([
            'kind' => ['required', 'in:scholarship,early_bird,staff,sibling,other'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);
        $service->grantDiscount($invoice, $data['kind'], (string) $data['amount'], $data['reason'] ?? null, $request->user());

        return back()->with('success', 'Diskon diberikan.');
    }

    public function penalty(Request $request, StudentInvoice $invoice, FinanceAdjustmentService $service): RedirectResponse
    {
        $this->authorizeInvoice($request, $invoice->id);
        $data = $request->validate([
            'kind' => ['required', 'string', 'max:50'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);
        $service->applyPenalty($invoice, $data['kind'], (string) $data['amount'], $data['reason'] ?? null, $request->user());

        return back()->with('success', 'Denda diterapkan.');
    }

    public function waivePenalty(Request $request, InvoicePenalty $penalty, FinanceAdjustmentService $service): RedirectResponse
    {
        abort_unless($request->user()?->hasRole(['super_admin', 'finance']), 403);
        $this->authorizeInvoice($request, $penalty->student_invoice_id);
        $service->waivePenalty($penalty, $request->user());

        return back()->with('success', 'Denda dibebaskan.');
    }

    private function authorizeInvoice(Request $request, string $invoiceId): void
    {
        abort_unless($request->user()?->hasRole(['super_admin', 'finance']), 403);
        $scope = app(UniversityScope::class);
        abort_unless($scope->invoices(StudentInvoice::query(), $request->user())->whereKey($invoiceId)->exists(), 404);
    }
}
