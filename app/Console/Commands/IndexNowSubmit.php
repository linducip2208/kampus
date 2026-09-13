<?php

namespace App\Console\Commands;

use App\Services\Seo\IndexNowService;
use Illuminate\Console\Command;

class IndexNowSubmit extends Command
{
    protected $signature = 'seo:indexnow {urls?*}';
    protected $description = 'Kirim URL baru ke IndexNow dengan cache deduplikasi.';

    public function handle(IndexNowService $indexNow): int
    {
        $urls = $this->argument('urls') ?: [url('/'), url('/docs')];
        $this->info($indexNow->submit($urls) ? 'URL berhasil dikirim atau sudah tersimpan di cache.' : 'IndexNow belum dikonfigurasi.');
        return self::SUCCESS;
    }
}
