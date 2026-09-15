<?php

namespace App\Services\Payments;

class MidtransGateway extends AbstractGateway
{
    public function provider(): string
    {
        return 'midtrans';
    }

    public function createTransaction(array $order): array
    {
        $started = (int) (microtime(true) * 1000);
        if ($this->isFake()) {
            $externalId = 'MID-FAKE-'.$order['order_id'];
            $this->log('gateway.create', ['order_id' => $order['order_id'], 'fake' => true], true, 200, (int) (microtime(true) * 1000) - $started);

            return ['external_id' => $externalId, 'checkout_url' => 'https://sandbox.midtrans.fake/'.$externalId, 'status' => 'pending', 'raw' => ['provider' => 'midtrans', 'mode' => 'sandbox']];
        }

        throw new \RuntimeException('Midtrans live memerlukan kredensial produksi institusi.');
    }

    public function queryTransaction(string $externalId): array
    {
        if ($this->isFake()) {
            return ['status' => 'pending', 'paid' => false, 'raw' => ['provider' => 'midtrans', 'external_id' => $externalId]];
        }

        throw new \RuntimeException('Midtrans live memerlukan kredensial produksi institusi.');
    }

    public function validateCallback(array $payload, string $signature): bool
    {
        if ($this->isFake()) {
            return ($payload['status_code'] ?? null) === '200' && isset($payload['order_id']);
        }

        $secret = $this->secret();
        if (! $secret) {
            return false;
        }

        $expected = hash('sha512', ($payload['order_id'] ?? '').($payload['status_code'] ?? '').($payload['gross_amount'] ?? '').$secret);

        return hash_equals($expected, $signature);
    }
}
