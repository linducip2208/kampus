<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class BackupDatabase extends Command
{
    protected $signature = 'backup:database';
    protected $description = 'Membuat backup database sesuai driver yang dikonfigurasi.';
    public function handle(): int
    {
        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir);
        $stamp = now()->format('Ymd-His');

        if (config('database.default') === 'mysql') {
            $path = $dir.'/campus-'.$stamp.'.sql';
            $process = new Process([
                'mysqldump',
                '--host='.config('database.connections.mysql.host'),
                '--port='.config('database.connections.mysql.port'),
                '--user='.config('database.connections.mysql.username'),
                '--single-transaction',
                '--routines',
                '--triggers',
                config('database.connections.mysql.database'),
            ], base_path(), ['MYSQL_PWD' => (string) config('database.connections.mysql.password')], null, 300);
            $process->run();
            if (! $process->isSuccessful()) {
                $this->error('Backup MySQL gagal: '.$process->getErrorOutput());
                return self::FAILURE;
            }
            File::put($path, $process->getOutput());
            $this->info('MySQL backup created: '.$path);
            return self::SUCCESS;
        }

        $sqlitePath = config('database.connections.sqlite.database');
        $path = $dir.'/campus-'.$stamp.'.sqlite';
        if ($sqlitePath && $sqlitePath !== ':memory:' && File::exists($sqlitePath)) {
            File::copy($sqlitePath, $path);
            $this->info('SQLite backup created: '.$path);
            return self::SUCCESS;
        }

        $metadataPath = $dir.'/campus-'.$stamp.'.json';
        File::put($metadataPath, json_encode([
            'created_at' => now()->toIso8601String(),
            'driver' => config('database.default'),
            'note' => 'No file-backed database is configured in this environment.',
        ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        $this->warn('Database metadata created because the current database is not file-backed: '.$metadataPath);
        return self::SUCCESS;
    }
}
