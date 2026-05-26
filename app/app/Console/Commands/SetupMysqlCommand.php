<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PDO;

class SetupMysqlCommand extends Command
{
    protected $signature = 'db:setup-mysql {--seed : Run database seeders after migrate} {--fresh : Drop all tables first}';

    protected $description = 'Create the MySQL database (if missing) and run migrations';

    public function handle(): int
    {
        if (config('database.default') !== 'mysql') {
            $this->error('DB_CONNECTION is not mysql. Set it in .env first.');

            return self::FAILURE;
        }

        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port');
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        if (! $database) {
            $this->error('DB_DATABASE is not set in .env');

            return self::FAILURE;
        }

        try {
            $pdo = new PDO(
                "mysql:host={$host};port={$port};charset=utf8mb4",
                $username,
                $password,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (\PDOException $e) {
            $this->error('Could not connect to MySQL: '.$e->getMessage());
            $this->line('');
            $this->line('Check DB_HOST, DB_USERNAME, and DB_PASSWORD in .env.');
            $this->line('MySQL 8 on Windows: use the password you set during MySQL Installer setup.');

            return self::FAILURE;
        }

        $safeName = str_replace('`', '``', $database);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$safeName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $this->info("Database `{$database}` is ready.");

        config(['database.connections.mysql.database' => $database]);
        DB::purge('mysql');
        DB::reconnect('mysql');

        $migrate = $this->option('fresh') ? 'migrate:fresh' : 'migrate';
        $options = ['--force' => true];

        if ($this->option('seed')) {
            $options['--seed'] = true;
        }

        $this->info("Running php artisan {$migrate}...");
        Artisan::call($migrate, $options);
        $this->output->write(Artisan::output());

        $this->newLine();
        $this->info('MySQL setup complete.');

        return self::SUCCESS;
    }
}
