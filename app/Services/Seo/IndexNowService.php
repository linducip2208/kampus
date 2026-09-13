<?php

namespace App\Services\Seo;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class IndexNowService
{
    public function submit(array|string $urls): bool
    {
        $urls = array_values(array_unique((array) $urls));
        $pending = collect($urls)->reject(fn ($url) => Cache::has('indexnow:'.sha1($url)))->values();
        if ($pending->isEmpty() || ! config('app.url')) return true;
        $key = config('services.indexnow.key');
        if (! $key) return false;
        $response = Http::timeout(10)->post('https://api.indexnow.org/indexnow', [
            'host' => parse_url(config('app.url'), PHP_URL_HOST), 'key' => $key, 'keyLocation' => url('/indexnow-key.txt'), 'urlList' => $pending->all(),
        ]);
        if ($response->successful()) $pending->each(fn ($url) => Cache::put('indexnow:'.sha1($url), true, now()->addDays(7)));
        return $response->successful();
    }
}
