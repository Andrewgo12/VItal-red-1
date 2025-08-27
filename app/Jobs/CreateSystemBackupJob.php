<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;
use ZipArchive;

class CreateSystemBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $backupType;
    protected $maxTries = 2;
    protected $timeout = 3600; // 1 hour

    /**
     * Create a new job instance.
     */
    public function __construct(string $backupType = 'full')
    {
        $this->backupType = $backupType;
        $this->onQueue('backups');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Starting system backup', ['type' => $this->backupType]);

            $timestamp = now()->format('Y-m-d_H-i-s');
            $backupName = "backup_{$this->backupType}_{$timestamp}";
            $backupPath = storage_path("app/backups/{$backupName}");

            // Create backup directory
            if (!file_exists($backupPath)) {
                mkdir($backupPath, 0755, true);
            }

            // Perform backup based on type
            switch ($this->backupType) {
                case 'database':
                    $this->backupDatabase($backupPath);
                    break;
                case 'files':
                    $this->backupFiles($backupPath);
                    break;
                case 'full':
                default:
                    $this->backupDatabase($backupPath);
                    $this->backupFiles($backupPath);
                    break;
            }

            // Compress backup
            $zipPath = $this->compressBackup($backupPath, $backupName);

            // Clean up temporary directory
            $this->cleanupDirectory($backupPath);

            // Clean old backups
            $this->cleanOldBackups();

            // Log success
            $fileSize = $this->formatBytes(filesize($zipPath));
            Log::info('System backup completed successfully', [
                'type' => $this->backupType,
                'file' => basename($zipPath),
                'size' => $fileSize
            ]);

        } catch (Exception $e) {
            Log::error('System backup failed', [
                'type' => $this->backupType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Backup database
     */
    private function backupDatabase(string $backupPath): void
    {
        Log::info('Starting database backup');

        $dbConfig = config('database.connections.' . config('database.default'));
        $dbName = $dbConfig['database'];
        $dbUser = $dbConfig['username'];
        $dbPassword = $dbConfig['password'];
        $dbHost = $dbConfig['host'];
        $dbPort = $dbConfig['port'];

        $sqlFile = $backupPath . '/database.sql';

        // Use mysqldump command
        $command = sprintf(
            'mysqldump --host=%s --port=%s --user=%s --password=%s --single-transaction --routines --triggers %s > %s',
            escapeshellarg($dbHost),
            escapeshellarg($dbPort),
            escapeshellarg($dbUser),
            escapeshellarg($dbPassword),
            escapeshellarg($dbName),
            escapeshellarg($sqlFile)
        );

        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new Exception('Database backup failed: ' . implode("\n", $output));
        }

        // Verify backup file was created and has content
        if (!file_exists($sqlFile) || filesize($sqlFile) === 0) {
            throw new Exception('Database backup file is empty or was not created');
        }

        Log::info('Database backup completed', [
            'file' => basename($sqlFile),
            'size' => $this->formatBytes(filesize($sqlFile))
        ]);
    }

    /**
     * Backup important files
     */
    private function backupFiles(string $backupPath): void
    {
        Log::info('Starting files backup');

        $filesToBackup = [
            '.env' => base_path('.env'),
            'storage' => storage_path(),
            'public/uploads' => public_path('uploads'),
        ];

        foreach ($filesToBackup as $name => $sourcePath) {
            if (file_exists($sourcePath)) {
                $destinationPath = $backupPath . '/' . $name;
                
                if (is_file($sourcePath)) {
                    // Copy single file
                    $this->ensureDirectoryExists(dirname($destinationPath));
                    copy($sourcePath, $destinationPath);
                } else {
                    // Copy directory recursively
                    $this->copyDirectory($sourcePath, $destinationPath);
                }
                
                Log::info('Backed up', ['source' => $name]);
            }
        }

        Log::info('Files backup completed');
    }

    /**
     * Compress backup directory into zip file
     */
    private function compressBackup(string $backupPath, string $backupName): string
    {
        Log::info('Compressing backup');

        $zipPath = storage_path("app/backups/{$backupName}.zip");
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            throw new Exception('Cannot create zip file: ' . $zipPath);
        }

        $this->addDirectoryToZip($zip, $backupPath, '');
        $zip->close();

        if (!file_exists($zipPath)) {
            throw new Exception('Zip file was not created');
        }

        Log::info('Backup compressed', [
            'file' => basename($zipPath),
            'size' => $this->formatBytes(filesize($zipPath))
        ]);

        return $zipPath;
    }

    /**
     * Add directory contents to zip file recursively
     */
    private function addDirectoryToZip(ZipArchive $zip, string $sourcePath, string $zipPath): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourcePath),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                $relativePath = $zipPath . substr($file->getPathname(), strlen($sourcePath) + 1) . '/';
                $zip->addEmptyDir($relativePath);
            } elseif ($file->isFile()) {
                $relativePath = $zipPath . substr($file->getPathname(), strlen($sourcePath) + 1);
                $zip->addFile($file->getPathname(), $relativePath);
            }
        }
    }

    /**
     * Copy directory recursively
     */
    private function copyDirectory(string $source, string $destination): void
    {
        if (!is_dir($source)) {
            return;
        }

        $this->ensureDirectoryExists($destination);

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $destPath = $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            
            if ($item->isDir()) {
                $this->ensureDirectoryExists($destPath);
            } else {
                $this->ensureDirectoryExists(dirname($destPath));
                copy($item->getPathname(), $destPath);
            }
        }
    }

    /**
     * Ensure directory exists
     */
    private function ensureDirectoryExists(string $path): void
    {
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }
    }

    /**
     * Clean up temporary directory
     */
    private function cleanupDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }

        rmdir($path);
    }

    /**
     * Clean old backup files
     */
    private function cleanOldBackups(): void
    {
        $backupDir = storage_path('app/backups');
        $retentionDays = config('backup.retention_days', 30);
        $cutoffDate = now()->subDays($retentionDays);

        if (!is_dir($backupDir)) {
            return;
        }

        $files = glob($backupDir . '/backup_*.zip');
        $deletedCount = 0;

        foreach ($files as $file) {
            $fileTime = filemtime($file);
            
            if ($fileTime < $cutoffDate->timestamp) {
                unlink($file);
                $deletedCount++;
                Log::info('Deleted old backup', ['file' => basename($file)]);
            }
        }

        if ($deletedCount > 0) {
            Log::info('Cleaned old backups', ['deleted_count' => $deletedCount]);
        }
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Handle job failure
     */
    public function failed(Exception $exception): void
    {
        Log::error('CreateSystemBackupJob failed permanently', [
            'type' => $this->backupType,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        // Could send notification to administrators about failed backup
    }
}
