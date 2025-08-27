<?php

namespace App\Services;

use App\Models\MetricaSistema;
use App\Models\SolicitudMedica;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MetricsService
{
    private const CACHE_TTL = 300; // 5 minutes
    private const CACHE_PREFIX = 'metrics_';

    /**
     * Record a metric
     */
    public function recordMetric(string $name, $value, string $type = 'gauge', array $labels = [], Carbon $timestamp = null): void
    {
        try {
            MetricaSistema::create([
                'nombre_metrica' => $name,
                'valor' => $value,
                'tipo_metrica' => $type,
                'etiquetas' => $labels,
                'timestamp' => $timestamp ?? now(),
            ]);

            // Clear related cache
            $this->clearMetricCache($name);
        } catch (\Exception $e) {
            Log::error('Error recording metric', [
                'metric' => $name,
                'value' => $value,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Increment a counter metric
     */
    public function incrementCounter(string $name, array $labels = [], int $increment = 1): void
    {
        $this->recordMetric($name, $increment, 'counter', $labels);
    }

    /**
     * Record a histogram value
     */
    public function recordHistogram(string $name, float $value, array $labels = []): void
    {
        $this->recordMetric($name, $value, 'histogram', $labels);
    }

    /**
     * Set a gauge value
     */
    public function setGauge(string $name, float $value, array $labels = []): void
    {
        $this->recordMetric($name, $value, 'gauge', $labels);
    }

    /**
     * Get system metrics dashboard
     */
    public function getSystemMetrics(Carbon $startDate = null, Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(7);
        $endDate = $endDate ?? now();

        $cacheKey = self::CACHE_PREFIX . 'system_' . $startDate->format('Y-m-d') . '_' . $endDate->format('Y-m-d');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($startDate, $endDate) {
            return [
                'performance' => $this->getPerformanceMetrics($startDate, $endDate),
                'medical' => $this->getMedicalMetrics($startDate, $endDate),
                'ai' => $this->getAIMetrics($startDate, $endDate),
                'system_health' => $this->getSystemHealthMetrics(),
                'user_activity' => $this->getUserActivityMetrics($startDate, $endDate),
            ];
        });
    }

    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics(Carbon $startDate, Carbon $endDate): array
    {
        return [
            'response_times' => $this->getResponseTimeMetrics($startDate, $endDate),
            'throughput' => $this->getThroughputMetrics($startDate, $endDate),
            'error_rates' => $this->getErrorRateMetrics($startDate, $endDate),
            'resource_usage' => $this->getResourceUsageMetrics($startDate, $endDate),
        ];
    }

    /**
     * Get medical metrics
     */
    public function getMedicalMetrics(Carbon $startDate, Carbon $endDate): array
    {
        $cacheKey = self::CACHE_PREFIX . 'medical_' . $startDate->format('Y-m-d') . '_' . $endDate->format('Y-m-d');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($startDate, $endDate) {
            $totalCases = SolicitudMedica::whereBetween('fecha_recepcion_email', [$startDate, $endDate])->count();
            $pendingCases = SolicitudMedica::where('estado', 'pendiente_evaluacion')->count();
            $urgentCases = SolicitudMedica::where('prioridad_ia', 'Alta')
                ->where('estado', 'pendiente_evaluacion')->count();
            
            $evaluatedCases = SolicitudMedica::whereBetween('fecha_evaluacion', [$startDate, $endDate])->count();
            $acceptedCases = SolicitudMedica::whereBetween('fecha_evaluacion', [$startDate, $endDate])
                ->where('estado', 'aceptada')->count();

            $avgResponseTime = SolicitudMedica::whereBetween('fecha_evaluacion', [$startDate, $endDate])
                ->whereNotNull('fecha_evaluacion')
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, fecha_recepcion_email, fecha_evaluacion)) as avg_time')
                ->value('avg_time');

            return [
                'total_cases' => $totalCases,
                'pending_cases' => $pendingCases,
                'urgent_cases' => $urgentCases,
                'cases_today' => SolicitudMedica::whereDate('fecha_recepcion_email', today())->count(),
                'evaluations_today' => SolicitudMedica::whereDate('fecha_evaluacion', today())->count(),
                'acceptance_rate' => $evaluatedCases > 0 ? round(($acceptedCases / $evaluatedCases) * 100, 1) : 0,
                'avg_response_time' => round($avgResponseTime ?? 0, 1),
                'cases_by_specialty' => $this->getCasesBySpecialty($startDate, $endDate),
                'cases_by_priority' => $this->getCasesByPriority($startDate, $endDate),
                'cases_by_status' => $this->getCasesByStatus($startDate, $endDate),
                'temporal_trends' => $this->getTemporalTrends($startDate, $endDate),
            ];
        });
    }

    /**
     * Get AI metrics
     */
    public function getAIMetrics(Carbon $startDate, Carbon $endDate): array
    {
        $cacheKey = self::CACHE_PREFIX . 'ai_' . $startDate->format('Y-m-d') . '_' . $endDate->format('Y-m-d');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($startDate, $endDate) {
            $totalProcessed = SolicitudMedica::whereBetween('fecha_recepcion_email', [$startDate, $endDate])
                ->whereNotNull('analisis_ia')->count();
            
            $totalCases = SolicitudMedica::whereBetween('fecha_recepcion_email', [$startDate, $endDate])->count();
            
            $accuratePredictions = SolicitudMedica::whereBetween('fecha_recepcion_email', [$startDate, $endDate])
                ->whereNotNull('prioridad_medico')
                ->whereColumn('prioridad_ia', 'prioridad_medico')
                ->count();
            
            $totalEvaluated = SolicitudMedica::whereBetween('fecha_recepcion_email', [$startDate, $endDate])
                ->whereNotNull('prioridad_medico')->count();

            $avgConfidence = SolicitudMedica::whereBetween('fecha_recepcion_email', [$startDate, $endDate])
                ->whereNotNull('score_urgencia')
                ->avg('score_urgencia');

            return [
                'processing_rate' => $totalCases > 0 ? round(($totalProcessed / $totalCases) * 100, 1) : 0,
                'accuracy_rate' => $totalEvaluated > 0 ? round(($accuratePredictions / $totalEvaluated) * 100, 1) : 0,
                'avg_confidence' => round($avgConfidence ?? 0, 1),
                'total_processed' => $totalProcessed,
                'accurate_predictions' => $accuratePredictions,
                'processing_errors' => $this->getAIProcessingErrors($startDate, $endDate),
                'model_performance' => $this->getModelPerformance($startDate, $endDate),
            ];
        });
    }

    /**
     * Get system health metrics
     */
    public function getSystemHealthMetrics(): array
    {
        return [
            'database_status' => $this->checkDatabaseHealth(),
            'redis_status' => $this->checkRedisHealth(),
            'storage_status' => $this->checkStorageHealth(),
            'queue_status' => $this->checkQueueHealth(),
            'memory_usage' => $this->getMemoryUsage(),
            'disk_usage' => $this->getDiskUsage(),
        ];
    }

    /**
     * Get user activity metrics
     */
    public function getUserActivityMetrics(Carbon $startDate, Carbon $endDate): array
    {
        return [
            'active_users' => User::where('is_active', true)->count(),
            'total_doctors' => User::where('role', 'medico')->where('is_active', true)->count(),
            'total_admins' => User::where('role', 'administrador')->where('is_active', true)->count(),
            'login_activity' => $this->getLoginActivity($startDate, $endDate),
            'doctor_workload' => $this->getDoctorWorkload($startDate, $endDate),
        ];
    }

    /**
     * Get response time metrics
     */
    private function getResponseTimeMetrics(Carbon $startDate, Carbon $endDate): array
    {
        $metrics = MetricaSistema::where('nombre_metrica', 'response_time')
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->get();

        return [
            'avg' => $metrics->avg('valor'),
            'min' => $metrics->min('valor'),
            'max' => $metrics->max('valor'),
            'p95' => $metrics->sortBy('valor')->values()->get(intval($metrics->count() * 0.95))?->valor ?? 0,
            'p99' => $metrics->sortBy('valor')->values()->get(intval($metrics->count() * 0.99))?->valor ?? 0,
        ];
    }

    /**
     * Get throughput metrics
     */
    private function getThroughputMetrics(Carbon $startDate, Carbon $endDate): array
    {
        $requests = MetricaSistema::where('nombre_metrica', 'http_requests')
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->sum('valor');

        $hours = $startDate->diffInHours($endDate);
        
        return [
            'total_requests' => $requests,
            'requests_per_hour' => $hours > 0 ? round($requests / $hours, 2) : 0,
            'peak_hour' => $this->getPeakHour($startDate, $endDate),
        ];
    }

    /**
     * Get error rate metrics
     */
    private function getErrorRateMetrics(Carbon $startDate, Carbon $endDate): array
    {
        $totalRequests = MetricaSistema::where('nombre_metrica', 'http_requests')
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->sum('valor');

        $errorRequests = MetricaSistema::where('nombre_metrica', 'http_errors')
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->sum('valor');

        return [
            'total_errors' => $errorRequests,
            'error_rate' => $totalRequests > 0 ? round(($errorRequests / $totalRequests) * 100, 2) : 0,
            'error_types' => $this->getErrorTypes($startDate, $endDate),
        ];
    }

    /**
     * Get resource usage metrics
     */
    private function getResourceUsageMetrics(Carbon $startDate, Carbon $endDate): array
    {
        return [
            'cpu_usage' => $this->getAverageMetric('cpu_usage', $startDate, $endDate),
            'memory_usage' => $this->getAverageMetric('memory_usage', $startDate, $endDate),
            'disk_usage' => $this->getAverageMetric('disk_usage', $startDate, $endDate),
        ];
    }

    /**
     * Get cases by specialty
     */
    private function getCasesBySpecialty(Carbon $startDate, Carbon $endDate): array
    {
        return SolicitudMedica::whereBetween('fecha_recepcion_email', [$startDate, $endDate])
            ->select('especialidad_solicitada', DB::raw('count(*) as total'))
            ->groupBy('especialidad_solicitada')
            ->orderBy('total', 'desc')
            ->pluck('total', 'especialidad_solicitada')
            ->toArray();
    }

    /**
     * Get cases by priority
     */
    private function getCasesByPriority(Carbon $startDate, Carbon $endDate): array
    {
        return SolicitudMedica::whereBetween('fecha_recepcion_email', [$startDate, $endDate])
            ->select('prioridad_ia', DB::raw('count(*) as total'))
            ->groupBy('prioridad_ia')
            ->pluck('total', 'prioridad_ia')
            ->toArray();
    }

    /**
     * Get cases by status
     */
    private function getCasesByStatus(Carbon $startDate, Carbon $endDate): array
    {
        return SolicitudMedica::whereBetween('fecha_recepcion_email', [$startDate, $endDate])
            ->select('estado', DB::raw('count(*) as total'))
            ->groupBy('estado')
            ->pluck('total', 'estado')
            ->toArray();
    }

    /**
     * Get temporal trends
     */
    private function getTemporalTrends(Carbon $startDate, Carbon $endDate): array
    {
        $trends = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $dayStart = $currentDate->copy()->startOfDay();
            $dayEnd = $currentDate->copy()->endOfDay();
            
            $count = SolicitudMedica::whereBetween('fecha_recepcion_email', [$dayStart, $dayEnd])->count();
            
            $trends[] = [
                'date' => $currentDate->format('Y-m-d'),
                'label' => $currentDate->format('M d'),
                'count' => $count
            ];
            
            $currentDate->addDay();
        }

        return $trends;
    }

    /**
     * Check database health
     */
    private function checkDatabaseHealth(): array
    {
        try {
            DB::select('SELECT 1');
            return ['status' => 'healthy', 'response_time' => 0];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'error' => $e->getMessage()];
        }
    }

    /**
     * Check Redis health
     */
    private function checkRedisHealth(): array
    {
        try {
            Cache::put('health_check', 'ok', 1);
            $value = Cache::get('health_check');
            return ['status' => $value === 'ok' ? 'healthy' : 'unhealthy'];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'error' => $e->getMessage()];
        }
    }

    /**
     * Check storage health
     */
    private function checkStorageHealth(): array
    {
        try {
            $diskFree = disk_free_space(storage_path());
            $diskTotal = disk_total_space(storage_path());
            $usagePercent = round((($diskTotal - $diskFree) / $diskTotal) * 100, 1);
            
            return [
                'status' => $usagePercent < 90 ? 'healthy' : 'warning',
                'usage_percent' => $usagePercent,
                'free_space' => $diskFree,
                'total_space' => $diskTotal
            ];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'error' => $e->getMessage()];
        }
    }

    /**
     * Check queue health
     */
    private function checkQueueHealth(): array
    {
        try {
            $failedJobs = DB::table('failed_jobs')->count();
            $pendingJobs = DB::table('jobs')->count();
            
            return [
                'status' => $failedJobs < 10 ? 'healthy' : 'warning',
                'failed_jobs' => $failedJobs,
                'pending_jobs' => $pendingJobs
            ];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'error' => $e->getMessage()];
        }
    }

    /**
     * Get memory usage
     */
    private function getMemoryUsage(): array
    {
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        
        return [
            'current' => $memoryUsage,
            'peak' => $memoryPeak,
            'limit' => ini_get('memory_limit')
        ];
    }

    /**
     * Get disk usage
     */
    private function getDiskUsage(): array
    {
        $diskFree = disk_free_space('/');
        $diskTotal = disk_total_space('/');
        
        return [
            'free' => $diskFree,
            'total' => $diskTotal,
            'used_percent' => round((($diskTotal - $diskFree) / $diskTotal) * 100, 1)
        ];
    }

    /**
     * Get AI processing errors
     */
    private function getAIProcessingErrors(Carbon $startDate, Carbon $endDate): int
    {
        return MetricaSistema::where('nombre_metrica', 'ai_processing_error')
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->sum('valor');
    }

    /**
     * Get model performance
     */
    private function getModelPerformance(Carbon $startDate, Carbon $endDate): array
    {
        return [
            'avg_processing_time' => $this->getAverageMetric('ai_processing_time', $startDate, $endDate),
            'success_rate' => 95.5, // Placeholder
            'confidence_score' => 87.2, // Placeholder
        ];
    }

    /**
     * Get login activity
     */
    private function getLoginActivity(Carbon $startDate, Carbon $endDate): array
    {
        return [
            'total_logins' => $this->getSumMetric('user_login', $startDate, $endDate),
            'unique_users' => User::where('last_login_at', '>=', $startDate)->count(),
            'failed_attempts' => $this->getSumMetric('failed_login', $startDate, $endDate),
        ];
    }

    /**
     * Get doctor workload
     */
    private function getDoctorWorkload(Carbon $startDate, Carbon $endDate): array
    {
        return User::where('role', 'medico')
            ->where('is_active', true)
            ->withCount([
                'solicitudesEvaluadas' => function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('fecha_evaluacion', [$startDate, $endDate]);
                }
            ])
            ->get()
            ->map(function ($doctor) {
                return [
                    'name' => $doctor->name,
                    'cases_handled' => $doctor->solicitudes_evaluadas_count,
                    'workload_level' => $this->calculateWorkloadLevel($doctor->solicitudes_evaluadas_count)
                ];
            })
            ->toArray();
    }

    /**
     * Get average metric value
     */
    private function getAverageMetric(string $metricName, Carbon $startDate, Carbon $endDate): float
    {
        return MetricaSistema::where('nombre_metrica', $metricName)
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->avg('valor') ?? 0;
    }

    /**
     * Get sum metric value
     */
    private function getSumMetric(string $metricName, Carbon $startDate, Carbon $endDate): int
    {
        return MetricaSistema::where('nombre_metrica', $metricName)
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->sum('valor') ?? 0;
    }

    /**
     * Get peak hour
     */
    private function getPeakHour(Carbon $startDate, Carbon $endDate): int
    {
        $hourlyData = MetricaSistema::where('nombre_metrica', 'http_requests')
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->selectRaw('HOUR(timestamp) as hour, SUM(valor) as total')
            ->groupBy('hour')
            ->orderBy('total', 'desc')
            ->first();

        return $hourlyData?->hour ?? 0;
    }

    /**
     * Get error types
     */
    private function getErrorTypes(Carbon $startDate, Carbon $endDate): array
    {
        return MetricaSistema::where('nombre_metrica', 'http_errors')
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->select('etiquetas', DB::raw('SUM(valor) as total'))
            ->groupBy('etiquetas')
            ->get()
            ->pluck('total', 'etiquetas')
            ->toArray();
    }

    /**
     * Calculate workload level
     */
    private function calculateWorkloadLevel(int $casesHandled): string
    {
        if ($casesHandled >= 50) return 'high';
        if ($casesHandled >= 20) return 'medium';
        return 'low';
    }

    /**
     * Clear metric cache
     */
    private function clearMetricCache(string $metricName): void
    {
        $keys = [
            self::CACHE_PREFIX . 'system_*',
            self::CACHE_PREFIX . 'medical_*',
            self::CACHE_PREFIX . 'ai_*',
        ];

        foreach ($keys as $pattern) {
            Cache::forget($pattern);
        }
    }

    /**
     * Get real-time metrics
     */
    public function getRealTimeMetrics(): array
    {
        return [
            'current_load' => $this->getCurrentSystemLoad(),
            'active_sessions' => $this->getActiveSessions(),
            'queue_size' => $this->getCurrentQueueSize(),
            'pending_urgent_cases' => SolicitudMedica::where('prioridad_ia', 'Alta')
                ->where('estado', 'pendiente_evaluacion')->count(),
        ];
    }

    /**
     * Get current system load
     */
    private function getCurrentSystemLoad(): float
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return $load[0] ?? 0;
        }
        return 0;
    }

    /**
     * Get active sessions
     */
    private function getActiveSessions(): int
    {
        try {
            return DB::table('sessions')->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get current queue size
     */
    private function getCurrentQueueSize(): int
    {
        try {
            return DB::table('jobs')->count();
        } catch (\Exception $e) {
            return 0;
        }
    }
}
