<?php

namespace App\Services\Payments;

use App\Models\AuditLog;
use App\Models\IntegrationEndpoint;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\StudentInvoice;
use App\Models\User;
use App\Services\Finance\PaymentAllocationService;
use App\Services\NumberSequenceService;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentGatewayService
{
    public function __construct(
        private readonly NumberSequenceService $sequences,
        private readonly PaymentAllocationService $allocation,
    ) {}

    public function resolve(IntegrationEndpoint $endpoint): Contracts\PaymentGatewayInterface
    {
        return match ($endpoint->kind) {
            'payment_manual' => new ManualTransferGateway($endpoint),
            'payment_midtrans' => new MidtransGateway($endpoint),
            'payment_xendit' => new XenditGateway($endpoint),
            'payment_tripay' => new TripayGateway($endpoint),
            'payment_duitku' => new DuitkuGateway($endpoint),
            default => throw ValidationException::withMessages(['provider' => 'Provider gateway tidak dikenal.']),
        };
    }

    public function createForInvoice(StudentInvoice $invoice, IntegrationEndpoint $endpoint, User $actor, ?string $idempotencyKey = null): PaymentTransaction
    {
        return DB::transaction(function () use ($invoice, $endpoint, $actor, $idempotencyKey) {
            $locked = StudentInvoice::query()->with('enrollment.studentProfile')->lockForUpdate()->findOrFail($invoice->id);
            if (! in_array($locked->status, ['issued', 'partial'], true)) {
                throw ValidationException::withMessages(['invoice' => 'Tagihan sudah lunas.']);
            }
            $key = $idempotencyKey ?? (string) Str::ulid();
            $existing = PaymentTransaction::query()->where('idempotency_key', $key)->first();
            if ($existing) {
                return $existing;
            }

            $gateway = $this->resolve($endpoint);
            $amountMinor = Money::toMinorUnits($locked->outstanding_amount);
            try {
                $result = $gateway->createTransaction([
                    'amount_minor' => $amountMinor,
                    'order_id' => $locked->invoice_number,
                    'customer' => ['name' => $locked->enrollment->studentProfile->full_name, 'email' => $locked->enrollment->studentProfile->email],
                    'invoice_number' => $locked->invoice_number,
                ]);
            } catch (\Throwable $error) {
                PaymentTransaction::query()->create([
                    'university_id' => $endpoint->university_id,
                    'integration_endpoint_id' => $endpoint->id,
                    'student_invoice_id' => $locked->id,
                    'provider' => $gateway->provider(),
                    'idempotency_key' => $key,
                    'amount' => Money::fromMinorUnits($amountMinor),
                    'status' => 'failed',
                    'attempts' => 1,
                    'last_error' => $gateway->normalizeError($error),
                ]);
                throw ValidationException::withMessages(['gateway' => $gateway->normalizeError($error)]);
            }

            $transaction = PaymentTransaction::query()->create([
                'university_id' => $endpoint->university_id,
                'integration_endpoint_id' => $endpoint->id,
                'student_invoice_id' => $locked->id,
                'provider' => $gateway->provider(),
                'external_id' => $result['external_id'],
                'idempotency_key' => $key,
                'amount' => Money::fromMinorUnits($amountMinor),
                'status' => $result['status'],
                'request_payload' => $result['raw'],
                'attempts' => 1,
            ]);
            $this->audit($actor, $transaction, 'gateway.transaction_created', ['external_id' => $result['external_id']]);

            return $transaction->fresh();
        });
    }

    public function handleCallback(IntegrationEndpoint $endpoint, array $payload, string $signature, ?User $actor = null): PaymentTransaction
    {
        return DB::transaction(function () use ($endpoint, $payload, $signature, $actor) {
            $gateway = $this->resolve($endpoint);
            if (! $gateway->validateCallback($payload, $signature)) {
                throw ValidationException::withMessages(['signature' => 'Tanda tangan callback tidak valid.']);
            }

            $externalId = $payload['order_id'] ?? $payload['merchantOrderId'] ?? $payload['external_id'] ?? null;
            $transaction = PaymentTransaction::query()->where('external_id', $externalId)->lockForUpdate()->firstOrFail();
            if ($transaction->status === 'paid') {
                return $transaction->fresh();
            }

            $transaction->forceFill(['callback_payload' => $payload, 'attempts' => $transaction->attempts + 1])->save();

            $paid = ($payload['transaction_status'] ?? null) === 'settlement'
                || ($payload['status_code'] ?? null) === '200'
                || ($payload['resultCode'] ?? null) === '00'
                || ($payload['status'] ?? null) === 'PAID';
            if (! $paid) {
                $transaction->forceFill(['status' => 'failed', 'last_error' => 'Callback menyatakan belum lunas.'])->save();

                return $transaction->fresh();
            }

            $invoice = StudentInvoice::query()->lockForUpdate()->findOrFail($transaction->student_invoice_id);
            $universityId = $transaction->university_id;
            $this->sequences->ensure($universityId, 'payment', 'PAY/{year}/{number}');
            $payment = Payment::query()->create([
                'student_enrollment_id' => $invoice->student_enrollment_id,
                'payment_number' => $this->sequences->next($universityId, 'payment', ['year' => now()->year]),
                'amount' => $transaction->amount,
                'method' => 'gateway:'.$transaction->provider,
                'status' => 'paid',
                'paid_at' => now(),
            ]);
            $this->allocation->allocate($payment, $invoice, $transaction->amount);
            $transaction->forceFill(['status' => 'paid', 'payment_id' => $payment->id, 'paid_at' => now()])->save();
            if ($actor) {
                $this->audit($actor, $transaction, 'gateway.transaction_paid', ['payment_id' => $payment->id]);
            }

            return $transaction->fresh();
        });
    }

    public function retry(PaymentTransaction $transaction, User $actor): PaymentTransaction
    {
        return DB::transaction(function () use ($transaction, $actor) {
            $locked = PaymentTransaction::query()->with('endpoint')->lockForUpdate()->findOrFail($transaction->id);
            if ($locked->status === 'paid') {
                throw ValidationException::withMessages(['transaction' => 'Transaksi sudah lunas.']);
            }
            $gateway = $this->resolve($locked->endpoint);
            try {
                $result = $gateway->queryTransaction((string) $locked->external_id);
                $locked->forceFill(['attempts' => $locked->attempts + 1])->save();
                if ($result['paid']) {
                    return $this->handleCallback($locked->endpoint, ['order_id' => $locked->external_id, 'status' => 'PAID'], '', $actor);
                }
            } catch (\Throwable $error) {
                $locked->forceFill(['attempts' => $locked->attempts + 1, 'last_error' => $gateway->normalizeError($error)])->save();
            }

            return $locked->fresh();
        });
    }

    private function audit(User $actor, PaymentTransaction $transaction, string $event, array $values): void
    {
        AuditLog::query()->create([
            'user_id' => $actor->id, 'event' => $event, 'module' => 'finance',
            'entity_type' => PaymentTransaction::class, 'entity_id' => $transaction->id, 'new_values' => $values,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
            'user_agent' => app()->runningInConsole() ? null : request()->userAgent(),
        ]);
    }
}
