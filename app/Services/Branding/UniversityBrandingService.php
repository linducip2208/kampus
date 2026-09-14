<?php

namespace App\Services\Branding;

use App\Models\University;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class UniversityBrandingService
{
    public function current(?User $user = null): ?University
    {
        $universityId = $user?->roles()->whereNotNull('role_user.university_id')->value('role_user.university_id');
        $cacheKey = 'branding.university.'.($universityId ?: 'public');

        return Cache::remember($cacheKey, now()->addMinutes(30), fn () => University::query()
            ->when($universityId, fn ($query) => $query->whereKey($universityId))
            ->first());
    }

    public function values(?User $user = null): array
    {
        $university = $this->current($user);

        return [
            'university' => $university,
            'name' => $university?->name ?: config('app.name'),
            'shortName' => $university?->short_name ?: $university?->name ?: config('app.name'),
            'logo' => $university?->logo_path,
            'logoDark' => $university?->logo_dark_path,
            'favicon' => $university?->favicon_path,
            'primaryColor' => $university?->primary_color ?: '#206bc4',
            'secondaryColor' => $university?->secondary_color ?: '#0ca678',
        ];
    }
}
