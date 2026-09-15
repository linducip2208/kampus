<?php

namespace App\Services\Payments;

use App\Models\IntegrationEndpoint;
use App\Models\IntegrationLog;
use App\Services\Payments\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\Crypt;

abstract class AbstractGateway implements PaymentGatewayInterface
{
    public function __construct(protected readonly IntegrationEndpoint $endpoint) {}

    protected function isFake(): bool
    {
        return str_starts_with($this->endpoint->base_url, 'fake://')
            || ($this->endpoint->settings['mode'] ?? 'sandbox') === 'sandbox'
            || empty($this->secret());
    }

    protected function secret(): ?string
    {
        $credentials = $this->endpoint->credentials;
        if (! $credentials) {
            return null;
        }

        try {
            $decoded = json_decode(Crypt::decryptString($credentials), true);
        } catch (\Throwable) {
            $decoded = json_decode((string) $credentials, true);
        }

        return $decoded['secret'] ?? $decoded['server_key'] ?? null;
    }

    protected function publicKey(): ?string
    {
        $credentials = $this->endpoint->credentials;
        if (! $credentials) {
            return null;
        }

        try {
            $decoded = json_decode(Crypt::decryptString($credentials), true);
        } catch (\Throwable) {
            $decoded = json_decode((string) $credentials, true);
        }

        return $decoded['public_key'] ?? $decoded['client_key'] ?? $decoded['api_key'] ?? null;
    }

    protected function log(string $event, array $payload, bool $success, int $responseCode, int $durationMs): IntegrationLog
    {
        return IntegrationLog::query()->create([
            'integration_endpoint_id' => $this->endpoint->id,
            'event' => $event,
            'direction' => 'outbound',
            'payload' => $payload,
            'response_code' => $responseCode,
            'success' => $success,
            'duration_ms' => $durationMs,
        ]);
    }

    protected function hmacValid(array $payload, string $signature, string $field = 'order_id'): bool
    {
        $secret = $this->secret();
        if (! $secret) {
            return false;
        }

        $expected = hash_hmac('sha512', json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES), $secret);

        return hash_equals($expected, $signature);
    }

    public function normalizeError(\Throwable $error): string
    {
        return $this->provider().': '.$error->getMessage();
    }
}
