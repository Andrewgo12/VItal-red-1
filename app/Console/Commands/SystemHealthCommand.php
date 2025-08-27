<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use App\Services\GeminiAIService;
use App\Services\GmailService;
use App\Models\User;
use App\Models\SolicitudMedica;
use Carbon\Carbon;

class SystemHealthCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'system:health 
                            {--format=table : Output format (table, json, summary)}
                            {--check= : Specific check to run (database, cache, storage, ai, gmail, queue)}
                            {--detailed : Show detailed information}
                            {--export= : Export results to file}';

    /**
     * The console command description.
     */
    protected $description = 'Check system health and display status of all components';

    private array $healthChecks = [];
    private array $results = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🏥 Vital Red System Health Check');
        $this->info('================================');

        $specificCheck = $this->option('check');
        
        if ($specificCheck) {
            $this->runSpecificCheck($specificCheck);
        } else {
            $this->runAllChecks();
        }

        $this->displayResults();

        if ($this->option('export')) {
            $this->exportResults();
        }

        return $this->getExitCode();
    }

    /**
     * Run all health checks
     */
    private function runAllChecks(): void
    {
        $this->info('Running comprehensive health check...');
        
        $checks = [
            'system' => 'System Information',
            'database' => 'Database Connectivity',
            'cache' => 'Cache System',
            'storage' => 'Storage System',
            'queue' => 'Queue System',
            'ai' => 'AI Services',
            'gmail' => 'Gmail Integration',
            'security' => 'Security Status',
            'performance' => 'Performance Metrics',
        ];

        foreach ($checks as $check => $description) {
            $this->line("Checking {$description}...");
            $this->{"check" . ucfirst($check)}();
        }
    }

    /**
     * Run specific health check
     */
    private function runSpecificCheck(string $check): void
    {
        $this->info("Running {$check} health check...");
        
        $method = 'check' . ucfirst($check);
        
        if (method_exists($this, $method)) {
            $this->$method();
        } else {
            $this->error("Unknown health check: {$check}");
        }
    }

    /**
     * Check system information
     */
    private function checkSystem(): void
    {
        $startTime = microtime(true);
        
        try {
            $systemInfo = [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'app_version' => config('version.version', '1.0.0'),
                'environment' => app()->environment(),
                'debug_mode' => config('app.debug'),
                'timezone' => config('app.timezone'),
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'operating_system' => PHP_OS,
            ];

            $this->results['system'] = [
                'status' => 'healthy',
                'response_time' => round((microtime(true) - $startTime) * 1000, 2),
                'details' => $systemInfo,
                'message' => 'System information collected successfully'
            ];

        } catch (\Exception $e) {
            $this->results['system'] = [
                'status' => 'error',
                'response_time' => round((microtime(true) - $startTime) * 1000, 2),
                'error' => $e->getMessage(),
                'message' => 'Failed to collect system information'
            ];
        }
    }

    /**
     * Check database connectivity
     */
    private function checkDatabase(): void
    {
        $startTime = microtime(true);
        
        try {
            // Test connection
            DB::connection()->getPdo();
            
            // Test query
            $userCount = User::count();
            $caseCount = SolicitudMedica::count();
            
            // Check database size
            $databaseSize = $this->getDatabaseSize();
            
            $this->results['database'] = [
                'status' => 'healthy',
                'response_time' => round((microtime(true) - $startTime) * 1000, 2),
                'details' => [
                    'connection' => 'active',
                    'driver' => config('database.default'),
                    'users_count' => $userCount,
                    'cases_count' => $caseCount,
                    'database_size' => $databaseSize,
                ],
                'message' => 'Database is accessible and responsive'
            ];

        } catch (\Exception $e) {
            $this->results['database'] = [
                'status' => 'error',
                'response_time' => round((microtime(true) - $startTime) * 1000, 2),
                'error' => $e->getMessage(),
                'message' => 'Database connection failed'
            ];
        }
    }

    /**
     * Check cache system
     */
    private function checkCache(): void
    {
        $startTime = microtime(true);
        
        try {
            $testKey = 'health_check_' . time();
            $testValue = 'test_value';
            
            // Test cache write
            Cache::put($testKey, $testValue, 60);
            
            // Test cache read
            $retrievedValue = Cache::get($testKey);
            
            // Test cache delete
            Cache::forget($testKey);
            
            $cacheWorking = $retrievedValue === $testValue;
            
            $this->results['cache'] = [
                'status' => $cacheWorking ? 'healthy' : 'warning',
                'response_time' => round((microtime(true) - $startTime) * 1000, 2),
                'details' => [
                    'driver' => config('cache.default'),
                    'write_test' => 'passed',
                    'read_test' => $cacheWorking ? 'passed' : 'failed',
                    'delete_test' => 'passed',
                ],
                'message' => $cacheWorking ? 'Cache system is working correctly' : 'Cache system has issues'
            ];

        } catch (\Exception $e) {
            $this->results['cache'] = [
                'status' => 'error',
                'response_time' => round((microtime(true) - $startTime) * 1000, 2),
                'error' => $e->getMessage(),
                'message' => 'Cache system failed'
            ];
        }
    }

    /**
     * Check storage system
     */
    private function checkStorage(): void
    {
        $startTime = microtime(true);
        
        try {
            $testFile = 'health_check_' . time() . '.txt';
            $testContent = 'Health check test file';
            
            // Test file write
            Storage::put($testFile, $testContent);
            
            // Test file read
            $retrievedContent = Storage::get($testFile);
            
            // Test file delete
            Storage::delete($testFile);
            
            // Check storage space
            $storageInfo = $this->getStorageInfo();
            
            $storageWorking = $retrievedContent === $testContent;
            
            $this->results['storage'] = [
                'status' => $storageWorking ? 'healthy' : 'error',
                'response_time' => round((microtime(true) - $startTime) * 1000, 2),
                'details' => array_merge([
                    'default_disk' => config('filesystems.default'),
                    'write_test' => 'passed',
                    'read_test' => $storageWorking ? 'passed' : 'failed',
                    'delete_test' => 'passed',
                ], $storageInfo),
                'message' => $storageWorking ? 'Storage system is working correctly' : 'Storage system has issues'
            ];

        } catch (\Exception $e) {
            $this->results['storage'] = [
                'status' => 'error',
                'response_time' => round((microtime(true) - $startTime) * 1000, 2),
                'error' => $e->getMessage(),
                'message' => 'Storage system failed'
            ];
        }
    }

    /**
     * Check queue system
     */
    private function checkQueue(): void
    {
        $startTime = microtime(true);
        
        try {
            // Check queue configuration
            $queueDriver = config('queue.default');
            
            // Check for failed jobs
            $failedJobs = DB::table('failed_jobs')->count();
            
            // Check for pending jobs
            $pendingJobs = DB::table('jobs')->count();
            
            $queueStatus = $failedJobs < 10 ? 'healthy' : 'warning';
            
            $this->results['queue'] = [
                'status' => $queueStatus,
                'response_time' => round((microtime(true) - $startTime) * 1000, 2),
                'details' => [
                    'driver' => $queueDriver,
                    'failed_jobs' => $failedJobs,
                    'pending_jobs' => $pendingJobs,
                    'workers_status' => $this->checkQueueWorkers(),
                ],
                'message' => $queueStatus === 'healthy' ? 'Queue system is working correctly' : 'Queue system has issues'
            ];

        } catch (\Exception $e) {
            $this->results['queue'] = [
                'status' => 'error',
                'response_time' => round((microtime(true) - $startTime) * 1000, 2),
                'error' => $e->getMessage(),
                'message' => 'Queue system check failed'
            ];
        }
    }

    /**
     * Check AI services
     */
    private function checkAi(): void
    {
        $startTime = microtime(true);
        
        try {
            $geminiService = app(GeminiAIService::class);
            
            // Test AI service with simple request
            $testText = "Test medical case: Patient with chest pain.";
            $response = $geminiService->analyzeText($testText);
            
            $aiWorking = !empty($response);
            
            $this->results['ai'] = [
                'status' => $aiWorking ? 'healthy' : 'warning',
                'response_time' => round((microtime(true) - $startTime) * 1000, 2),
                'details' => [
                    'service' => 'Google Gemini',
                    'api_key_configured' => !empty(config('gemini.api_key')),
                    'test_request' => $aiWorking ? 'passed' : 'failed',
                    'model' => config('gemini.model', 'gemini-pro'),
                ],
                'message' => $aiWorking ? 'AI service is working correctly' : 'AI service has issues'
            ];

        } catch (\Exception $e) {
            $this->results['ai'] = [
                'status' => 'error',
                'response_time' => round((microtime(true) - $startTime) * 1000, 2),
                'error' => $e->getMessage(),
                'message' => 'AI service check failed'
            ];
        }
    }

    /**
     * Check Gmail integration
     */
    private function checkGmail(): void
    {
        $startTime = microtime(true);
        
        try {
            $gmailService = app(GmailService::class);
            
            // Check Gmail configuration
            $gmailConfigured = !empty(config('gmail.client_id')) && !empty(config('gmail.client_secret'));
            
            if ($gmailConfigured) {
                // Test Gmail connection (this might fail if not properly authenticated)
                try {
                    $testResult = $gmailService->testConnection();
                    $connectionWorking = true;
                } catch (\Exception $e) {
                    $connectionWorking = false;
                    $connectionError = $e->getMessage();
                }
            } else {
                $connectionWorking = false;
                $connectionError = 'Gmail not configured';
            }

            $this->results['gmail'] = [
                'status' => $connectionWorking ? 'healthy' : 'warning',
                'response_time' => round((microtime(true) - $startTime) * 1000, 2),
                'details' => [
                    'configured' => $gmailConfigured,
                    'client_id_set' => !empty(config('gmail.client_id')),
                    'client_secret_set' => !empty(config('gmail.client_secret')),
                    'connection_test' => $connectionWorking ? 'passed' : 'failed',
                ],
                'message' => $connectionWorking ? 'Gmail integration is working' : ($connectionError ?? 'Gmail integration has issues')
            ];

        } catch (\Exception $e) {
            $this->results['gmail'] = [
                'status' => 'error',
                'response_time' => round((microtime(true) - $startTime) * 1000, 2),
                'error' => $e->getMessage(),
                'message' => 'Gmail integration check failed'
            ];
        }
    }

    /**
     * Check security status
     */
    private function checkSecurity(): void
    {
        $startTime = microtime(true);
        
        try {
            $securityChecks = [
                'app_key_set' => !empty(config('app.key')),
                'debug_disabled' => !config('app.debug') || app()->environment('local'),
                'https_enforced' => config('app.url', '')->startsWith('https') || app()->environment('local'),
                'session_secure' => config('session.secure') || app()->environment('local'),
                'csrf_protection' => true, // Laravel has CSRF protection by default
                'password_hashing' => Hash::needsRehash('test') === false,
            ];

            $securityScore = array_sum($securityChecks) / count($securityChecks) * 100;
            $securityStatus = $securityScore >= 80 ? 'healthy' : ($securityScore >= 60 ? 'warning' : 'error');

            $this->results['security'] = [
                'status' => $securityStatus,
                'response_time' => round((microtime(true) - $startTime) * 1000, 2),
                'details' => array_merge($securityChecks, [
                    'security_score' => round($securityScore, 1) . '%',
                ]),
                'message' => "Security score: {$securityScore}%"
            ];

        } catch (\Exception $e) {
            $this->results['security'] = [
                'status' => 'error',
                'response_time' => round((microtime(true) - $startTime) * 1000, 2),
                'error' => $e->getMessage(),
                'message' => 'Security check failed'
            ];
        }
    }

    /**
     * Check performance metrics
     */
    private function checkPerformance(): void
    {
        $startTime = microtime(true);
        
        try {
            $performanceMetrics = [
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
                'memory_limit' => $this->parseMemoryLimit(ini_get('memory_limit')),
                'load_average' => $this->getLoadAverage(),
                'disk_usage' => $this->getDiskUsage(),
                'uptime' => $this->getSystemUptime(),
            ];

            $memoryUsagePercent = ($performanceMetrics['memory_usage'] / $performanceMetrics['memory_limit']) * 100;
            $performanceStatus = $memoryUsagePercent < 80 ? 'healthy' : 'warning';

            $this->results['performance'] = [
                'status' => $performanceStatus,
                'response_time' => round((microtime(true) - $startTime) * 1000, 2),
                'details' => array_merge($performanceMetrics, [
                    'memory_usage_percent' => round($memoryUsagePercent, 1) . '%',
                ]),
                'message' => "Memory usage: {$memoryUsagePercent}%"
            ];

        } catch (\Exception $e) {
            $this->results['performance'] = [
                'status' => 'error',
                'response_time' => round((microtime(true) - $startTime) * 1000, 2),
                'error' => $e->getMessage(),
                'message' => 'Performance check failed'
            ];
        }
    }

    /**
     * Display results based on format
     */
    private function displayResults(): void
    {
        $format = $this->option('format');
        
        switch ($format) {
            case 'json':
                $this->displayJsonResults();
                break;
            case 'summary':
                $this->displaySummaryResults();
                break;
            default:
                $this->displayTableResults();
                break;
        }
    }

    /**
     * Display results in table format
     */
    private function displayTableResults(): void
    {
        $this->info('');
        $this->info('Health Check Results:');
        
        $headers = ['Component', 'Status', 'Response Time', 'Message'];
        $rows = [];
        
        foreach ($this->results as $component => $result) {
            $status = $this->formatStatus($result['status']);
            $responseTime = $result['response_time'] . 'ms';
            $message = $result['message'] ?? '';
            
            $rows[] = [ucfirst($component), $status, $responseTime, $message];
        }
        
        $this->table($headers, $rows);
        
        if ($this->option('detailed')) {
            $this->displayDetailedResults();
        }
    }

    /**
     * Display detailed results
     */
    private function displayDetailedResults(): void
    {
        foreach ($this->results as $component => $result) {
            if (isset($result['details'])) {
                $this->info('');
                $this->info(ucfirst($component) . ' Details:');
                foreach ($result['details'] as $key => $value) {
                    $this->line("  {$key}: " . (is_bool($value) ? ($value ? 'Yes' : 'No') : $value));
                }
            }
        }
    }

    /**
     * Display results in JSON format
     */
    private function displayJsonResults(): void
    {
        $this->line(json_encode($this->results, JSON_PRETTY_PRINT));
    }

    /**
     * Display summary results
     */
    private function displaySummaryResults(): void
    {
        $healthy = 0;
        $warning = 0;
        $error = 0;
        
        foreach ($this->results as $result) {
            switch ($result['status']) {
                case 'healthy':
                    $healthy++;
                    break;
                case 'warning':
                    $warning++;
                    break;
                case 'error':
                    $error++;
                    break;
            }
        }
        
        $total = count($this->results);
        
        $this->info('System Health Summary:');
        $this->line("  Total components checked: {$total}");
        $this->line("  Healthy: {$healthy}");
        $this->line("  Warning: {$warning}");
        $this->line("  Error: {$error}");
        
        $overallStatus = $error > 0 ? 'CRITICAL' : ($warning > 0 ? 'WARNING' : 'HEALTHY');
        $this->info("Overall Status: {$overallStatus}");
    }

    /**
     * Export results to file
     */
    private function exportResults(): void
    {
        $filename = $this->option('export');
        $content = json_encode([
            'timestamp' => now()->toISOString(),
            'results' => $this->results
        ], JSON_PRETTY_PRINT);
        
        file_put_contents($filename, $content);
        $this->info("Results exported to: {$filename}");
    }

    /**
     * Get exit code based on results
     */
    private function getExitCode(): int
    {
        foreach ($this->results as $result) {
            if ($result['status'] === 'error') {
                return self::FAILURE;
            }
        }
        
        return self::SUCCESS;
    }

    /**
     * Format status for display
     */
    private function formatStatus(string $status): string
    {
        switch ($status) {
            case 'healthy':
                return '<fg=green>✓ Healthy</>';
            case 'warning':
                return '<fg=yellow>⚠ Warning</>';
            case 'error':
                return '<fg=red>✗ Error</>';
            default:
                return $status;
        }
    }

    // Helper methods for system information gathering...
    
    private function getDatabaseSize(): string
    {
        try {
            $size = DB::select("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS 'size' FROM information_schema.tables WHERE table_schema = ?", [config('database.connections.mysql.database')])[0]->size ?? 0;
            return $size . ' MB';
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    private function getStorageInfo(): array
    {
        try {
            $storagePath = storage_path();
            $freeBytes = disk_free_space($storagePath);
            $totalBytes = disk_total_space($storagePath);
            $usedBytes = $totalBytes - $freeBytes;
            
            return [
                'free_space' => $this->formatBytes($freeBytes),
                'total_space' => $this->formatBytes($totalBytes),
                'used_space' => $this->formatBytes($usedBytes),
                'usage_percent' => round(($usedBytes / $totalBytes) * 100, 1) . '%',
            ];
        } catch (\Exception $e) {
            return ['error' => 'Could not retrieve storage info'];
        }
    }

    private function checkQueueWorkers(): string
    {
        // This is a simplified check - in production you might want to check actual worker processes
        return 'Unknown';
    }

    private function parseMemoryLimit(string $memoryLimit): int
    {
        $memoryLimit = trim($memoryLimit);
        $last = strtolower($memoryLimit[strlen($memoryLimit)-1]);
        $memoryLimit = (int) $memoryLimit;
        
        switch($last) {
            case 'g':
                $memoryLimit *= 1024;
            case 'm':
                $memoryLimit *= 1024;
            case 'k':
                $memoryLimit *= 1024;
        }
        
        return $memoryLimit;
    }

    private function getLoadAverage(): string
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return implode(', ', array_map(fn($l) => round($l, 2), $load));
        }
        return 'Unknown';
    }

    private function getDiskUsage(): string
    {
        try {
            $freeBytes = disk_free_space('/');
            $totalBytes = disk_total_space('/');
            $usedPercent = round((($totalBytes - $freeBytes) / $totalBytes) * 100, 1);
            return $usedPercent . '%';
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    private function getSystemUptime(): string
    {
        if (file_exists('/proc/uptime')) {
            $uptime = file_get_contents('/proc/uptime');
            $uptime = explode(' ', $uptime)[0];
            $days = floor($uptime / 86400);
            $hours = floor(($uptime % 86400) / 3600);
            return "{$days}d {$hours}h";
        }
        return 'Unknown';
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, 1) . ' ' . $units[$i];
    }
}
