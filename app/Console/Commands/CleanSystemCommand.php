<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class CleanSystemCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:clean 
                            {--logs : Clean old log files}
                            {--temp : Clean temporary files}
                            {--cache : Clean cache files}
                            {--sessions : Clean expired sessions}
                            {--failed-jobs : Clean old failed jobs}
                            {--all : Clean everything}
                            {--dry-run : Show what would be cleaned without actually cleaning}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean system files and database records';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧹 Starting system cleanup...');
        $this->newLine();

        $dryRun = $this->option('dry-run');
        $all = $this->option('all');

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No files will actually be deleted');
            $this->newLine();
        }

        $cleaned = [];

        // Clean logs
        if ($all || $this->option('logs')) {
            $cleaned['logs'] = $this->cleanLogs($dryRun);
        }

        // Clean temporary files
        if ($all || $this->option('temp')) {
            $cleaned['temp'] = $this->cleanTempFiles($dryRun);
        }

        // Clean cache
        if ($all || $this->option('cache')) {
            $cleaned['cache'] = $this->cleanCache($dryRun);
        }

        // Clean expired sessions
        if ($all || $this->option('sessions')) {
            $cleaned['sessions'] = $this->cleanSessions($dryRun);
        }

        // Clean failed jobs
        if ($all || $this->option('failed-jobs')) {
            $cleaned['failed_jobs'] = $this->cleanFailedJobs($dryRun);
        }

        // Show summary
        $this->showSummary($cleaned, $dryRun);

        return 0;
    }

    /**
     * Clean old log files
     */
    private function cleanLogs(bool $dryRun): array
    {
        $this->info('📄 Cleaning log files...');

        $logPath = storage_path('logs');
        $retentionDays = config('logging.retention_days', 90);
        $cutoffDate = Carbon::now()->subDays($retentionDays);

        $stats = ['files' => 0, 'size' => 0];

        if (!is_dir($logPath)) {
            $this->warn('Log directory not found');
            return $stats;
        }

        $files = glob($logPath . '/*.log*');

        foreach ($files as $file) {
            $fileTime = Carbon::createFromTimestamp(filemtime($file));
            
            if ($fileTime->lt($cutoffDate)) {
                $fileSize = filesize($file);
                $stats['files']++;
                $stats['size'] += $fileSize;

                $this->line("  - " . basename($file) . " (" . $this->formatBytes($fileSize) . ")");

                if (!$dryRun) {
                    unlink($file);
                }
            }
        }

        if ($stats['files'] === 0) {
            $this->info('  No old log files to clean');
        }

        return $stats;
    }

    /**
     * Clean temporary files
     */
    private function cleanTempFiles(bool $dryRun): array
    {
        $this->info('🗂️  Cleaning temporary files...');

        $tempPaths = [
            storage_path('app/temp'),
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
        ];

        $stats = ['files' => 0, 'size' => 0];

        foreach ($tempPaths as $path) {
            if (!is_dir($path)) {
                continue;
            }

            $files = $this->getFilesRecursive($path);
            
            foreach ($files as $file) {
                // Skip files modified in the last hour
                if (filemtime($file) > time() - 3600) {
                    continue;
                }

                $fileSize = filesize($file);
                $stats['files']++;
                $stats['size'] += $fileSize;

                $relativePath = str_replace(storage_path(), '', $file);
                $this->line("  - " . $relativePath . " (" . $this->formatBytes($fileSize) . ")");

                if (!$dryRun) {
                    unlink($file);
                }
            }
        }

        if ($stats['files'] === 0) {
            $this->info('  No temporary files to clean');
        }

        return $stats;
    }

    /**
     * Clean cache files
     */
    private function cleanCache(bool $dryRun): array
    {
        $this->info('💾 Cleaning cache...');

        $stats = ['files' => 0, 'size' => 0];

        if (!$dryRun) {
            // Clear Laravel caches
            $this->call('cache:clear');
            $this->call('config:clear');
            $this->call('route:clear');
            $this->call('view:clear');
            
            $stats['files'] = 1; // Indicate that cache was cleared
        } else {
            $this->line('  - Would clear Laravel caches (cache, config, route, view)');
        }

        return $stats;
    }

    /**
     * Clean expired sessions
     */
    private function cleanSessions(bool $dryRun): array
    {
        $this->info('🔐 Cleaning expired sessions...');

        $stats = ['records' => 0];

        try {
            if (config('session.driver') === 'database') {
                $expiredCount = DB::table('sessions')
                    ->where('last_activity', '<', time() - config('session.lifetime') * 60)
                    ->count();

                $stats['records'] = $expiredCount;

                if ($expiredCount > 0) {
                    $this->line("  - {$expiredCount} expired sessions");

                    if (!$dryRun) {
                        DB::table('sessions')
                            ->where('last_activity', '<', time() - config('session.lifetime') * 60)
                            ->delete();
                    }
                } else {
                    $this->info('  No expired sessions to clean');
                }
            } else {
                $this->info('  Session driver is not database, skipping');
            }
        } catch (\Exception $e) {
            $this->error('  Error cleaning sessions: ' . $e->getMessage());
        }

        return $stats;
    }

    /**
     * Clean old failed jobs
     */
    private function cleanFailedJobs(bool $dryRun): array
    {
        $this->info('❌ Cleaning old failed jobs...');

        $stats = ['records' => 0];

        try {
            $retentionDays = 30;
            $cutoffDate = Carbon::now()->subDays($retentionDays);

            $failedCount = DB::table('failed_jobs')
                ->where('failed_at', '<', $cutoffDate)
                ->count();

            $stats['records'] = $failedCount;

            if ($failedCount > 0) {
                $this->line("  - {$failedCount} old failed jobs");

                if (!$dryRun) {
                    DB::table('failed_jobs')
                        ->where('failed_at', '<', $cutoffDate)
                        ->delete();
                }
            } else {
                $this->info('  No old failed jobs to clean');
            }
        } catch (\Exception $e) {
            $this->error('  Error cleaning failed jobs: ' . $e->getMessage());
        }

        return $stats;
    }

    /**
     * Get files recursively
     */
    private function getFilesRecursive(string $directory): array
    {
        $files = [];

        if (!is_dir($directory)) {
            return $files;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
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
     * Show cleanup summary
     */
    private function showSummary(array $cleaned, bool $dryRun): void
    {
        $this->newLine();
        $this->info('📊 Cleanup Summary:');
        $this->newLine();

        $totalFiles = 0;
        $totalSize = 0;
        $totalRecords = 0;

        foreach ($cleaned as $type => $stats) {
            if (isset($stats['files'])) {
                $totalFiles += $stats['files'];
            }
            if (isset($stats['size'])) {
                $totalSize += $stats['size'];
            }
            if (isset($stats['records'])) {
                $totalRecords += $stats['records'];
            }

            switch ($type) {
                case 'logs':
                    if ($stats['files'] > 0) {
                        $this->line("📄 Log files: {$stats['files']} files ({$this->formatBytes($stats['size'])})");
                    }
                    break;
                case 'temp':
                    if ($stats['files'] > 0) {
                        $this->line("🗂️  Temp files: {$stats['files']} files ({$this->formatBytes($stats['size'])})");
                    }
                    break;
                case 'cache':
                    if ($stats['files'] > 0) {
                        $this->line("💾 Cache: Cleared");
                    }
                    break;
                case 'sessions':
                    if ($stats['records'] > 0) {
                        $this->line("🔐 Sessions: {$stats['records']} records");
                    }
                    break;
                case 'failed_jobs':
                    if ($stats['records'] > 0) {
                        $this->line("❌ Failed jobs: {$stats['records']} records");
                    }
                    break;
            }
        }

        $this->newLine();
        
        if ($totalFiles > 0 || $totalRecords > 0) {
            $this->info("✅ Total: {$totalFiles} files ({$this->formatBytes($totalSize)}), {$totalRecords} database records");
            
            if ($dryRun) {
                $this->warn('🔍 This was a dry run - no files were actually deleted');
                $this->info('💡 Run without --dry-run to perform the actual cleanup');
            } else {
                $this->info('🎉 Cleanup completed successfully!');
            }
        } else {
            $this->info('✨ System is already clean - nothing to remove');
        }
    }
}
