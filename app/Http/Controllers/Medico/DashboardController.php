<?php

namespace App\Http\Controllers\Medico;

use App\Http\Controllers\Controller;
use App\Models\SolicitudMedica;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:medico');
    }

    /**
     * Display medical dashboard
     */
    public function index()
    {
        $medico = Auth::user();
        
        // Get dashboard metrics
        $metrics = $this->getDashboardMetrics($medico);
        
        // Get recent cases
        $recentCases = $this->getRecentCases();
        
        // Get urgent cases
        $urgentCases = $this->getUrgentCases();
        
        // Get my evaluations today
        $myEvaluationsToday = $this->getMyEvaluationsToday($medico);
        
        return view('medico.dashboard', compact(
            'metrics', 
            'recentCases', 
            'urgentCases', 
            'myEvaluationsToday'
        ));
    }

    /**
     * Get dashboard metrics for medical user
     */
    private function getDashboardMetrics($medico): array
    {
        $today = Carbon::today();
        $thisWeek = Carbon::now()->startOfWeek();
        $thisMonth = Carbon::now()->startOfMonth();

        return [
            'pending_cases' => SolicitudMedica::where('estado', 'pendiente_evaluacion')->count(),
            'urgent_cases' => SolicitudMedica::where('prioridad_ia', 'Alta')
                ->where('estado', 'pendiente_evaluacion')->count(),
            'my_evaluations_today' => SolicitudMedica::where('medico_evaluador_id', $medico->id)
                ->whereDate('fecha_evaluacion', $today)->count(),
            'my_evaluations_week' => SolicitudMedica::where('medico_evaluador_id', $medico->id)
                ->where('fecha_evaluacion', '>=', $thisWeek)->count(),
            'my_evaluations_month' => SolicitudMedica::where('medico_evaluador_id', $medico->id)
                ->where('fecha_evaluacion', '>=', $thisMonth)->count(),
            'avg_response_time' => $this->getAverageResponseTime($medico),
            'acceptance_rate' => $this->getAcceptanceRate($medico),
            'cases_by_specialty' => $this->getCasesBySpecialty(),
            'daily_activity' => $this->getDailyActivity()
        ];
    }

    /**
     * Get recent cases (last 24 hours)
     */
    private function getRecentCases()
    {
        return SolicitudMedica::where('fecha_recepcion_email', '>=', Carbon::now()->subDay())
            ->where('estado', 'pendiente_evaluacion')
            ->orderBy('fecha_recepcion_email', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * Get urgent cases
     */
    private function getUrgentCases()
    {
        return SolicitudMedica::where('prioridad_ia', 'Alta')
            ->where('estado', 'pendiente_evaluacion')
            ->orderBy('fecha_recepcion_email', 'asc')
            ->limit(5)
            ->get();
    }

    /**
     * Get my evaluations today
     */
    private function getMyEvaluationsToday($medico)
    {
        return SolicitudMedica::where('medico_evaluador_id', $medico->id)
            ->whereDate('fecha_evaluacion', Carbon::today())
            ->orderBy('fecha_evaluacion', 'desc')
            ->limit(5)
            ->get();
    }

    /**
     * Get average response time for medico
     */
    private function getAverageResponseTime($medico): float
    {
        $avgTime = SolicitudMedica::where('medico_evaluador_id', $medico->id)
            ->whereNotNull('fecha_evaluacion')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, fecha_recepcion_email, fecha_evaluacion)) as avg_time')
            ->value('avg_time');

        return round($avgTime ?? 0, 1);
    }

    /**
     * Get acceptance rate for medico
     */
    private function getAcceptanceRate($medico): float
    {
        $total = SolicitudMedica::where('medico_evaluador_id', $medico->id)
            ->whereNotNull('decision_medica')
            ->count();

        if ($total === 0) return 0;

        $accepted = SolicitudMedica::where('medico_evaluador_id', $medico->id)
            ->where('decision_medica', 'aceptar')
            ->count();

        return round(($accepted / $total) * 100, 1);
    }

    /**
     * Get cases by specialty
     */
    private function getCasesBySpecialty(): array
    {
        return SolicitudMedica::select('especialidad_solicitada')
            ->selectRaw('COUNT(*) as total')
            ->where('estado', 'pendiente_evaluacion')
            ->groupBy('especialidad_solicitada')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * Get daily activity for the last 7 days
     */
    private function getDailyActivity(): array
    {
        $days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $count = SolicitudMedica::whereDate('fecha_recepcion_email', $date)->count();
            
            $days[] = [
                'date' => $date->format('Y-m-d'),
                'day' => $date->format('D'),
                'count' => $count
            ];
        }
        
        return $days;
    }

    /**
     * Get dashboard data as JSON for AJAX requests
     */
    public function getData(Request $request)
    {
        $medico = Auth::user();
        $metrics = $this->getDashboardMetrics($medico);
        
        return response()->json([
            'success' => true,
            'data' => $metrics
        ]);
    }

    /**
     * Get urgent cases for notifications
     */
    public function getUrgentNotifications()
    {
        $urgentCases = SolicitudMedica::where('prioridad_ia', 'Alta')
            ->where('estado', 'pendiente_evaluacion')
            ->where('fecha_recepcion_email', '>=', Carbon::now()->subHours(2))
            ->orderBy('fecha_recepcion_email', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $urgentCases,
            'count' => $urgentCases->count()
        ]);
    }

    /**
     * Mark urgent case as viewed
     */
    public function markUrgentAsViewed(Request $request)
    {
        $caseId = $request->input('case_id');
        
        // Here you could implement logic to mark the case as viewed
        // For now, we'll just return success
        
        return response()->json([
            'success' => true,
            'message' => 'Caso marcado como visto'
        ]);
    }

    /**
     * Get performance metrics for the medico
     */
    public function getPerformanceMetrics()
    {
        $medico = Auth::user();
        
        $metrics = [
            'evaluations_this_month' => SolicitudMedica::where('medico_evaluador_id', $medico->id)
                ->where('fecha_evaluacion', '>=', Carbon::now()->startOfMonth())
                ->count(),
            'avg_response_time_month' => $this->getAverageResponseTimeForPeriod($medico, 'month'),
            'acceptance_rate_month' => $this->getAcceptanceRateForPeriod($medico, 'month'),
            'sla_compliance' => $this->getSLACompliance($medico),
            'specialties_handled' => $this->getSpecialtiesHandled($medico),
            'performance_trend' => $this->getPerformanceTrend($medico)
        ];

        return response()->json([
            'success' => true,
            'data' => $metrics
        ]);
    }

    /**
     * Get average response time for a specific period
     */
    private function getAverageResponseTimeForPeriod($medico, $period): float
    {
        $startDate = match($period) {
            'week' => Carbon::now()->startOfWeek(),
            'month' => Carbon::now()->startOfMonth(),
            'year' => Carbon::now()->startOfYear(),
            default => Carbon::now()->startOfMonth()
        };

        $avgTime = SolicitudMedica::where('medico_evaluador_id', $medico->id)
            ->where('fecha_evaluacion', '>=', $startDate)
            ->whereNotNull('fecha_evaluacion')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, fecha_recepcion_email, fecha_evaluacion)) as avg_time')
            ->value('avg_time');

        return round($avgTime ?? 0, 1);
    }

    /**
     * Get acceptance rate for a specific period
     */
    private function getAcceptanceRateForPeriod($medico, $period): float
    {
        $startDate = match($period) {
            'week' => Carbon::now()->startOfWeek(),
            'month' => Carbon::now()->startOfMonth(),
            'year' => Carbon::now()->startOfYear(),
            default => Carbon::now()->startOfMonth()
        };

        $total = SolicitudMedica::where('medico_evaluador_id', $medico->id)
            ->where('fecha_evaluacion', '>=', $startDate)
            ->whereNotNull('decision_medica')
            ->count();

        if ($total === 0) return 0;

        $accepted = SolicitudMedica::where('medico_evaluador_id', $medico->id)
            ->where('fecha_evaluacion', '>=', $startDate)
            ->where('decision_medica', 'aceptar')
            ->count();

        return round(($accepted / $total) * 100, 1);
    }

    /**
     * Get SLA compliance rate
     */
    private function getSLACompliance($medico): array
    {
        $slaTargets = [
            'Alta' => 2,    // 2 hours
            'Media' => 24,  // 24 hours
            'Baja' => 72    // 72 hours
        ];

        $compliance = [];

        foreach ($slaTargets as $priority => $targetHours) {
            $total = SolicitudMedica::where('medico_evaluador_id', $medico->id)
                ->where('prioridad_ia', $priority)
                ->whereNotNull('fecha_evaluacion')
                ->count();

            if ($total > 0) {
                $withinSLA = SolicitudMedica::where('medico_evaluador_id', $medico->id)
                    ->where('prioridad_ia', $priority)
                    ->whereNotNull('fecha_evaluacion')
                    ->whereRaw("TIMESTAMPDIFF(HOUR, fecha_recepcion_email, fecha_evaluacion) <= ?", [$targetHours])
                    ->count();

                $compliance[$priority] = round(($withinSLA / $total) * 100, 1);
            } else {
                $compliance[$priority] = 0;
            }
        }

        return $compliance;
    }

    /**
     * Get specialties handled by the medico
     */
    private function getSpecialtiesHandled($medico): array
    {
        return SolicitudMedica::where('medico_evaluador_id', $medico->id)
            ->select('especialidad_solicitada')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('especialidad_solicitada')
            ->orderByDesc('total')
            ->get()
            ->toArray();
    }

    /**
     * Get performance trend for the last 6 months
     */
    private function getPerformanceTrend($medico): array
    {
        $trend = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $startDate = Carbon::now()->subMonths($i)->startOfMonth();
            $endDate = Carbon::now()->subMonths($i)->endOfMonth();
            
            $evaluations = SolicitudMedica::where('medico_evaluador_id', $medico->id)
                ->whereBetween('fecha_evaluacion', [$startDate, $endDate])
                ->count();
            
            $avgResponseTime = SolicitudMedica::where('medico_evaluador_id', $medico->id)
                ->whereBetween('fecha_evaluacion', [$startDate, $endDate])
                ->whereNotNull('fecha_evaluacion')
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, fecha_recepcion_email, fecha_evaluacion)) as avg_time')
                ->value('avg_time');
            
            $trend[] = [
                'month' => $startDate->format('M Y'),
                'evaluations' => $evaluations,
                'avg_response_time' => round($avgResponseTime ?? 0, 1)
            ];
        }
        
        return $trend;
    }
}
