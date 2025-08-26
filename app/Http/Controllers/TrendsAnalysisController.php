<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\SolicitudMedica;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TrendsAnalysisController extends Controller
{
    /**
     * Display trends analysis dashboard
     */
    public function index()
    {
        $trendsData = [
            'temporal_trends' => $this->getTemporalTrends(),
            'specialty_trends' => $this->getSpecialtyTrends(),
            'institution_trends' => $this->getInstitutionTrends(),
            'priority_trends' => $this->getPriorityTrends(),
            'performance_trends' => $this->getPerformanceTrends(),
            'seasonal_patterns' => $this->getSeasonalPatterns()
        ];

        return view('admin.trends.index', compact('trendsData'));
    }

    /**
     * Get temporal trends analysis
     */
    public function getTemporalTrends(Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', '6months');
            $groupBy = $request->get('group_by', 'month');

            $startDate = $this->getStartDateForPeriod($period);
            $endDate = now();

            $dateFormat = $this->getDateFormatForGrouping($groupBy);

            // Volume trends
            $volumeTrends = SolicitudMedica::whereBetween('fecha_recepcion_email', [$startDate, $endDate])
                ->selectRaw("DATE_FORMAT(fecha_recepcion_email, '{$dateFormat}') as period")
                ->selectRaw('COUNT(*) as total_requests')
                ->selectRaw('SUM(CASE WHEN prioridad_ia = "Alta" THEN 1 ELSE 0 END) as urgent_requests')
                ->selectRaw('SUM(CASE WHEN decision_medica = "aceptar" THEN 1 ELSE 0 END) as accepted_requests')
                ->selectRaw('SUM(CASE WHEN decision_medica = "rechazar" THEN 1 ELSE 0 END) as rejected_requests')
                ->groupBy('period')
                ->orderBy('period')
                ->get();

            // Response time trends
            $responseTimeTrends = SolicitudMedica::whereBetween('fecha_recepcion_email', [$startDate, $endDate])
                ->whereNotNull('fecha_evaluacion')
                ->selectRaw("DATE_FORMAT(fecha_recepcion_email, '{$dateFormat}') as period")
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, fecha_recepcion_email, fecha_evaluacion)) as avg_response_hours')
                ->selectRaw('MIN(TIMESTAMPDIFF(HOUR, fecha_recepcion_email, fecha_evaluacion)) as min_response_hours')
                ->selectRaw('MAX(TIMESTAMPDIFF(HOUR, fecha_recepcion_email, fecha_evaluacion)) as max_response_hours')
                ->groupBy('period')
                ->orderBy('period')
                ->get();

            // Calculate growth rates
            $growthRates = $this->calculateGrowthRates($volumeTrends);

            return response()->json([
                'success' => true,
                'data' => [
                    'volume_trends' => $volumeTrends,
                    'response_time_trends' => $responseTimeTrends,
                    'growth_rates' => $growthRates,
                    'period' => $period,
                    'group_by' => $groupBy
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error analyzing temporal trends: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specialty trends analysis
     */
    public function getSpecialtyTrends(Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', '6months');
            $startDate = $this->getStartDateForPeriod($period);

            // Specialty volume trends over time
            $specialtyTrends = SolicitudMedica::whereBetween('fecha_recepcion_email', [$startDate, now()])
                ->select('especialidad_solicitada')
                ->selectRaw('DATE_FORMAT(fecha_recepcion_email, "%Y-%m") as month')
                ->selectRaw('COUNT(*) as count')
                ->groupBy('especialidad_solicitada', 'month')
                ->orderBy('month')
                ->get()
                ->groupBy('especialidad_solicitada');

            // Specialty performance metrics
            $specialtyPerformance = SolicitudMedica::whereBetween('fecha_recepcion_email', [$startDate, now()])
                ->select('especialidad_solicitada')
                ->selectRaw('COUNT(*) as total_requests')
                ->selectRaw('AVG(CASE WHEN decision_medica IS NOT NULL THEN TIMESTAMPDIFF(HOUR, fecha_recepcion_email, fecha_evaluacion) END) as avg_response_hours')
                ->selectRaw('SUM(CASE WHEN decision_medica = "aceptar" THEN 1 ELSE 0 END) as accepted_count')
                ->selectRaw('SUM(CASE WHEN prioridad_ia = "Alta" THEN 1 ELSE 0 END) as urgent_count')
                ->groupBy('especialidad_solicitada')
                ->orderByDesc('total_requests')
                ->get();

            // Calculate acceptance rates and trends
            $specialtyPerformance = $specialtyPerformance->map(function ($item) {
                $item->acceptance_rate = $item->total_requests > 0 ? 
                    round(($item->accepted_count / $item->total_requests) * 100, 2) : 0;
                $item->urgency_rate = $item->total_requests > 0 ? 
                    round(($item->urgent_count / $item->total_requests) * 100, 2) : 0;
                return $item;
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'specialty_trends' => $specialtyTrends,
                    'specialty_performance' => $specialtyPerformance,
                    'period' => $period
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error analyzing specialty trends: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get institution trends analysis
     */
    public function getInstitutionTrends(Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', '6months');
            $startDate = $this->getStartDateForPeriod($period);

            // Top institutions by volume
            $institutionVolume = SolicitudMedica::whereBetween('fecha_recepcion_email', [$startDate, now()])
                ->select('institucion_remitente')
                ->selectRaw('COUNT(*) as total_requests')
                ->selectRaw('SUM(CASE WHEN prioridad_ia = "Alta" THEN 1 ELSE 0 END) as urgent_requests')
                ->selectRaw('SUM(CASE WHEN decision_medica = "aceptar" THEN 1 ELSE 0 END) as accepted_requests')
                ->selectRaw('AVG(CASE WHEN decision_medica IS NOT NULL THEN TIMESTAMPDIFF(HOUR, fecha_recepcion_email, fecha_evaluacion) END) as avg_response_hours')
                ->groupBy('institucion_remitente')
                ->orderByDesc('total_requests')
                ->limit(20)
                ->get();

            // Institution trends over time
            $institutionTrends = SolicitudMedica::whereBetween('fecha_recepcion_email', [$startDate, now()])
                ->whereIn('institucion_remitente', $institutionVolume->pluck('institucion_remitente')->take(10))
                ->select('institucion_remitente')
                ->selectRaw('DATE_FORMAT(fecha_recepcion_email, "%Y-%m") as month')
                ->selectRaw('COUNT(*) as count')
                ->groupBy('institucion_remitente', 'month')
                ->orderBy('month')
                ->get()
                ->groupBy('institucion_remitente');

            // Calculate acceptance rates
            $institutionVolume = $institutionVolume->map(function ($item) {
                $item->acceptance_rate = $item->total_requests > 0 ? 
                    round(($item->accepted_requests / $item->total_requests) * 100, 2) : 0;
                $item->urgency_rate = $item->total_requests > 0 ? 
                    round(($item->urgent_requests / $item->total_requests) * 100, 2) : 0;
                return $item;
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'institution_volume' => $institutionVolume,
                    'institution_trends' => $institutionTrends,
                    'period' => $period
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error analyzing institution trends: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get priority trends analysis
     */
    public function getPriorityTrends(Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', '6months');
            $startDate = $this->getStartDateForPeriod($period);

            // Priority distribution over time
            $priorityTrends = SolicitudMedica::whereBetween('fecha_recepcion_email', [$startDate, now()])
                ->select('prioridad_ia')
                ->selectRaw('DATE_FORMAT(fecha_recepcion_email, "%Y-%m") as month')
                ->selectRaw('COUNT(*) as count')
                ->groupBy('prioridad_ia', 'month')
                ->orderBy('month')
                ->get()
                ->groupBy('prioridad_ia');

            // Priority response times
            $priorityResponseTimes = SolicitudMedica::whereBetween('fecha_recepcion_email', [$startDate, now()])
                ->whereNotNull('fecha_evaluacion')
                ->select('prioridad_ia')
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, fecha_recepcion_email, fecha_evaluacion)) as avg_response_hours')
                ->selectRaw('COUNT(*) as total_cases')
                ->groupBy('prioridad_ia')
                ->get();

            // SLA compliance trends
            $slaCompliance = $this->calculateSLAComplianceTrends($startDate, now());

            return response()->json([
                'success' => true,
                'data' => [
                    'priority_trends' => $priorityTrends,
                    'priority_response_times' => $priorityResponseTimes,
                    'sla_compliance' => $slaCompliance,
                    'period' => $period
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error analyzing priority trends: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get performance trends analysis
     */
    public function getPerformanceTrends(Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', '6months');
            $startDate = $this->getStartDateForPeriod($period);

            // User performance trends
            $userPerformance = User::where('role', 'medico')
                ->withCount([
                    'solicitudesEvaluadas as total_evaluations' => function($query) use ($startDate) {
                        $query->whereBetween('fecha_evaluacion', [$startDate, now()]);
                    }
                ])
                ->with([
                    'solicitudesEvaluadas' => function($query) use ($startDate) {
                        $query->whereBetween('fecha_evaluacion', [$startDate, now()])
                              ->selectRaw('medico_evaluador_id, DATE_FORMAT(fecha_evaluacion, "%Y-%m") as month, COUNT(*) as monthly_count')
                              ->groupBy('medico_evaluador_id', 'month');
                    }
                ])
                ->get();

            // System performance metrics over time
            $systemPerformance = SolicitudMedica::whereBetween('fecha_recepcion_email', [$startDate, now()])
                ->selectRaw('DATE_FORMAT(fecha_recepcion_email, "%Y-%m") as month')
                ->selectRaw('COUNT(*) as total_requests')
                ->selectRaw('AVG(CASE WHEN fecha_evaluacion IS NOT NULL THEN TIMESTAMPDIFF(HOUR, fecha_recepcion_email, fecha_evaluacion) END) as avg_response_hours')
                ->selectRaw('SUM(CASE WHEN decision_medica = "aceptar" THEN 1 ELSE 0 END) as accepted_count')
                ->selectRaw('SUM(CASE WHEN decision_medica IS NOT NULL THEN 1 ELSE 0 END) as evaluated_count')
                ->groupBy('month')
                ->orderBy('month')
                ->get();

            // Calculate performance metrics
            $systemPerformance = $systemPerformance->map(function ($item) {
                $item->acceptance_rate = $item->evaluated_count > 0 ? 
                    round(($item->accepted_count / $item->evaluated_count) * 100, 2) : 0;
                $item->evaluation_rate = $item->total_requests > 0 ? 
                    round(($item->evaluated_count / $item->total_requests) * 100, 2) : 0;
                return $item;
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'user_performance' => $userPerformance,
                    'system_performance' => $systemPerformance,
                    'period' => $period
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error analyzing performance trends: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get seasonal patterns analysis
     */
    public function getSeasonalPatterns(Request $request): JsonResponse
    {
        try {
            // Analyze patterns by day of week
            $dayOfWeekPatterns = SolicitudMedica::selectRaw('DAYOFWEEK(fecha_recepcion_email) as day_of_week')
                ->selectRaw('COUNT(*) as count')
                ->selectRaw('AVG(CASE WHEN prioridad_ia = "Alta" THEN 1 ELSE 0 END) * 100 as urgency_rate')
                ->groupBy('day_of_week')
                ->orderBy('day_of_week')
                ->get();

            // Analyze patterns by hour of day
            $hourlyPatterns = SolicitudMedica::selectRaw('HOUR(fecha_recepcion_email) as hour')
                ->selectRaw('COUNT(*) as count')
                ->selectRaw('AVG(CASE WHEN prioridad_ia = "Alta" THEN 1 ELSE 0 END) * 100 as urgency_rate')
                ->groupBy('hour')
                ->orderBy('hour')
                ->get();

            // Analyze patterns by month
            $monthlyPatterns = SolicitudMedica::selectRaw('MONTH(fecha_recepcion_email) as month')
                ->selectRaw('COUNT(*) as count')
                ->selectRaw('AVG(CASE WHEN prioridad_ia = "Alta" THEN 1 ELSE 0 END) * 100 as urgency_rate')
                ->groupBy('month')
                ->orderBy('month')
                ->get();

            // Map day numbers to names
            $dayNames = ['', 'Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
            $dayOfWeekPatterns = $dayOfWeekPatterns->map(function ($item) use ($dayNames) {
                $item->day_name = $dayNames[$item->day_of_week];
                return $item;
            });

            // Map month numbers to names
            $monthNames = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                          'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
            $monthlyPatterns = $monthlyPatterns->map(function ($item) use ($monthNames) {
                $item->month_name = $monthNames[$item->month];
                return $item;
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'day_of_week_patterns' => $dayOfWeekPatterns,
                    'hourly_patterns' => $hourlyPatterns,
                    'monthly_patterns' => $monthlyPatterns
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error analyzing seasonal patterns: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate predictive analysis
     */
    public function getPredictiveAnalysis(Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', '3months');
            $startDate = $this->getStartDateForPeriod($period);

            // Get historical data for prediction
            $historicalData = SolicitudMedica::whereBetween('fecha_recepcion_email', [$startDate, now()])
                ->selectRaw('DATE_FORMAT(fecha_recepcion_email, "%Y-%m") as month')
                ->selectRaw('COUNT(*) as count')
                ->groupBy('month')
                ->orderBy('month')
                ->get();

            // Simple linear regression for prediction
            $predictions = $this->calculateLinearTrendPrediction($historicalData);

            // Capacity analysis
            $capacityAnalysis = $this->analyzeSystemCapacity($startDate, now());

            return response()->json([
                'success' => true,
                'data' => [
                    'historical_data' => $historicalData,
                    'predictions' => $predictions,
                    'capacity_analysis' => $capacityAnalysis,
                    'period' => $period
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating predictive analysis: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper methods
     */
    private function getStartDateForPeriod(string $period): Carbon
    {
        return match($period) {
            '1month' => now()->subMonth(),
            '3months' => now()->subMonths(3),
            '6months' => now()->subMonths(6),
            '1year' => now()->subYear(),
            '2years' => now()->subYears(2),
            default => now()->subMonths(6)
        };
    }

    private function getDateFormatForGrouping(string $groupBy): string
    {
        return match($groupBy) {
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            'quarter' => '%Y-Q%q',
            'year' => '%Y',
            default => '%Y-%m'
        };
    }

    private function calculateGrowthRates($volumeTrends): array
    {
        $growthRates = [];
        $previousPeriod = null;

        foreach ($volumeTrends as $current) {
            if ($previousPeriod) {
                $growthRate = $previousPeriod->total_requests > 0 ? 
                    round((($current->total_requests - $previousPeriod->total_requests) / $previousPeriod->total_requests) * 100, 2) : 0;
                
                $growthRates[] = [
                    'period' => $current->period,
                    'growth_rate' => $growthRate,
                    'current_volume' => $current->total_requests,
                    'previous_volume' => $previousPeriod->total_requests
                ];
            }
            $previousPeriod = $current;
        }

        return $growthRates;
    }

    private function calculateSLAComplianceTrends(Carbon $startDate, Carbon $endDate): array
    {
        $slaTargets = ['Alta' => 2, 'Media' => 24, 'Baja' => 72];
        $compliance = [];

        foreach ($slaTargets as $priority => $targetHours) {
            $monthlyCompliance = SolicitudMedica::whereBetween('fecha_recepcion_email', [$startDate, $endDate])
                ->where('prioridad_ia', $priority)
                ->whereNotNull('fecha_evaluacion')
                ->selectRaw('DATE_FORMAT(fecha_recepcion_email, "%Y-%m") as month')
                ->selectRaw('COUNT(*) as total')
                ->selectRaw("SUM(CASE WHEN TIMESTAMPDIFF(HOUR, fecha_recepcion_email, fecha_evaluacion) <= {$targetHours} THEN 1 ELSE 0 END) as within_sla")
                ->groupBy('month')
                ->orderBy('month')
                ->get();

            $compliance[$priority] = $monthlyCompliance->map(function ($item) {
                $item->compliance_rate = $item->total > 0 ? 
                    round(($item->within_sla / $item->total) * 100, 2) : 0;
                return $item;
            });
        }

        return $compliance;
    }

    private function calculateLinearTrendPrediction($historicalData): array
    {
        if ($historicalData->count() < 2) {
            return [];
        }

        $n = $historicalData->count();
        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;

        foreach ($historicalData as $index => $data) {
            $x = $index + 1;
            $y = $data->count;
            
            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
        }

        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        $intercept = ($sumY - $slope * $sumX) / $n;

        // Predict next 3 months
        $predictions = [];
        for ($i = 1; $i <= 3; $i++) {
            $nextX = $n + $i;
            $predictedY = $slope * $nextX + $intercept;
            
            $predictions[] = [
                'month' => now()->addMonths($i)->format('Y-m'),
                'predicted_count' => max(0, round($predictedY)),
                'confidence' => 'medium' // Simple confidence level
            ];
        }

        return $predictions;
    }

    private function analyzeSystemCapacity(Carbon $startDate, Carbon $endDate): array
    {
        $totalRequests = SolicitudMedica::whereBetween('fecha_recepcion_email', [$startDate, $endDate])->count();
        $totalDays = $startDate->diffInDays($endDate);
        $avgDailyRequests = $totalDays > 0 ? round($totalRequests / $totalDays, 2) : 0;

        $peakDayRequests = SolicitudMedica::whereBetween('fecha_recepcion_email', [$startDate, $endDate])
            ->selectRaw('DATE(fecha_recepcion_email) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderByDesc('count')
            ->first();

        $activeMedicos = User::where('role', 'medico')->where('is_active', true)->count();
        $avgEvaluationsPerMedico = $activeMedicos > 0 ? round($totalRequests / $activeMedicos, 2) : 0;

        return [
            'avg_daily_requests' => $avgDailyRequests,
            'peak_day_requests' => $peakDayRequests ? $peakDayRequests->count : 0,
            'peak_day_date' => $peakDayRequests ? $peakDayRequests->date : null,
            'active_medicos' => $activeMedicos,
            'avg_evaluations_per_medico' => $avgEvaluationsPerMedico,
            'capacity_utilization' => $avgEvaluationsPerMedico > 0 ? 
                min(100, round(($avgDailyRequests / ($activeMedicos * 10)) * 100, 2)) : 0, // Assuming 10 evaluations per day capacity per medico
            'recommendations' => $this->generateCapacityRecommendations($avgDailyRequests, $activeMedicos)
        ];
    }

    private function generateCapacityRecommendations(float $avgDailyRequests, int $activeMedicos): array
    {
        $recommendations = [];
        
        $dailyCapacityPerMedico = 10; // Assumed capacity
        $totalDailyCapacity = $activeMedicos * $dailyCapacityPerMedico;
        $utilizationRate = $totalDailyCapacity > 0 ? ($avgDailyRequests / $totalDailyCapacity) * 100 : 0;

        if ($utilizationRate > 90) {
            $recommendations[] = 'Sistema operando cerca del límite de capacidad. Considerar agregar más médicos evaluadores.';
        } elseif ($utilizationRate > 75) {
            $recommendations[] = 'Utilización alta del sistema. Monitorear de cerca y preparar recursos adicionales.';
        } elseif ($utilizationRate < 30) {
            $recommendations[] = 'Baja utilización del sistema. Evaluar redistribución de recursos.';
        } else {
            $recommendations[] = 'Utilización óptima del sistema.';
        }

        if ($avgDailyRequests > 50) {
            $recommendations[] = 'Considerar implementar turnos adicionales para manejar el volumen de solicitudes.';
        }

        return $recommendations;
    }
}
