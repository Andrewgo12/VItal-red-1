<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\MetricaSistema;
use App\Models\Notification;

class SystemCleanupCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'system:cleanup 
                            {--dry-run : Show what would be cleaned without actually doing it}
                            {--force : Force cleanup without confirmation}
                            {--logs : Clean old log files}
                            {--metrics : Clean old metrics}
                            {--notifications : Clean old notifications}
                            {--temp : Clean temporary files}
                            {--cache : Clean cache files}
                            {--sessions : Clean expired sessions}
                            {--all : Clean everything}';

    /**
     * The console command description.
     */
    protected $description = 'Clean up old system data, logs, and temporary files';

    private bool $dryRun = false;
    private array $cleanupStats = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->dryRun = $this->option('dry-run');
        
        $this->info('🧹 System Cleanup Utility');
        $this->info('========================');
        
        if ($this->dryRun) {
            $this->warn('DRY RUN MODE - No actual cleanup will be performed');
        }

        try {
            $this->initializeStats();

            // Determine what to clean
            $cleanAll = $this->option('all');
            
            if ($cleanAll || $this->option('logs')) {
                $this->cleanLogFiles();
            }
            
            if ($cleanAll || $this->option('metrics')) {
                $this->cleanOldMetrics();
            }
            
            if ($cleanAll || $this->option('notifications')) {
                $this->cleanOldNotifications();
            }
            
            if ($cleanAll || $this->option('temp')) {
                $this->cleanTemporaryFiles();
            }
            
            if ($cleanAll || $this->option('cache')) {
                $this->cleanCacheFiles();
            }
            
            if ($cleanAll || $this->option('sessions')) {
                $this->cleanExpiredSessions();
            }

            // If no specific options, ask user what to clean
            if (!$cleanAll && !$this->hasCleanupOptions()) {
                $this->interactiveCleanup();
            }

            $this->displayCleanupSummary();
            
            if (!$this->dryRun) {
                Log::info('System cleanup completed', $this->cleanupStats);
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Cleanup failed: ' . $e->getMessage());
            Log::error('System cleanup failed', ['error' => $e->getMessage()]);
            return self::FAILURE;
        }
    }

    /**
     * Initialize cleanup statistics
     */
    private function initializeStats(): void
    {
        $this->cleanupStats = [
            'logs' => ['files' => 0, 'size' => 0],
            'metrics' => ['records' => 0, 'size' => 0],
            'notifications' => ['records' => 0, 'size' => 0],
            'temp_files' => ['files' => 0, 'size' => 0],
            'cache' => ['files' => 0, 'size' => 0],
            'sessions' => ['records' => 0, 'size' => 0],
            'total_freed' => 0,
        ];
    }

    /**
     * Check if any cleanup options are specified
     */
    private function hasCleanupOptions(): bool
    {
        return $this->option('logs') || 
               $this->option('metrics') || 
               $this->option('notifications') || 
               $this->option('temp') || 
               $this->option('cache') || 
               $this->option('sessions');
    }

    /**
     * Interactive cleanup selection
     */
    private function interactiveCleanup(): void
    {
        $this->info('Select cleanup operations:');
        
        $choices = [
            'logs' => 'Clean old log files (older than 30 days)',
            'metrics' => 'Clean old metrics (older than 90 days)',
            'notifications' => 'Clean old notifications (older than 30 days)',
            'temp' => 'Clean temporary files',
            'cache' => 'Clean cache files',
            'sessions' => 'Clean expired sessions',
        ];

        foreach ($choices as $key => $description) {
            if ($this->confirm($description)) {
                $this->{"clean" . ucfirst($key === 'temp' ? 'TemporaryFiles' : ($key === 'sessions' ? 'ExpiredSessions' : ucfirst($key)))}();
            }
        }
    }

    /**
     * Clean old log files
     */
    private function cleanLogFiles(): void
    {
        $this->info('🗂️ Cleaning log files...');
        
        $logPath = storage_path('logs');
        $cutoffDate = now()->subDays(30);
        
        if (!is_dir($logPath)) {
            $this->warn('Log directory not found');
            return;
        }

        $files = glob($logPath . '/*.log*');
        $cleanedFiles = 0;
        $freedSpace = 0;

        foreach ($files as $file) {
            $fileTime = Carbon::createFromTimestamp(filemtime($file));
            
            if ($fileTime->lt($cutoffDate)) {
                $fileSize = filesize($file);
                
                if (!$this->dryRun) {
                    if (unlink($file)) {
                        $cleanedFiles++;
                        $freedSpace += $fileSize;
                    }
                } else {
                    $cleanedFiles++;
                    $freedSpace += $fileSize;
                    $this->line("  Would delete: " . basename($file) . " (" . $this->formatBytes($fileSize) . ")");
                }
            }
        }

        $this->cleanupStats['logs'] = [
            'files' => $cleanedFiles,
            'size' => $freedSpace
        ];

        $this->line("  Cleaned {$cleanedFiles} log files (" . $this->formatBytes($freedSpace) . ")");
    }

    /**
     * Clean old metrics
     */
    private function cleanOldMetrics(): void
    {
        $this->info('📊 Cleaning old metrics...');
        
        $cutoffDate = now()->subDays(90);
        
        $query = MetricaSistema::where('timestamp', '<', $cutoffDate);
        $recordCount = $query->count();
        
        if ($recordCount === 0) {
            $this->line('  No old metrics to clean');
            return;
        }

        if (!$this->dryRun) {
            $deleted = $query->delete();
            $this->cleanupStats['metrics']['records'] = $deleted;
        } else {
            $this->line("  Would delete {$recordCount} metric records");
            $this->cleanupStats['metrics']['records'] = $recordCount;
        }

        $this->line("  Cleaned {$recordCount} metric records");
    }

    /**
     * Clean old notifications
     */
    private function cleanOldNotifications(): void
    {
        $this->info('🔔 Cleaning old notifications...');
        
        $cutoffDate = now()->subDays(30);
        
        try {
            $query = Notification::where('created_at', '<', $cutoffDate);
            $recordCount = $query->count();
            
            if ($recordCount === 0) {
                $this->line('  No old notifications to clean');
                return;
            }

            if (!$this->dryRun) {
                $deleted = $query->delete();
                $this->cleanupStats['notifications']['records'] = $deleted;
            } else {
                $this->line("  Would delete {$recordCount} notification records");
                $this->cleanupStats['notifications']['records'] = $recordCount;
            }

            $this->line("  Cleaned {$recordCount} notification records");
            
        } catch (\Exception $e) {
            $this->warn('  Could not clean notifications: ' . $e->getMessage());
        }
    }

    /**
     * Clean temporary files
     */
    private function cleanTemporaryFiles(): void
    {
        $this->info('🗑️ Cleaning temporary files...');
        
        $tempPaths = [
            storage_path('app/temp'),
            storage_path('framework/cache/data'),
            storage_path('framework/views'),
            sys_get_temp_dir() . '/vital_red_*',
        ];

        $cleanedFiles = 0;
        $freedSpace = 0;

        foreach ($tempPaths as $path) {
            if (str_contains($path, '*')) {
                $files = glob($path);
            } else {
                $files = is_dir($path) ? glob($path . '/*') : [];
            }

            foreach ($files as $file) {
                if (is_file($file)) {
                    $fileSize = filesize($file);
                    $fileAge = Carbon::createFromTimestamp(filemtime($file));
                    
                    // Clean files older than 1 day
                    if ($fileAge->lt(now()->subDay())) {
                        if (!$this->dryRun) {
                            if (unlink($file)) {
                                $cleanedFiles++;
                                $freedSpace += $fileSize;
                            }
                        } else {
                            $cleanedFiles++;
                            $freedSpace += $fileSize;
                            $this->line("  Would delete: " . basename($file));
                        }
                    }
                }
            }
        }

        $this->cleanupStats['temp_files'] = [
            'files' => $cleanedFiles,
            'size' => $freedSpace
        ];

        $this->line("  Cleaned {$cleanedFiles} temporary files (" . $this->formatBytes($freedSpace) . ")");
    }

    /**
     * Clean cache files
     */
    private function cleanCacheFiles(): void
    {
        $this->info('💾 Cleaning cache files...');
        
        $cachePaths = [
            storage_path('framework/cache'),
            storage_path('app/cache'),
        ];

        $cleanedFiles = 0;
        $freedSpace = 0;

        foreach ($cachePaths as $cachePath) {
            if (!is_dir($cachePath)) {
                continue;
            }

            $files = $this->getFilesRecursively($cachePath);
            
            foreach ($files as $file) {
                if (basename($file) === '.gitignore') {
                    continue;
                }

                $fileSize = filesize($file);
                
                if (!$this->dryRun) {
                    if (unlink($file)) {
                        $cleanedFiles++;
                        $freedSpace += $fileSize;
                    }
                } else {
                    $cleanedFiles++;
                    $freedSpace += $fileSize;
                }
            }
        }

        $this->cleanupStats['cache'] = [
            'files' => $cleanedFiles,
            'size' => $freedSpace
        ];

        $this->line("  Cleaned {$cleanedFiles} cache files (" . $this->formatBytes($freedSpace) . ")");
    }

    /**
     * Clean expired sessions
     */
    private function cleanExpiredSessions(): void
    {
        $this->info('🔐 Cleaning expired sessions...');
        
        try {
            $sessionLifetime = config('session.lifetime', 120) * 60; // Convert to seconds
            $cutoffTime = now()->subSeconds($sessionLifetime)->timestamp;
            
            $query = DB::table('sessions')->where('last_activity', '<', $cutoffTime);
            $recordCount = $query->count();
            
            if ($recordCount === 0) {
                $this->line('  No expired sessions to clean');
                return;
            }

            if (!$this->dryRun) {
                $deleted = $query->delete();
                $this->cleanupStats['sessions']['records'] = $deleted;
            } else {
                $this->line("  Would delete {$recordCount} expired sessions");
                $this->cleanupStats['sessions']['records'] = $recordCount;
            }

            $this->line("  Cleaned {$recordCount} expired sessions");
            
        } catch (\Exception $e) {
            $this->warn('  Could not clean sessions: ' . $e->getMessage());
        }
    }

    /**
     * Get files recursively from directory
     */
    private function getFilesRecursively(string $directory): array
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
     * Display cleanup summary
     */
    private function displayCleanupSummary(): void
    {
        $this->info('');
        $this->info('📋 Cleanup Summary');
        $this->info('==================');

        $totalFreed = 0;
        $totalFiles = 0;
        $totalRecords = 0;

        foreach ($this->cleanupStats as $category => $stats) {
            if ($category === 'total_freed') {
                continue;
            }

            if (isset($stats['files']) && $stats['files'] > 0) {
                $this->line("  {$category}: {$stats['files']} files (" . $this->formatBytes($stats['size']) . ")");
                $totalFiles += $stats['files'];
                $totalFreed += $stats['size'];
            }

            if (isset($stats['records']) && $stats['records'] > 0) {
                $this->line("  {$category}: {$stats['records']} records");
                $totalRecords += $stats['records'];
            }
        }

        $this->info('');
        $this->info("Total files cleaned: {$totalFiles}");
        $this->info("Total records cleaned: {$totalRecords}");
        $this->info("Total space freed: " . $this->formatBytes($totalFreed));

        if ($this->dryRun) {
            $this->warn('');
            $this->warn('This was a dry run. No actual cleanup was performed.');
            $this->warn('Run without --dry-run to perform the actual cleanup.');
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
}
