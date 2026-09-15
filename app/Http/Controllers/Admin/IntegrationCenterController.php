<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IntegrationEndpoint;
use App\Models\IntegrationLog;
use App\Services\Integrations\OpsFoundationService;
use App\Services\Security\UniversityScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\View\View;

class IntegrationCenterController extends Controller
{
    public function index(Request $request, UniversityScope $scope): View
    {
        abort_unless($request->user()?->hasRole(['super_admin', 'finance', 'operator_pddikti', 'auditor']), 403);

        $endpoints = $scope->direct(IntegrationEndpoint::query()->withCount('logs')->latest(), $request->user())->get();
        $logs = IntegrationLog::query()->whereIn(
            'integration_endpoint_id',
            $scope->direct(IntegrationEndpoint::query()->select('id'), $request->user())
        )->latest()->paginate(20);

        return view('admin.integrations', compact('endpoints', 'logs'));
    }

    public function store(Request $request, OpsFoundationService $service): RedirectResponse
    {
        abort_unless($request->user()?->hasRole(['super_admin', 'finance', 'operator_pddikti']), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'kind' => ['required', 'string', 'max:50'],
            'base_url' => ['required', 'string', 'max:500'],
            'auth_type' => ['nullable', 'string', 'max:50'],
            'secret' => ['nullable', 'string', 'max:1000'],
            'mode' => ['nullable', 'in:sandbox,live'],
        ]);
        $universityId = $request->user()->roles()->firstOrFail()->pivot->university_id;

        $credentials = null;
        if (! empty($data['secret'])) {
            $credentials = Crypt::encryptString(json_encode(['secret' => $data['secret']], JSON_THROW_ON_ERROR));
        }

        $service->registerEndpoint($universityId, [
            'name' => $data['name'],
            'kind' => $data['kind'],
            'base_url' => $data['base_url'],
            'auth_type' => $data['auth_type'] ?? 'none',
            'credentials' => $credentials,
            'settings' => ['mode' => $data['mode'] ?? 'sandbox'],
        ]);

        return back()->with('success', 'Endpoint integrasi didaftarkan, kredensial terenkripsi.');
    }

    public function test(Request $request, IntegrationEndpoint $endpoint, OpsFoundationService $service): RedirectResponse
    {
        abort_unless($request->user()?->hasRole(['super_admin', 'finance', 'operator_pddikti']), 403);
        $scope = app(UniversityScope::class);
        $endpoint = $scope->direct(IntegrationEndpoint::query(), $request->user())->findOrFail($endpoint->id);
        $service->call($endpoint, 'connection_test', ['at' => now()->toIso8601String()], $request->user());

        return back()->with('success', 'Tes koneksi berhasil dicatat.');
    }
}
