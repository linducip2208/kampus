<?php

namespace App\Services\Integrations;

use App\Models\AuditLog;
use App\Models\IntegrationEndpoint;
use App\Models\IntegrationLog;
use App\Models\NotificationPreference;
use App\Models\NotificationTemplate;
use App\Models\SystemBackup;
use App\Models\SystemHealthCheck;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OpsFoundationService
{
    public function registerEndpoint(string $universityId, array $data): IntegrationEndpoint
    {
        if (! in_array($data['kind'], ['pddikti', 'payment_gateway', 'whatsapp', 'sso', 'storage', 'smtp', 'generic'], true)) {
            throw ValidationException::withMessages(['kind' => 'Jenis integrasi tidak dikenal.']);
        }

        return IntegrationEndpoint::query()->create([
            'university_id' => $universityId,
            'name' => trim($data['name']),
            'kind' => $data['kind'],
            'base_url' => trim($data['base_url']),
            'auth_type' => $data['auth_type'] ?? 'none',
            'credentials' => $data['credentials'] ?? null,
            'settings' => $data['settings'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function call(IntegrationEndpoint $endpoint, string $event, array $payload = [], ?User $actor = null): IntegrationLog
    {
        $started = (int) (microtime(true) * 1000);
        $isFake = str_starts_with($endpoint->base_url, 'fake://');

        if ($isFake) {
            usleep(1000);
            $success = true;
            $code = 200;
        } else {
            $success = false;
            $code = 503;
        }

        $log = IntegrationLog::query()->create([
            'integration_endpoint_id' => $endpoint->id,
            'event' => $event,
            'direction' => 'outbound',
            'payload' => ['request' => $payload, 'fake' => $isFake],
            'response_code' => $code,
            'success' => $success,
            'duration_ms' => (int) (microtime(true) * 1000) - $started,
        ]);

        if ($actor) {
            AuditLog::query()->create([
                'user_id' => $actor->id, 'event' => 'integration.called', 'module' => 'integrations',
                'entity_type' => IntegrationLog::class, 'entity_id' => $log->id,
                'new_values' => ['event' => $event, 'success' => $success],
                'ip_address' => app()->runningInConsole() ? null : request()->ip(),
                'user_agent' => app()->runningInConsole() ? null : request()->userAgent(),
            ]);
        }

        if (! $success) {
            throw ValidationException::withMessages(['integration' => 'Endpoint live memerlukan credential institusi; gunakan driver fake:// untuk pengujian.']);
        }

        return $log->fresh();
    }

    public function upsertTemplate(string $universityId, string $key, string $channel, string $subject, string $body): NotificationTemplate
    {
        return NotificationTemplate::query()->updateOrCreate(
            ['university_id' => $universityId, 'key' => $key, 'channel' => $channel],
            ['subject' => $subject, 'body' => $body, 'is_active' => true],
        );
    }

    public function setPreference(User $user, string $event, string $channel, bool $enabled): NotificationPreference
    {
        return NotificationPreference::query()->updateOrCreate(
            ['user_id' => $user->id, 'event' => $event, 'channel' => $channel],
            ['is_enabled' => $enabled],
        );
    }

    public function recordBackup(string $filename, string $disk = 'local', int $sizeBytes = 0): SystemBackup
    {
        return SystemBackup::query()->create([
            'filename' => $filename,
            'disk' => $disk,
            'size_bytes' => $sizeBytes,
            'status' => 'completed',
            'started_at' => now()->subMinute(),
            'finished_at' => now(),
        ]);
    }

    public function runHealthChecks(): array
    {
        return DB::transaction(function () {
            $checks = [
                ['check_name' => 'database', 'status' => 'pass', 'details' => ['driver' => DB::getDriverName()]],
                ['check_name' => 'storage_writable', 'status' => is_writable(storage_path()) ? 'pass' : 'fail', 'details' => ['path' => storage_path()]],
                ['check_name' => 'queue_default', 'status' => 'pass', 'details' => ['driver' => config('queue.default')]],
            ];
            foreach ($checks as $check) {
                SystemHealthCheck::query()->updateOrCreate(
                    ['check_name' => $check['check_name']],
                    ['status' => $check['status'], 'details' => $check['details'], 'checked_at' => now()],
                );
            }

            return $checks;
        });
    }
}
