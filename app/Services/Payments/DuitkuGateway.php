<?php

namespace App\Services\Payments;

class DuitkuGateway extends AbstractGateway
{
    public function provider(): string
    {
        return 'duitku';
    }

    public function createTransaction(array $order): array
    {
        $started = (int) (microtime(true) * 1000);
        if ($this->isFake()) {
            $externalId = 'DUI-FAKE-'.$order['order_id'];
            $this->log('gateway.create', ['order_id' => $order['order_id'], 'fake' => true], true, 200, (int) (microtime(true) * 1000) - $started);

            return ['external_id' => $externalId, 'checkout_url' => 'https://sandbox.duitku.fake/'.$externalId, 'status' => 'pending', 'raw' => ['provider' => 'duitku', 'mode' => 'sandbox']];
        }

        throw new \RuntimeException('Duitku live memerlukan kredensial produksi institusi.');
    }

    public function queryTransaction(string $externalId): array
    {
        if ($this->isFake()) {
            return ['status' => 'pending', 'paid' => false, 'raw' => ['provider' => 'duitku', 'external_id' => $externalId]];
        }

        throw new \RuntimeException('Duitku live memerlukan kredensial produksi institusi.');
    }

    public function validateCallback(array $payload, string $signature): bool
    {
        $secret = $this->secret();
        if (! $secret && $this->isFake()) {
            return ($payload['resultCode'] ?? null) === '00' && isset($payload['merchantOrderId']);
        }
        if (! $secret) {
            return false;
        }

        $expected = md5(($payload['merchantCode'] ?? '').($payload['amount'] ?? '').($payload['merchantOrderId'] ?? '').$secret);

        return hash_equals($expected, $signature);
    }
}
