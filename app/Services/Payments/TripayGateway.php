<?php

namespace App\Services\Payments;

class TripayGateway extends AbstractGateway
{
    public function provider(): string
    {
        return 'tripay';
    }

    public function createTransaction(array $order): array
    {
        $started = (int) (microtime(true) * 1000);
        if ($this->isFake()) {
            $externalId = 'TRI-FAKE-'.$order['order_id'];
            $this->log('gateway.create', ['order_id' => $order['order_id'], 'fake' => true], true, 200, (int) (microtime(true) * 1000) - $started);

            return ['external_id' => $externalId, 'checkout_url' => 'https://sandbox.tripay.fake/'.$externalId, 'status' => 'pending', 'raw' => ['provider' => 'tripay', 'mode' => 'sandbox']];
        }

        throw new \RuntimeException('Tripay live memerlukan kredensial produksi institusi.');
    }

    public function queryTransaction(string $externalId): array
    {
        if ($this->isFake()) {
            return ['status' => 'pending', 'paid' => false, 'raw' => ['provider' => 'tripay', 'external_id' => $externalId]];
        }

        throw new \RuntimeException('Tripay live memerlukan kredensial produksi institusi.');
    }

    public function validateCallback(array $payload, string $signature): bool
    {
        return $this->hmacValid($payload, $signature);
    }
}
