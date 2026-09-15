<?php

namespace App\Services\Payments\Contracts;

interface PaymentGatewayInterface
{
    public function provider(): string;

    /**
     * @param  array{amount_minor: int, order_id: string, customer: array{name: string, email: string}, invoice_number?: string}  $order
     * @return array{external_id: string, checkout_url: ?string, status: string, raw: array}
     */
    public function createTransaction(array $order): array;

    /**
     * @return array{status: string, paid: bool, raw: array}
     */
    public function queryTransaction(string $externalId): array;

    public function validateCallback(array $payload, string $signature): bool;

    public function normalizeError(\Throwable $error): string;
}
