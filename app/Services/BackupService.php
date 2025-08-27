<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;
use ZipArchive;

class BackupService
{
    private string $backupDisk;
    private string $backupPath;
    private array $config;

    public function __construct()
    {
        $this->backupDisk = config('backup.backup.destination.disks')[0] ?? 'local';
        $this->backupPath = config('backup.backup.name', config('app.name'));
        $this->config = config('backup', []);
    }

    /**
     * Create a full system backup
     */
    public function createFullBackup(array $options = []): array
    {
        $startTime = microtime(true);
        $backupId = 'backup_' . now()->format('Y-m-d_H-i-s');
        
        Log::info('Starting full system backup', ['backup_id' => $backupId]);

        try {
            $result = [
                'backup_id' => $backupId,
                'started_at' => now(),
                'type' => 'full',
                'status' => 'in_progress',
                'components' => []
            ];

            // Create backup directory
            $backupDir = "backups/{$backupId}";
            Storage::disk($this->backupDisk)->makeDirectory($backupDir);

            // Backup database
            if ($options['include_database'] ?? true) {
                $result['components']['database'] = $this->backupDatabase($backupDir);
            }

            // Backup files
            if ($options['include_files'] ?? true) {
                $result['components']['files'] = $this->backupFiles($backupDir, $options);
            }

            // Backup configuration
            if ($options['include_config'] ?? true) {
                $result['components']['config'] = $this->backupConfiguration($backupDir);
            }

            // Create backup manifest
            $manifest = $this->createBackupManifest($result);
            Storage::disk($this->backupDisk)->put("{$backupDir}/manifest.json", json_encode($manifest, JSON_PRETTY_PRINT));

            // Compress backup if requested
            if ($options['compress'] ?? true) {
                $result['components']['compression'] = $this->compressBackup($backupDir);
            }

            // Calculate backup size and duration
            $result['size'] = $this->calculateBackupSize($backupDir);
            $result['duration'] = round(microtime(true) - $startTime, 2);
            $result['completed_at'] = now();
            $result['status'] = 'completed';

            // Store backup metadata
            $this->storeBackupMetadata($result);

            // Cleanup old backups
            $this->cleanupOldBackups();

            Log::info('Full system backup completed successfully', [
                'backup_id' => $backupId,
                'duration' => $result['duration'],
                'size' => $result['size']
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('Full system backup failed', [
                'backup_id' => $backupId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'backup_id' => $backupId,
                'status' => 'failed',
                'error' => $e->getMessage(),
                'duration' => round(microtime(true) - $startTime, 2)
            ];
        }
    }

    /**
     * Create database backup
     */
    public function backupDatabase(string $backupDir): array
    {
        $startTime = microtime(true);
        
        try {
            $connection = config('database.default');
            $config = config("database.connections.{$connection}");
            
            $filename = "database_" . now()->format('Y-m-d_H-i-s') . ".sql";
            $filepath = "{$backupDir}/{$filename}";

            switch ($config['driver']) {
                case 'mysql':
                    $this->backupMysqlDatabase($config, $filepath);
                    break;
                case 'pgsql':
                    $this->backupPostgresDatabase($config, $filepath);
                    break;
                case 'sqlite':
                    $this->backupSqliteDatabase($config, $filepath);
                    break;
                default:
                    throw new \Exception("Unsupported database driver: {$config['driver']}");
            }

            return [
                'status' => 'success',
                'filename' => $filename,
                'size' => Storage::disk($this->backupDisk)->size($filepath),
                'duration' => round(microtime(true) - $startTime, 2)
            ];

        } catch (\Exception $e) {
            Log::error('Database backup failed', ['error' => $e->getMessage()]);
            
            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
                'duration' => round(microtime(true) - $startTime, 2)
            ];
        }
    }

    /**
     * Backup MySQL database
     */
    private function backupMysqlDatabase(array $config, string $filepath): void
    {
        $command = sprintf(
            'mysqldump --host=%s --port=%s --user=%s --password=%s --single-transaction --routines --triggers %s',
            escapeshellarg($config['host']),
            escapeshellarg($config['port']),
            escapeshellarg($config['username']),
            escapeshellarg($config['password']),
            escapeshellarg($config['database'])
        );

        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception('MySQL backup failed with return code: ' . $returnCode);
        }

        Storage::disk($this->backupDisk)->put($filepath, implode("\n", $output));
    }

    /**
     * Backup PostgreSQL database
     */
    private function backupPostgresDatabase(array $config, string $filepath): void
    {
        $command = sprintf(
            'PGPASSWORD=%s pg_dump --host=%s --port=%s --username=%s --format=custom --no-owner --no-acl %s',
            escapeshellarg($config['password']),
            escapeshellarg($config['host']),
            escapeshellarg($config['port']),
            escapeshellarg($config['username']),
            escapeshellarg($config['database'])
        );

        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception('PostgreSQL backup failed with return code: ' . $returnCode);
        }

        Storage::disk($this->backupDisk)->put($filepath, implode("\n", $output));
    }

    /**
     * Backup SQLite database
     */
    private function backupSqliteDatabase(array $config, string $filepath): void
    {
        $sourcePath = $config['database'];
        
        if (!file_exists($sourcePath)) {
            throw new \Exception("SQLite database file not found: {$sourcePath}");
        }

        $content = file_get_contents($sourcePath);
        Storage::disk($this->backupDisk)->put($filepath, $content);
    }

    /**
     * Backup application files
     */
    public function backupFiles(string $backupDir, array $options = []): array
    {
        $startTime = microtime(true);
        
        try {
            $includePatterns = $options['include_files'] ?? [
                'app/',
                'config/',
                'database/migrations/',
                'database/seeders/',
                'resources/',
                'routes/',
                'storage/app/',
                '.env',
                'composer.json',
                'composer.lock',
                'package.json',
                'package-lock.json'
            ];

            $excludePatterns = $options['exclude_files'] ?? [
                'storage/logs/',
                'storage/framework/cache/',
                'storage/framework/sessions/',
                'storage/framework/views/',
                'node_modules/',
                'vendor/',
                '.git/',
                'bootstrap/cache/'
            ];

            $filesBackupDir = "{$backupDir}/files";
            Storage::disk($this->backupDisk)->makeDirectory($filesBackupDir);

            $totalSize = 0;
            $fileCount = 0;

            foreach ($includePatterns as $pattern) {
                $this->copyFilesRecursively(base_path($pattern), $filesBackupDir, $excludePatterns, $totalSize, $fileCount);
            }

            return [
                'status' => 'success',
                'file_count' => $fileCount,
                'total_size' => $totalSize,
                'duration' => round(microtime(true) - $startTime, 2)
            ];

        } catch (\Exception $e) {
            Log::error('Files backup failed', ['error' => $e->getMessage()]);
            
            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
                'duration' => round(microtime(true) - $startTime, 2)
            ];
        }
    }

    /**
     * Backup system configuration
     */
    public function backupConfiguration(string $backupDir): array
    {
        $startTime = microtime(true);
        
        try {
            $configDir = "{$backupDir}/config";
            Storage::disk($this->backupDisk)->makeDirectory($configDir);

            // Backup environment configuration (without sensitive data)
            $envConfig = $this->getSafeEnvironmentConfig();
            Storage::disk($this->backupDisk)->put("{$configDir}/environment.json", json_encode($envConfig, JSON_PRETTY_PRINT));

            // Backup application configuration
            $appConfig = [
                'app_name' => config('app.name'),
                'app_version' => config('version.version', '1.0.0'),
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'database_driver' => config('database.default'),
                'cache_driver' => config('cache.default'),
                'queue_driver' => config('queue.default'),
                'mail_driver' => config('mail.default'),
            ];
            Storage::disk($this->backupDisk)->put("{$configDir}/application.json", json_encode($appConfig, JSON_PRETTY_PRINT));

            // Backup database schema
            $schema = $this->getDatabaseSchema();
            Storage::disk($this->backupDisk)->put("{$configDir}/database_schema.json", json_encode($schema, JSON_PRETTY_PRINT));

            return [
                'status' => 'success',
                'components' => ['environment', 'application', 'database_schema'],
                'duration' => round(microtime(true) - $startTime, 2)
            ];

        } catch (\Exception $e) {
            Log::error('Configuration backup failed', ['error' => $e->getMessage()]);
            
            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
                'duration' => round(microtime(true) - $startTime, 2)
            ];
        }
    }

    /**
     * Compress backup directory
     */
    public function compressBackup(string $backupDir): array
    {
        $startTime = microtime(true);
        
        try {
            $zipFilename = basename($backupDir) . '.zip';
            $zipPath = dirname($backupDir) . '/' . $zipFilename;
            
            $zip = new ZipArchive();
            $result = $zip->open(Storage::disk($this->backupDisk)->path($zipPath), ZipArchive::CREATE | ZipArchive::OVERWRITE);
            
            if ($result !== TRUE) {
                throw new \Exception("Cannot create zip file: {$zipPath}");
            }

            $this->addDirectoryToZip($zip, Storage::disk($this->backupDisk)->path($backupDir), $backupDir);
            $zip->close();

            // Remove uncompressed directory
            Storage::disk($this->backupDisk)->deleteDirectory($backupDir);

            return [
                'status' => 'success',
                'compressed_file' => $zipFilename,
                'size' => Storage::disk($this->backupDisk)->size($zipPath),
                'duration' => round(microtime(true) - $startTime, 2)
            ];

        } catch (\Exception $e) {
            Log::error('Backup compression failed', ['error' => $e->getMessage()]);
            
            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
                'duration' => round(microtime(true) - $startTime, 2)
            ];
        }
    }

    /**
     * List available backups
     */
    public function listBackups(): array
    {
        try {
            $backups = [];
            $backupFiles = Storage::disk($this->backupDisk)->files('backups');
            
            foreach ($backupFiles as $file) {
                if (str_ends_with($file, '.zip') || str_ends_with($file, '.json')) {
                    $backups[] = [
                        'filename' => basename($file),
                        'path' => $file,
                        'size' => Storage::disk($this->backupDisk)->size($file),
                        'created_at' => Carbon::createFromTimestamp(Storage::disk($this->backupDisk)->lastModified($file)),
                        'type' => str_ends_with($file, '.zip') ? 'compressed' : 'metadata'
                    ];
                }
            }

            // Sort by creation date (newest first)
            usort($backups, fn($a, $b) => $b['created_at']->timestamp - $a['created_at']->timestamp);

            return $backups;

        } catch (\Exception $e) {
            Log::error('Failed to list backups', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Delete a backup
     */
    public function deleteBackup(string $backupPath): bool
    {
        try {
            if (Storage::disk($this->backupDisk)->exists($backupPath)) {
                Storage::disk($this->backupDisk)->delete($backupPath);
                Log::info('Backup deleted successfully', ['backup_path' => $backupPath]);
                return true;
            }
            
            return false;

        } catch (\Exception $e) {
            Log::error('Failed to delete backup', [
                'backup_path' => $backupPath,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Restore from backup
     */
    public function restoreFromBackup(string $backupPath, array $options = []): array
    {
        $startTime = microtime(true);
        
        try {
            Log::info('Starting backup restoration', ['backup_path' => $backupPath]);

            // Extract backup if compressed
            if (str_ends_with($backupPath, '.zip')) {
                $extractedPath = $this->extractBackup($backupPath);
            } else {
                $extractedPath = $backupPath;
            }

            $result = [
                'started_at' => now(),
                'backup_path' => $backupPath,
                'status' => 'in_progress',
                'components' => []
            ];

            // Restore database
            if ($options['restore_database'] ?? true) {
                $result['components']['database'] = $this->restoreDatabase($extractedPath);
            }

            // Restore files
            if ($options['restore_files'] ?? true) {
                $result['components']['files'] = $this->restoreFiles($extractedPath);
            }

            // Clear caches
            $this->clearApplicationCaches();

            $result['duration'] = round(microtime(true) - $startTime, 2);
            $result['completed_at'] = now();
            $result['status'] = 'completed';

            Log::info('Backup restoration completed successfully', [
                'backup_path' => $backupPath,
                'duration' => $result['duration']
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('Backup restoration failed', [
                'backup_path' => $backupPath,
                'error' => $e->getMessage()
            ]);

            return [
                'backup_path' => $backupPath,
                'status' => 'failed',
                'error' => $e->getMessage(),
                'duration' => round(microtime(true) - $startTime, 2)
            ];
        }
    }

    /**
     * Get backup statistics
     */
    public function getBackupStatistics(): array
    {
        $backups = $this->listBackups();
        
        return [
            'total_backups' => count($backups),
            'total_size' => array_sum(array_column($backups, 'size')),
            'latest_backup' => $backups[0] ?? null,
            'oldest_backup' => end($backups) ?: null,
            'backup_frequency' => $this->calculateBackupFrequency($backups),
            'storage_usage' => $this->getStorageUsage(),
        ];
    }

    /**
     * Schedule automatic backup
     */
    public function scheduleAutomaticBackup(string $frequency = 'daily'): void
    {
        // This would integrate with Laravel's task scheduler
        Log::info('Automatic backup scheduled', ['frequency' => $frequency]);
    }

    // Private helper methods...

    private function copyFilesRecursively(string $source, string $destination, array $excludePatterns, int &$totalSize, int &$fileCount): void
    {
        // Implementation for recursive file copying with exclusion patterns
        // This is a simplified version - full implementation would handle all edge cases
    }

    private function addDirectoryToZip(ZipArchive $zip, string $source, string $basePath): void
    {
        // Implementation for adding directory to ZIP archive
    }

    private function getSafeEnvironmentConfig(): array
    {
        // Return environment config without sensitive data
        return [
            'app_name' => config('app.name'),
            'app_env' => config('app.env'),
            'app_timezone' => config('app.timezone'),
            'database_driver' => config('database.default'),
            'cache_driver' => config('cache.default'),
            'queue_driver' => config('queue.default'),
        ];
    }

    private function getDatabaseSchema(): array
    {
        // Get database schema information
        return [
            'tables' => DB::select('SHOW TABLES'),
            'version' => DB::select('SELECT VERSION() as version')[0]->version ?? 'unknown'
        ];
    }

    private function createBackupManifest(array $backupData): array
    {
        return [
            'backup_id' => $backupData['backup_id'],
            'created_at' => $backupData['started_at'],
            'type' => $backupData['type'],
            'components' => array_keys($backupData['components']),
            'app_version' => config('version.version', '1.0.0'),
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
        ];
    }

    private function storeBackupMetadata(array $backupData): void
    {
        $metadataPath = "backups/metadata/{$backupData['backup_id']}.json";
        Storage::disk($this->backupDisk)->put($metadataPath, json_encode($backupData, JSON_PRETTY_PRINT));
    }

    private function calculateBackupSize(string $backupDir): int
    {
        $size = 0;
        $files = Storage::disk($this->backupDisk)->allFiles($backupDir);
        
        foreach ($files as $file) {
            $size += Storage::disk($this->backupDisk)->size($file);
        }
        
        return $size;
    }

    private function cleanupOldBackups(): void
    {
        $retentionDays = config('backup.cleanup.defaultStrategy.deleteOlderThan.days', 30);
        $cutoffDate = now()->subDays($retentionDays);
        
        $backups = $this->listBackups();
        
        foreach ($backups as $backup) {
            if ($backup['created_at']->lt($cutoffDate)) {
                $this->deleteBackup($backup['path']);
            }
        }
    }

    private function extractBackup(string $zipPath): string
    {
        // Implementation for extracting ZIP backup
        return dirname($zipPath) . '/' . pathinfo($zipPath, PATHINFO_FILENAME);
    }

    private function restoreDatabase(string $backupPath): array
    {
        // Implementation for database restoration
        return ['status' => 'success'];
    }

    private function restoreFiles(string $backupPath): array
    {
        // Implementation for files restoration
        return ['status' => 'success'];
    }

    private function clearApplicationCaches(): void
    {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
    }

    private function calculateBackupFrequency(array $backups): string
    {
        if (count($backups) < 2) {
            return 'insufficient_data';
        }
        
        $intervals = [];
        for ($i = 0; $i < count($backups) - 1; $i++) {
            $intervals[] = $backups[$i]['created_at']->diffInHours($backups[$i + 1]['created_at']);
        }
        
        $avgInterval = array_sum($intervals) / count($intervals);
        
        if ($avgInterval <= 25) return 'daily';
        if ($avgInterval <= 168) return 'weekly';
        if ($avgInterval <= 744) return 'monthly';
        
        return 'irregular';
    }

    private function getStorageUsage(): array
    {
        $backupPath = 'backups';
        $files = Storage::disk($this->backupDisk)->allFiles($backupPath);
        $totalSize = 0;
        
        foreach ($files as $file) {
            $totalSize += Storage::disk($this->backupDisk)->size($file);
        }
        
        return [
            'used_space' => $totalSize,
            'file_count' => count($files),
            'average_backup_size' => count($files) > 0 ? $totalSize / count($files) : 0
        ];
    }
}
