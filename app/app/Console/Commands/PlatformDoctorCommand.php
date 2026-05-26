<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class PlatformDoctorCommand extends Command
{
    protected $signature = 'platform:doctor';

    protected $description = 'Check deployment readiness (PHP extensions, database, storage)';

    public function handle(): int
    {
        $ok = true;
        $extensions = ['openssl', 'mbstring', 'pdo', 'curl', 'fileinfo', 'json'];

        foreach ($extensions as $ext) {
            if (! extension_loaded($ext)) {
                $this->error('Missing PHP extension: '.$ext);
                $ok = false;
            }
        }

        $this->line('Database driver: '.config('database.default'));

        try {
            DB::connection()->getPdo();
            $this->info('Database connection: OK');
        } catch (\Throwable $e) {
            $this->error('Database connection: '.$e->getMessage());
            $ok = false;
        }

        $paths = ['storage', 'bootstrap/cache', 'database'];
        foreach ($paths as $path) {
            if (! is_writable(base_path($path))) {
                $this->error('Not writable: '.$path);
                $ok = false;
            } else {
                $this->info('Writable: '.$path);
            }
        }

        if (! File::exists(public_path('build/manifest.json'))) {
            $this->warn('Vite build missing — run: npm ci && npm run build');
        } else {
            $this->info('Vite manifest: OK');
        }

        if (config('app.key') === null || config('app.key') === '') {
            $this->error('APP_KEY is empty — run: php artisan key:generate');
            $ok = false;
        }

        if ($ok) {
            $this->info('Platform doctor: all checks passed.');

            return self::SUCCESS;
        }

        $this->warn('Platform doctor: fix issues above before deploying.');

        return self::FAILURE;
    }
}
