<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\CampusPolicy;
use App\Services\Branding\UniversityBrandingService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::before(function ($user, string $ability, array $arguments): ?bool {
            if (! $user instanceof User) {
                return null;
            }

            return app(CampusPolicy::class)->allows($user, $ability, $arguments[0] ?? null);
        });

        View::composer(['layouts.tabler.*', 'auth.*'], function ($view): void {
            $view->with('brand', app(UniversityBrandingService::class)->values(auth()->user()));
        });
    }
}
