<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SystemBackup extends Command
{
    protected $signature = 'system:backup';
    protected $description = 'Backup database and storage files';

    public function handle()
    {
        try {
            $this->info('Starting backup...');

            $backupPath = storage_path('app/backups');
            File::ensureDirectoryExists($backupPath);

            $date = now()->format('Y-m-d_H-i-s');

            // ------------------------
            // DATABASE BACKUP
            // ------------------------
            $dbHost = config('database.connections.mysql.host');
            $dbName = config('database.connections.mysql.database');
            $dbUser = config('database.connections.mysql.username');
            $dbPass = config('database.connections.mysql.password');

            if (!$dbName || !$dbUser) {
                throw new \Exception('Database configuration missing.');
            }

            $dbFile = "{$backupPath}/db_{$date}.sql.gz";
            $tmpConfig = tempnam(sys_get_temp_dir(), 'mycnf');

            file_put_contents($tmpConfig, "[client]\nuser={$dbUser}\npassword={$dbPass}\nhost={$dbHost}\n");

            $command = "mysqldump --defaults-extra-file={$tmpConfig} {$dbName} 2>&1 | gzip > {$dbFile}";
            exec($command, $output, $result);

            unlink($tmpConfig);

            if ($result !== 0) {
                throw new \Exception('Database dump failed: ' . implode("\n", $output));
            }

            $this->info('Database backup completed.');

            // ------------------------
            // STORAGE BACKUP
            // ------------------------
            $storageFile = "{$backupPath}/storage_{$date}.tar.gz";
            exec("tar -czf {$storageFile} -C " . storage_path('app') . " public 2>&1", $tarOutput, $tarResult);

            if ($tarResult !== 0) {
                throw new \Exception('Storage backup failed: ' . implode("\n", $tarOutput));
            }

            $this->info('Storage backup completed.');

            // ------------------------
            // CLEAN OLD BACKUPS (Keep 30 days)
            // ------------------------
            $files = File::files($backupPath);

            foreach ($files as $file) {
                if (Carbon::createFromTimestamp($file->getCTime())->diffInDays(now()) > 30) {
                    File::delete($file);
                }
            }

            $this->info('Old backups cleaned.');
            $this->info('Backup finished successfully.');

            return Command::SUCCESS;

        } catch (\Throwable $e) {

            Log::error('Backup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->error('Backup failed: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }
}