<?php

namespace App\Services\Payments;

class ManualTransferGateway extends AbstractGateway
{
    public function provider(): string
    {
        return 'manual';
    }

    public function createTransaction(array $order): array
    {
        $started = (int) (microtime(true) * 1000);
        $externalId = 'MANUAL-'.$order['order_id'];
        $this->log('gateway.create', ['order_id' => $order['order_id'], 'fake' => true], true, 200, (int) (microtime(true) * 1000) - $started);

        return ['external_id' => $externalId, 'checkout_url' => null, 'status' => 'pending', 'raw' => ['provider' => 'manual']];
    }

    public function queryTransaction(string $externalId): array
    {
        return ['status' => 'pending', 'paid' => false, 'raw' => ['provider' => 'manual', 'external_id' => $externalId]];
    }

    public function validateCallback(array $payload, string $signature): bool
    {
        return $this->hmacValid($payload, $signature);
    }
}
