<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\SolicitudMedica;
use App\Models\NotificacionInterna;
use App\Models\MetricaSistema;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class MetricsController extends Controller
{
    /**
     * Get dashboard metrics summary
     */
    public function getDashboardMetrics(): JsonResponse
    {
        try {
            $cacheKey = 'dashboard_metrics_' . now()->format('Y-m-d-H');
            
            $metrics = Cache::remember($cacheKey, 300, function () { // Cache for 5 minutes
                return [
                    'overview' => $this->getOverviewMetrics(),
                    'solicitudes' => $this->getSolicitudesMetrics(),
                    'performance' => $this->getPerformanceMetrics(),
                    'activity' => $this->getActivityMetrics(),
                    'trends' => $this->getTrendMetrics(),
                    'alerts' => $this->getAlertsMetrics()
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $metrics,
                'generated_at' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get overview metrics
     */
    private function getOverviewMetrics(): array
    {
        $today = now()->startOfDay();
        $yesterday = now()->subDay()->startOfDay();

        // Today's metrics
        $todaySolicitudes = SolicitudMedica::whereDate('fecha_recepcion_email', $today)->count();
        $todayUrgentes = SolicitudMedica::whereDate('fecha_recepcion_email', $today)
            ->where('prioridad_ia', 'Alta')->count();
        $todayEvaluadas = SolicitudMedica::whereDate('fecha_evaluacion', $today)->count();
        $todayAceptadas = SolicitudMedica::whereDate('fecha_evaluacion', $today)
            ->where('decision_medica', 'aceptar')->count();

        // Yesterday's metrics for comparison
        $yesterdaySolicitudes = SolicitudMedica::whereDate('fecha_recepcion_email', $yesterday)->count();
        $yesterdayUrgentes = SolicitudMedica::whereDate('fecha_recepcion_email', $yesterday)
            ->where('prioridad_ia', 'Alta')->count();

        // Pending metrics
        $pendientesEvaluacion = SolicitudMedica::pendientesEvaluacion()->count();
        $urgentesNoEvaluadas = SolicitudMedica::urgentes()->pendientesEvaluacion()->count();

        return [
            'today' => [
                'total_solicitudes' => $todaySolicitudes,
                'urgentes' => $todayUrgentes,
                'evaluadas' => $todayEvaluadas,
                'aceptadas' => $todayAceptadas
            ],
            'comparison' => [
                'solicitudes_change' => $todaySolicitudes - $yesterdaySolicitudes,
                'urgentes_change' => $todayUrgentes - $yesterdayUrgentes
            ],
            'pending' => [
                'pendientes_evaluacion' => $pendientesEvaluacion,
                'urgentes_no_evaluadas' => $urgentesNoEvaluadas
            ],
            'rates' => [
                'acceptance_rate' => $todayEvaluadas > 0 ? round(($todayAceptadas / $todayEvaluadas) * 100, 1) : 0,
                'urgency_rate' => $todaySolicitudes > 0 ? round(($todayUrgentes / $todaySolicitudes) * 100, 1) : 0
            ]
        ];
    }

    /**
     * Get solicitudes metrics
     */
    private function getSolicitudesMetrics(): array
    {
        $last7Days = now()->subDays(7);
        $last30Days = now()->subDays(30);

        // Status distribution
        $statusDistribution = SolicitudMedica::select('estado', DB::raw('count(*) as count'))
            ->where('created_at', '>=', $last30Days)
            ->groupBy('estado')
            ->get()
            ->pluck('count', 'estado')
            ->toArray();

        // Priority distribution
        $priorityDistribution = SolicitudMedica::select('prioridad_ia', DB::raw('count(*) as count'))
            ->where('created_at', '>=', $last30Days)
            ->groupBy('prioridad_ia')
            ->get()
            ->pluck('count', 'prioridad_ia')
            ->toArray();

        // Specialty distribution
        $specialtyDistribution = SolicitudMedica::select('especialidad_solicitada', DB::raw('count(*) as count'))
            ->where('created_at', '>=', $last30Days)
            ->groupBy('especialidad_solicitada')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->pluck('count', 'especialidad_solicitada')
            ->toArray();

        // Institution distribution
        $institutionDistribution = SolicitudMedica::select('institucion_remitente', DB::raw('count(*) as count'))
            ->where('created_at', '>=', $last30Days)
            ->groupBy('institucion_remitente')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->pluck('count', 'institucion_remitente')
            ->toArray();

        return [
            'status_distribution' => $statusDistribution,
            'priority_distribution' => $priorityDistribution,
            'specialty_distribution' => $specialtyDistribution,
            'institution_distribution' => $institutionDistribution,
            'total_last_7_days' => SolicitudMedica::where('created_at', '>=', $last7Days)->count(),
            'total_last_30_days' => SolicitudMedica::where('created_at', '>=', $last30Days)->count()
        ];
    }

    /**
     * Get performance metrics
     */
    private function getPerformanceMetrics(): array
    {
        $last30Days = now()->subDays(30);

        // Average processing times
        $avgProcessingTime = SolicitudMedica::whereNotNull('fecha_procesamiento_ia')
            ->where('created_at', '>=', $last30Days)
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, fecha_recepcion_email, fecha_procesamiento_ia)) as avg_seconds')
            ->value('avg_seconds');

        $avgEvaluationTime = SolicitudMedica::whereNotNull('fecha_evaluacion')
            ->where('created_at', '>=', $last30Days)
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, fecha_procesamiento_ia, fecha_evaluacion)) as avg_minutes')
            ->value('avg_minutes');

        // Response times by priority
        $responseTimesByPriority = SolicitudMedica::whereNotNull('fecha_evaluacion')
            ->where('created_at', '>=', $last30Days)
            ->select('prioridad_ia')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, fecha_recepcion_email, fecha_evaluacion)) as avg_hours')
            ->groupBy('prioridad_ia')
            ->get()
            ->pluck('avg_hours', 'prioridad_ia')
            ->toArray();

        // Notification metrics
        $notificationMetrics = NotificacionInterna::where('created_at', '>=', $last30Days)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN estado = "enviada" THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN estado = "fallida" THEN 1 ELSE 0 END) as failed,
                AVG(intentos_envio) as avg_attempts
            ')
            ->first();

        return [
            'processing_times' => [
                'avg_ai_processing_seconds' => round($avgProcessingTime ?? 0, 2),
                'avg_medical_evaluation_minutes' => round($avgEvaluationTime ?? 0, 2)
            ],
            'response_times_by_priority' => array_map(function($hours) {
                return round($hours, 2);
            }, $responseTimesByPriority),
            'notifications' => [
                'total' => $notificationMetrics->total ?? 0,
                'sent' => $notificationMetrics->sent ?? 0,
                'failed' => $notificationMetrics->failed ?? 0,
                'success_rate' => $notificationMetrics->total > 0 ? 
                    round(($notificationMetrics->sent / $notificationMetrics->total) * 100, 1) : 0,
                'avg_attempts' => round($notificationMetrics->avg_attempts ?? 0, 1)
            ]
        ];
    }

    /**
     * Get activity metrics
     */
    private function getActivityMetrics(): array
    {
        $last7Days = now()->subDays(7);

        // Daily activity for the last 7 days
        $dailyActivity = SolicitudMedica::where('fecha_recepcion_email', '>=', $last7Days)
            ->selectRaw('DATE(fecha_recepcion_email) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();

        // Hourly distribution (last 24 hours)
        $hourlyDistribution = SolicitudMedica::where('fecha_recepcion_email', '>=', now()->subDay())
            ->selectRaw('HOUR(fecha_recepcion_email) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->pluck('count', 'hour')
            ->toArray();

        // User activity
        $userActivity = User::withCount([
                'solicitudesEvaluadas' => function($query) use ($last7Days) {
                    $query->where('fecha_evaluacion', '>=', $last7Days);
                }
            ])
            ->where('role', 'medico')
            ->orderByDesc('solicitudes_evaluadas_count')
            ->limit(10)
            ->get(['id', 'name', 'solicitudes_evaluadas_count'])
            ->toArray();

        return [
            'daily_activity' => $dailyActivity,
            'hourly_distribution' => $hourlyDistribution,
            'user_activity' => $userActivity,
            'peak_hour' => array_keys($hourlyDistribution, max($hourlyDistribution))[0] ?? null
        ];
    }

    /**
     * Get trend metrics
     */
    private function getTrendMetrics(): array
    {
        $last30Days = now()->subDays(30);

        // Weekly trends
        $weeklyTrends = SolicitudMedica::where('fecha_recepcion_email', '>=', $last30Days)
            ->selectRaw('
                WEEK(fecha_recepcion_email) as week,
                COUNT(*) as total,
                SUM(CASE WHEN prioridad_ia = "Alta" THEN 1 ELSE 0 END) as urgent,
                SUM(CASE WHEN decision_medica = "aceptar" THEN 1 ELSE 0 END) as accepted
            ')
            ->groupBy('week')
            ->orderBy('week')
            ->get()
            ->toArray();

        // Growth metrics
        $thisMonth = SolicitudMedica::whereMonth('fecha_recepcion_email', now()->month)->count();
        $lastMonth = SolicitudMedica::whereMonth('fecha_recepcion_email', now()->subMonth()->month)->count();
        $growthRate = $lastMonth > 0 ? round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1) : 0;

        return [
            'weekly_trends' => $weeklyTrends,
            'growth' => [
                'this_month' => $thisMonth,
                'last_month' => $lastMonth,
                'growth_rate' => $growthRate
            ]
        ];
    }

    /**
     * Get alerts metrics
     */
    private function getAlertsMetrics(): array
    {
        $now = now();

        // Pending urgent cases
        $urgentPending = SolicitudMedica::urgentes()
            ->pendientesEvaluacion()
            ->where('fecha_recepcion_email', '<', $now->subHours(2))
            ->count();

        // Old pending cases
        $oldPending = SolicitudMedica::pendientesEvaluacion()
            ->where('fecha_recepcion_email', '<', $now->subHours(24))
            ->count();

        // Failed notifications
        $failedNotifications = NotificacionInterna::where('estado', 'fallida')
            ->where('created_at', '>=', $now->subDay())
            ->count();

        // System alerts
        $systemAlerts = [];

        if ($urgentPending > 0) {
            $systemAlerts[] = [
                'type' => 'urgent_pending',
                'message' => "Hay {$urgentPending} casos urgentes pendientes de evaluación por más de 2 horas",
                'severity' => 'high',
                'count' => $urgentPending
            ];
        }

        if ($oldPending > 5) {
            $systemAlerts[] = [
                'type' => 'old_pending',
                'message' => "Hay {$oldPending} casos pendientes por más de 24 horas",
                'severity' => 'medium',
                'count' => $oldPending
            ];
        }

        if ($failedNotifications > 0) {
            $systemAlerts[] = [
                'type' => 'failed_notifications',
                'message' => "Hay {$failedNotifications} notificaciones fallidas en las últimas 24 horas",
                'severity' => 'medium',
                'count' => $failedNotifications
            ];
        }

        return [
            'urgent_pending' => $urgentPending,
            'old_pending' => $oldPending,
            'failed_notifications' => $failedNotifications,
            'system_alerts' => $systemAlerts,
            'alert_count' => count($systemAlerts)
        ];
    }

    /**
     * Get detailed metrics for a specific period
     */
    public function getDetailedMetrics(Request $request): JsonResponse
    {
        try {
            $startDate = Carbon::parse($request->get('start_date', now()->subDays(30)));
            $endDate = Carbon::parse($request->get('end_date', now()));
            $groupBy = $request->get('group_by', 'day'); // day, week, month

            $metrics = [
                'period' => [
                    'start' => $startDate->toDateString(),
                    'end' => $endDate->toDateString(),
                    'group_by' => $groupBy
                ],
                'solicitudes' => $this->getDetailedSolicitudesMetrics($startDate, $endDate, $groupBy),
                'performance' => $this->getDetailedPerformanceMetrics($startDate, $endDate),
                'users' => $this->getDetailedUserMetrics($startDate, $endDate)
            ];

            return response()->json([
                'success' => true,
                'data' => $metrics
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detailed solicitudes metrics for period
     */
    private function getDetailedSolicitudesMetrics(Carbon $startDate, Carbon $endDate, string $groupBy): array
    {
        $dateFormat = match($groupBy) {
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            default => '%Y-%m-%d'
        };

        $timeSeries = SolicitudMedica::whereBetween('fecha_recepcion_email', [$startDate, $endDate])
            ->selectRaw("
                DATE_FORMAT(fecha_recepcion_email, '{$dateFormat}') as period,
                COUNT(*) as total,
                SUM(CASE WHEN prioridad_ia = 'Alta' THEN 1 ELSE 0 END) as urgent,
                SUM(CASE WHEN prioridad_ia = 'Media' THEN 1 ELSE 0 END) as medium,
                SUM(CASE WHEN prioridad_ia = 'Baja' THEN 1 ELSE 0 END) as low,
                SUM(CASE WHEN decision_medica = 'aceptar' THEN 1 ELSE 0 END) as accepted,
                SUM(CASE WHEN decision_medica = 'rechazar' THEN 1 ELSE 0 END) as rejected
            ")
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->toArray();

        return [
            'time_series' => $timeSeries,
            'totals' => [
                'total_solicitudes' => array_sum(array_column($timeSeries, 'total')),
                'total_urgent' => array_sum(array_column($timeSeries, 'urgent')),
                'total_accepted' => array_sum(array_column($timeSeries, 'accepted')),
                'total_rejected' => array_sum(array_column($timeSeries, 'rejected'))
            ]
        ];
    }

    /**
     * Get detailed performance metrics for period
     */
    private function getDetailedPerformanceMetrics(Carbon $startDate, Carbon $endDate): array
    {
        $performanceData = SolicitudMedica::whereBetween('fecha_recepcion_email', [$startDate, $endDate])
            ->whereNotNull('fecha_evaluacion')
            ->selectRaw('
                prioridad_ia,
                AVG(TIMESTAMPDIFF(HOUR, fecha_recepcion_email, fecha_evaluacion)) as avg_response_hours,
                MIN(TIMESTAMPDIFF(HOUR, fecha_recepcion_email, fecha_evaluacion)) as min_response_hours,
                MAX(TIMESTAMPDIFF(HOUR, fecha_recepcion_email, fecha_evaluacion)) as max_response_hours,
                COUNT(*) as count
            ')
            ->groupBy('prioridad_ia')
            ->get()
            ->toArray();

        return [
            'response_times_by_priority' => $performanceData,
            'sla_compliance' => $this->calculateSLACompliance($startDate, $endDate)
        ];
    }

    /**
     * Calculate SLA compliance
     */
    private function calculateSLACompliance(Carbon $startDate, Carbon $endDate): array
    {
        // Define SLA targets (in hours)
        $slaTargets = [
            'Alta' => 2,   // 2 hours for urgent cases
            'Media' => 24, // 24 hours for medium priority
            'Baja' => 72   // 72 hours for low priority
        ];

        $compliance = [];

        foreach ($slaTargets as $priority => $targetHours) {
            $total = SolicitudMedica::whereBetween('fecha_recepcion_email', [$startDate, $endDate])
                ->where('prioridad_ia', $priority)
                ->whereNotNull('fecha_evaluacion')
                ->count();

            $withinSLA = SolicitudMedica::whereBetween('fecha_recepcion_email', [$startDate, $endDate])
                ->where('prioridad_ia', $priority)
                ->whereNotNull('fecha_evaluacion')
                ->whereRaw("TIMESTAMPDIFF(HOUR, fecha_recepcion_email, fecha_evaluacion) <= {$targetHours}")
                ->count();

            $compliance[$priority] = [
                'total' => $total,
                'within_sla' => $withinSLA,
                'compliance_rate' => $total > 0 ? round(($withinSLA / $total) * 100, 1) : 0,
                'target_hours' => $targetHours
            ];
        }

        return $compliance;
    }

    /**
     * Get detailed user metrics for period
     */
    private function getDetailedUserMetrics(Carbon $startDate, Carbon $endDate): array
    {
        $userMetrics = User::where('role', 'medico')
            ->withCount([
                'solicitudesEvaluadas as evaluations_count' => function($query) use ($startDate, $endDate) {
                    $query->whereBetween('fecha_evaluacion', [$startDate, $endDate]);
                }
            ])
            ->with([
                'solicitudesEvaluadas' => function($query) use ($startDate, $endDate) {
                    $query->whereBetween('fecha_evaluacion', [$startDate, $endDate])
                          ->selectRaw('
                              medico_evaluador_id,
                              decision_medica,
                              COUNT(*) as count
                          ')
                          ->groupBy('medico_evaluador_id', 'decision_medica');
                }
            ])
            ->get()
            ->map(function($user) {
                $decisions = $user->solicitudesEvaluadas->groupBy('decision_medica');
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'total_evaluations' => $user->evaluations_count,
                    'accepted' => $decisions->get('aceptar', collect())->sum('count'),
                    'rejected' => $decisions->get('rechazar', collect())->sum('count'),
                    'info_requested' => $decisions->get('solicitar_info', collect())->sum('count')
                ];
            })
            ->sortByDesc('total_evaluations')
            ->values()
            ->toArray();

        return [
            'user_performance' => $userMetrics,
            'top_evaluators' => array_slice($userMetrics, 0, 5)
        ];
    }
}
