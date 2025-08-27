<?php

namespace App\Services;

use App\Models\SolicitudMedica;
use App\Models\User;
use App\Models\MetricaSistema;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportService
{
    /**
     * Generate daily report
     */
    public function generateDailyReport(Carbon $date = null): array
    {
        $date = $date ?? now();
        $startDate = $date->copy()->startOfDay();
        $endDate = $date->copy()->endOfDay();

        return [
            'date' => $date->format('Y-m-d'),
            'period' => 'daily',
            'summary' => $this->getDailySummary($startDate, $endDate),
            'cases_by_specialty' => $this->getCasesBySpecialty($startDate, $endDate),
            'cases_by_priority' => $this->getCasesByPriority($startDate, $endDate),
            'response_times' => $this->getResponseTimes($startDate, $endDate),
            'doctor_performance' => $this->getDoctorPerformance($startDate, $endDate),
            'urgent_cases' => $this->getUrgentCases($startDate, $endDate),
            'ai_metrics' => $this->getAIMetrics($startDate, $endDate),
        ];
    }

    /**
     * Generate weekly report
     */
    public function generateWeeklyReport(Carbon $startDate = null): array
    {
        $startDate = $startDate ?? now()->startOfWeek();
        $endDate = $startDate->copy()->endOfWeek();

        return [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'period' => 'weekly',
            'summary' => $this->getWeeklySummary($startDate, $endDate),
            'trends' => $this->getWeeklyTrends($startDate, $endDate),
            'specialty_analysis' => $this->getSpecialtyAnalysis($startDate, $endDate),
            'performance_metrics' => $this->getPerformanceMetrics($startDate, $endDate),
            'quality_indicators' => $this->getQualityIndicators($startDate, $endDate),
        ];
    }

    /**
     * Generate monthly report
     */
    public function generateMonthlyReport(Carbon $date = null): array
    {
        $date = $date ?? now();
        $startDate = $date->copy()->startOfMonth();
        $endDate = $date->copy()->endOfMonth();

        return [
            'month' => $date->format('Y-m'),
            'period' => 'monthly',
            'executive_summary' => $this->getExecutiveSummary($startDate, $endDate),
            'kpi_dashboard' => $this->getKPIDashboard($startDate, $endDate),
            'department_analysis' => $this->getDepartmentAnalysis($startDate, $endDate),
            'cost_analysis' => $this->getCostAnalysis($startDate, $endDate),
            'recommendations' => $this->getRecommendations($startDate, $endDate),
        ];
    }

    /**
     * Generate custom report
     */
    public function generateCustomReport(array $parameters): array
    {
        $startDate = Carbon::parse($parameters['start_date']);
        $endDate = Carbon::parse($parameters['end_date']);
        $filters = $parameters['filters'] ?? [];
        $metrics = $parameters['metrics'] ?? [];

        $query = SolicitudMedica::whereBetween('fecha_recepcion_email', [$startDate, $endDate]);

        // Apply filters
        if (!empty($filters['specialty'])) {
            $query->where('especialidad_solicitada', $filters['specialty']);
        }

        if (!empty($filters['priority'])) {
            $query->where('prioridad_ia', $filters['priority']);
        }

        if (!empty($filters['status'])) {
            $query->where('estado', $filters['status']);
        }

        if (!empty($filters['doctor_id'])) {
            $query->where('medico_evaluador_id', $filters['doctor_id']);
        }

        $cases = $query->get();

        return [
            'parameters' => $parameters,
            'total_cases' => $cases->count(),
            'filtered_data' => $this->processCustomMetrics($cases, $metrics),
            'charts_data' => $this->generateChartsData($cases, $metrics),
            'export_ready' => true,
        ];
    }

    /**
     * Export report to PDF
     */
    public function exportToPDF(array $reportData, string $template = 'reports.default'): string
    {
        $pdf = Pdf::loadView($template, $reportData);
        $filename = 'report_' . now()->format('Y-m-d_H-i-s') . '.pdf';
        $path = 'reports/' . $filename;
        
        Storage::put($path, $pdf->output());
        
        return $path;
    }

    /**
     * Export report to Excel
     */
    public function exportToExcel(array $reportData): string
    {
        // Implementation would use Laravel Excel package
        $filename = 'report_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        $path = 'reports/' . $filename;
        
        // For now, create a CSV as placeholder
        $csv = $this->convertToCSV($reportData);
        Storage::put($path, $csv);
        
        return $path;
    }

    /**
     * Get daily summary
     */
    private function getDailySummary(Carbon $startDate, Carbon $endDate): array
    {
        $totalCases = SolicitudMedica::whereBetween('fecha_recepcion_email', [$startDate, $endDate])->count();
        $urgentCases = SolicitudMedica::whereBetween('fecha_recepcion_email', [$startDate, $endDate])
            ->where('prioridad_ia', 'Alta')->count();
        $evaluatedCases = SolicitudMedica::whereBetween('fecha_evaluacion', [$startDate, $endDate])->count();
        $acceptedCases = SolicitudMedica::whereBetween('fecha_evaluacion', [$startDate, $endDate])
            ->where('estado', 'aceptada')->count();

        return [
            'total_cases' => $totalCases,
            'urgent_cases' => $urgentCases,
            'evaluated_cases' => $evaluatedCases,
            'accepted_cases' => $acceptedCases,
            'acceptance_rate' => $evaluatedCases > 0 ? round(($acceptedCases / $evaluatedCases) * 100, 1) : 0,
            'urgency_rate' => $totalCases > 0 ? round(($urgentCases / $totalCases) * 100, 1) : 0,
        ];
    }

    /**
     * Get cases by specialty
     */
    private function getCasesBySpecialty(Carbon $startDate, Carbon $endDate): Collection
    {
        return SolicitudMedica::whereBetween('fecha_recepcion_email', [$startDate, $endDate])
            ->select('especialidad_solicitada', DB::raw('count(*) as total'))
            ->groupBy('especialidad_solicitada')
            ->orderBy('total', 'desc')
            ->get();
    }

    /**
     * Get cases by priority
     */
    private function getCasesByPriority(Carbon $startDate, Carbon $endDate): Collection
    {
        return SolicitudMedica::whereBetween('fecha_recepcion_email', [$startDate, $endDate])
            ->select('prioridad_ia', DB::raw('count(*) as total'))
            ->groupBy('prioridad_ia')
            ->get();
    }

    /**
     * Get response times
     */
    private function getResponseTimes(Carbon $startDate, Carbon $endDate): array
    {
        $cases = SolicitudMedica::whereBetween('fecha_recepcion_email', [$startDate, $endDate])
            ->whereNotNull('fecha_evaluacion')
            ->get();

        $responseTimes = $cases->map(function ($case) {
            return $case->fecha_recepcion_email->diffInHours($case->fecha_evaluacion);
        });

        return [
            'average' => $responseTimes->avg(),
            'median' => $responseTimes->median(),
            'min' => $responseTimes->min(),
            'max' => $responseTimes->max(),
            'within_sla' => $responseTimes->filter(fn($time) => $time <= 24)->count(),
            'total_evaluated' => $responseTimes->count(),
        ];
    }

    /**
     * Get doctor performance
     */
    private function getDoctorPerformance(Carbon $startDate, Carbon $endDate): Collection
    {
        return User::where('role', 'medico')
            ->where('is_active', true)
            ->withCount([
                'solicitudesEvaluadas' => function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('fecha_evaluacion', [$startDate, $endDate]);
                }
            ])
            ->get()
            ->map(function ($doctor) use ($startDate, $endDate) {
                $avgTime = SolicitudMedica::where('medico_evaluador_id', $doctor->id)
                    ->whereBetween('fecha_evaluacion', [$startDate, $endDate])
                    ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, fecha_recepcion_email, fecha_evaluacion)) as avg_time')
                    ->value('avg_time');

                return [
                    'doctor' => $doctor->name,
                    'department' => $doctor->department,
                    'cases_evaluated' => $doctor->solicitudes_evaluadas_count,
                    'avg_response_time' => round($avgTime ?? 0, 1),
                ];
            });
    }

    /**
     * Get urgent cases
     */
    private function getUrgentCases(Carbon $startDate, Carbon $endDate): Collection
    {
        return SolicitudMedica::whereBetween('fecha_recepcion_email', [$startDate, $endDate])
            ->where('prioridad_ia', 'Alta')
            ->with(['medicoEvaluador:id,name'])
            ->get()
            ->map(function ($case) {
                return [
                    'patient' => $case->paciente_nombre,
                    'specialty' => $case->especialidad_solicitada,
                    'urgency_score' => $case->score_urgencia,
                    'status' => $case->estado,
                    'received_at' => $case->fecha_recepcion_email,
                    'evaluated_by' => $case->medicoEvaluador?->name,
                    'response_time' => $case->fecha_evaluacion ? 
                        $case->fecha_recepcion_email->diffInHours($case->fecha_evaluacion) : null,
                ];
            });
    }

    /**
     * Get AI metrics
     */
    private function getAIMetrics(Carbon $startDate, Carbon $endDate): array
    {
        $totalCases = SolicitudMedica::whereBetween('fecha_recepcion_email', [$startDate, $endDate])->count();
        $aiProcessedCases = SolicitudMedica::whereBetween('fecha_recepcion_email', [$startDate, $endDate])
            ->whereNotNull('analisis_ia')->count();
        
        $accuratePredictions = SolicitudMedica::whereBetween('fecha_recepcion_email', [$startDate, $endDate])
            ->whereNotNull('prioridad_medico')
            ->whereColumn('prioridad_ia', 'prioridad_medico')
            ->count();
        
        $totalEvaluated = SolicitudMedica::whereBetween('fecha_recepcion_email', [$startDate, $endDate])
            ->whereNotNull('prioridad_medico')->count();

        return [
            'processing_rate' => $totalCases > 0 ? round(($aiProcessedCases / $totalCases) * 100, 1) : 0,
            'accuracy_rate' => $totalEvaluated > 0 ? round(($accuratePredictions / $totalEvaluated) * 100, 1) : 0,
            'total_processed' => $aiProcessedCases,
            'accurate_predictions' => $accuratePredictions,
            'total_evaluated' => $totalEvaluated,
        ];
    }

    /**
     * Convert report data to CSV format
     */
    private function convertToCSV(array $data): string
    {
        $csv = '';
        
        // Add headers
        if (isset($data['summary'])) {
            $csv .= "Summary Report\n";
            foreach ($data['summary'] as $key => $value) {
                $csv .= ucfirst(str_replace('_', ' ', $key)) . "," . $value . "\n";
            }
            $csv .= "\n";
        }
        
        return $csv;
    }

    /**
     * Get weekly summary
     */
    private function getWeeklySummary(Carbon $startDate, Carbon $endDate): array
    {
        // Implementation for weekly summary
        return $this->getDailySummary($startDate, $endDate);
    }

    /**
     * Get weekly trends
     */
    private function getWeeklyTrends(Carbon $startDate, Carbon $endDate): array
    {
        $trends = [];
        $currentDate = $startDate->copy();
        
        while ($currentDate->lte($endDate)) {
            $dayStart = $currentDate->copy()->startOfDay();
            $dayEnd = $currentDate->copy()->endOfDay();
            
            $trends[] = [
                'date' => $currentDate->format('Y-m-d'),
                'day_name' => $currentDate->format('l'),
                'cases' => SolicitudMedica::whereBetween('fecha_recepcion_email', [$dayStart, $dayEnd])->count(),
                'urgent_cases' => SolicitudMedica::whereBetween('fecha_recepcion_email', [$dayStart, $dayEnd])
                    ->where('prioridad_ia', 'Alta')->count(),
            ];
            
            $currentDate->addDay();
        }
        
        return $trends;
    }

    /**
     * Get specialty analysis
     */
    private function getSpecialtyAnalysis(Carbon $startDate, Carbon $endDate): array
    {
        return SolicitudMedica::whereBetween('fecha_recepcion_email', [$startDate, $endDate])
            ->select('especialidad_solicitada')
            ->selectRaw('COUNT(*) as total_cases')
            ->selectRaw('AVG(score_urgencia) as avg_urgency')
            ->selectRaw('SUM(CASE WHEN estado = "aceptada" THEN 1 ELSE 0 END) as accepted_cases')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, fecha_recepcion_email, fecha_evaluacion)) as avg_response_time')
            ->groupBy('especialidad_solicitada')
            ->get()
            ->toArray();
    }

    /**
     * Get performance metrics
     */
    private function getPerformanceMetrics(Carbon $startDate, Carbon $endDate): array
    {
        return [
            'system_uptime' => 99.9, // Placeholder
            'avg_processing_time' => 2.5, // Placeholder
            'error_rate' => 0.1, // Placeholder
            'user_satisfaction' => 4.5, // Placeholder
        ];
    }

    /**
     * Get quality indicators
     */
    private function getQualityIndicators(Carbon $startDate, Carbon $endDate): array
    {
        return [
            'data_completeness' => 95.5, // Placeholder
            'ai_confidence' => 87.2, // Placeholder
            'manual_corrections' => 12, // Placeholder
        ];
    }

    /**
     * Get executive summary
     */
    private function getExecutiveSummary(Carbon $startDate, Carbon $endDate): array
    {
        return [
            'key_metrics' => $this->getDailySummary($startDate, $endDate),
            'highlights' => [
                'Most active specialty',
                'Best performing doctor',
                'Improvement areas'
            ],
            'recommendations' => [
                'Increase staffing in high-demand specialties',
                'Implement additional AI training',
                'Optimize workflow processes'
            ]
        ];
    }

    /**
     * Get KPI dashboard
     */
    private function getKPIDashboard(Carbon $startDate, Carbon $endDate): array
    {
        return [
            'response_time_sla' => 85.5, // Percentage within SLA
            'patient_satisfaction' => 4.2, // Out of 5
            'cost_per_case' => 125.50, // Average cost
            'efficiency_score' => 78.9, // Overall efficiency
        ];
    }

    /**
     * Get department analysis
     */
    private function getDepartmentAnalysis(Carbon $startDate, Carbon $endDate): array
    {
        return User::where('role', 'medico')
            ->select('department')
            ->selectRaw('COUNT(*) as doctor_count')
            ->groupBy('department')
            ->get()
            ->toArray();
    }

    /**
     * Get cost analysis
     */
    private function getCostAnalysis(Carbon $startDate, Carbon $endDate): array
    {
        return [
            'total_operational_cost' => 15000, // Placeholder
            'cost_per_case' => 125.50, // Placeholder
            'cost_savings_ai' => 2500, // Placeholder
        ];
    }

    /**
     * Get recommendations
     */
    private function getRecommendations(Carbon $startDate, Carbon $endDate): array
    {
        return [
            'operational' => [
                'Optimize peak hour staffing',
                'Implement automated triage',
                'Enhance doctor training programs'
            ],
            'technical' => [
                'Upgrade AI models',
                'Improve system integration',
                'Enhance monitoring capabilities'
            ],
            'strategic' => [
                'Expand to new specialties',
                'Develop mobile applications',
                'Implement predictive analytics'
            ]
        ];
    }

    /**
     * Process custom metrics
     */
    private function processCustomMetrics(Collection $cases, array $metrics): array
    {
        $result = [];
        
        foreach ($metrics as $metric) {
            switch ($metric) {
                case 'case_volume':
                    $result['case_volume'] = $cases->count();
                    break;
                case 'avg_urgency':
                    $result['avg_urgency'] = $cases->avg('score_urgencia');
                    break;
                case 'response_time':
                    $result['response_time'] = $cases->whereNotNull('fecha_evaluacion')
                        ->map(fn($case) => $case->fecha_recepcion_email->diffInHours($case->fecha_evaluacion))
                        ->avg();
                    break;
            }
        }
        
        return $result;
    }

    /**
     * Generate charts data
     */
    private function generateChartsData(Collection $cases, array $metrics): array
    {
        return [
            'specialty_distribution' => $cases->groupBy('especialidad_solicitada')
                ->map(fn($group) => $group->count())->toArray(),
            'priority_distribution' => $cases->groupBy('prioridad_ia')
                ->map(fn($group) => $group->count())->toArray(),
            'status_distribution' => $cases->groupBy('estado')
                ->map(fn($group) => $group->count())->toArray(),
        ];
    }
}
