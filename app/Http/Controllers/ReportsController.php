<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use App\Models\SolicitudMedica;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportsController extends Controller
{
    /**
     * Display reports dashboard
     */
    public function index()
    {
        $reportTypes = [
            'medical_requests' => 'Solicitudes Médicas',
            'performance' => 'Rendimiento del Sistema',
            'user_activity' => 'Actividad de Usuarios',
            'audit' => 'Auditoría del Sistema',
            'specialties' => 'Análisis por Especialidades',
            'institutions' => 'Análisis por Instituciones'
        ];

        return view('admin.reports.index', compact('reportTypes'));
    }

    /**
     * Generate medical requests report
     */
    public function medicalRequestsReport(Request $request)
    {
        $startDate = Carbon::parse($request->get('start_date', now()->subMonth()));
        $endDate = Carbon::parse($request->get('end_date', now()));
        $format = $request->get('format', 'html');

        // Get solicitudes data
        $solicitudes = SolicitudMedica::whereBetween('fecha_recepcion_email', [$startDate, $endDate])
            ->with(['medicoEvaluador'])
            ->orderBy('fecha_recepcion_email', 'desc')
            ->get();

        // Calculate statistics
        $statistics = [
            'total_requests' => $solicitudes->count(),
            'urgent_requests' => $solicitudes->where('prioridad_ia', 'Alta')->count(),
            'accepted_requests' => $solicitudes->where('decision_medica', 'aceptar')->count(),
            'rejected_requests' => $solicitudes->where('decision_medica', 'rechazar')->count(),
            'pending_requests' => $solicitudes->where('estado', 'pendiente_evaluacion')->count(),
            'acceptance_rate' => $solicitudes->where('decision_medica', '!=', null)->count() > 0 ? 
                round(($solicitudes->where('decision_medica', 'aceptar')->count() / 
                       $solicitudes->where('decision_medica', '!=', null)->count()) * 100, 2) : 0,
        ];

        // Group by specialty
        $bySpecialty = $solicitudes->groupBy('especialidad_solicitada')
            ->map(function ($group) {
                return [
                    'total' => $group->count(),
                    'urgent' => $group->where('prioridad_ia', 'Alta')->count(),
                    'accepted' => $group->where('decision_medica', 'aceptar')->count(),
                    'avg_response_time' => $this->calculateAverageResponseTime($group)
                ];
            });

        // Group by institution
        $byInstitution = $solicitudes->groupBy('institucion_remitente')
            ->map(function ($group) {
                return [
                    'total' => $group->count(),
                    'urgent' => $group->where('prioridad_ia', 'Alta')->count(),
                    'accepted' => $group->where('decision_medica', 'aceptar')->count()
                ];
            })
            ->sortByDesc('total')
            ->take(10);

        // Daily distribution
        $dailyDistribution = $solicitudes->groupBy(function ($item) {
            return $item->fecha_recepcion_email->format('Y-m-d');
        })->map->count();

        $reportData = [
            'period' => [
                'start' => $startDate->format('d/m/Y'),
                'end' => $endDate->format('d/m/Y')
            ],
            'statistics' => $statistics,
            'by_specialty' => $bySpecialty,
            'by_institution' => $byInstitution,
            'daily_distribution' => $dailyDistribution,
            'solicitudes' => $solicitudes,
            'generated_at' => now()->format('d/m/Y H:i:s')
        ];

        if ($format === 'pdf') {
            return $this->generatePdfReport('medical_requests', $reportData);
        } elseif ($format === 'excel') {
            return $this->generateExcelReport('medical_requests', $reportData);
        } elseif ($format === 'json') {
            return response()->json($reportData);
        }

        return view('admin.reports.medical-requests', $reportData);
    }

    /**
     * Generate performance report
     */
    public function performanceReport(Request $request)
    {
        $startDate = Carbon::parse($request->get('start_date', now()->subMonth()));
        $endDate = Carbon::parse($request->get('end_date', now()));
        $format = $request->get('format', 'html');

        // Processing times
        $processingTimes = SolicitudMedica::whereBetween('fecha_recepcion_email', [$startDate, $endDate])
            ->whereNotNull('fecha_procesamiento_ia')
            ->selectRaw('
                AVG(TIMESTAMPDIFF(SECOND, fecha_recepcion_email, fecha_procesamiento_ia)) as avg_ai_processing,
                MIN(TIMESTAMPDIFF(SECOND, fecha_recepcion_email, fecha_procesamiento_ia)) as min_ai_processing,
                MAX(TIMESTAMPDIFF(SECOND, fecha_recepcion_email, fecha_procesamiento_ia)) as max_ai_processing
            ')
            ->first();

        // Evaluation times by priority
        $evaluationTimes = SolicitudMedica::whereBetween('fecha_recepcion_email', [$startDate, $endDate])
            ->whereNotNull('fecha_evaluacion')
            ->select('prioridad_ia')
            ->selectRaw('
                AVG(TIMESTAMPDIFF(HOUR, fecha_recepcion_email, fecha_evaluacion)) as avg_hours,
                COUNT(*) as count
            ')
            ->groupBy('prioridad_ia')
            ->get();

        // SLA compliance
        $slaCompliance = $this->calculateSLACompliance($startDate, $endDate);

        // System load by hour
        $hourlyLoad = SolicitudMedica::whereBetween('fecha_recepcion_email', [$startDate, $endDate])
            ->selectRaw('HOUR(fecha_recepcion_email) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->pluck('count', 'hour');

        // User performance
        $userPerformance = User::where('role', 'medico')
            ->withCount([
                'solicitudesEvaluadas as evaluations' => function($query) use ($startDate, $endDate) {
                    $query->whereBetween('fecha_evaluacion', [$startDate, $endDate]);
                }
            ])
            ->with([
                'solicitudesEvaluadas' => function($query) use ($startDate, $endDate) {
                    $query->whereBetween('fecha_evaluacion', [$startDate, $endDate])
                          ->selectRaw('
                              medico_evaluador_id,
                              AVG(TIMESTAMPDIFF(HOUR, fecha_recepcion_email, fecha_evaluacion)) as avg_response_time,
                              SUM(CASE WHEN decision_medica = "aceptar" THEN 1 ELSE 0 END) as accepted,
                              COUNT(*) as total
                          ')
                          ->groupBy('medico_evaluador_id');
                }
            ])
            ->get();

        $reportData = [
            'period' => [
                'start' => $startDate->format('d/m/Y'),
                'end' => $endDate->format('d/m/Y')
            ],
            'processing_times' => $processingTimes,
            'evaluation_times' => $evaluationTimes,
            'sla_compliance' => $slaCompliance,
            'hourly_load' => $hourlyLoad,
            'user_performance' => $userPerformance,
            'generated_at' => now()->format('d/m/Y H:i:s')
        ];

        if ($format === 'pdf') {
            return $this->generatePdfReport('performance', $reportData);
        } elseif ($format === 'json') {
            return response()->json($reportData);
        }

        return view('admin.reports.performance', $reportData);
    }

    /**
     * Generate audit report
     */
    public function auditReport(Request $request)
    {
        $startDate = Carbon::parse($request->get('start_date', now()->subWeek()));
        $endDate = Carbon::parse($request->get('end_date', now()));
        $format = $request->get('format', 'html');

        // Get audit logs
        $auditLogs = AuditLog::whereBetween('timestamp', [$startDate, $endDate])
            ->with('user')
            ->orderBy('timestamp', 'desc')
            ->get();

        // Statistics
        $statistics = [
            'total_actions' => $auditLogs->count(),
            'unique_users' => $auditLogs->unique('user_id')->count(),
            'failed_requests' => $auditLogs->where('status_code', '>=', 400)->count(),
            'high_risk_actions' => $auditLogs->whereIn('action', [
                'delete_medical_request',
                'start_gmail_monitoring',
                'stop_gmail_monitoring'
            ])->count()
        ];

        // Actions by type
        $actionsByType = $auditLogs->groupBy('action')
            ->map->count()
            ->sortByDesc(function ($count) {
                return $count;
            });

        // Users by activity
        $usersByActivity = $auditLogs->groupBy('user_name')
            ->map->count()
            ->sortByDesc(function ($count) {
                return $count;
            })
            ->take(10);

        // Failed requests
        $failedRequests = $auditLogs->where('status_code', '>=', 400)
            ->groupBy('status_code')
            ->map->count();

        // Daily activity
        $dailyActivity = $auditLogs->groupBy(function ($item) {
            return $item->timestamp->format('Y-m-d');
        })->map->count();

        $reportData = [
            'period' => [
                'start' => $startDate->format('d/m/Y'),
                'end' => $endDate->format('d/m/Y')
            ],
            'statistics' => $statistics,
            'actions_by_type' => $actionsByType,
            'users_by_activity' => $usersByActivity,
            'failed_requests' => $failedRequests,
            'daily_activity' => $dailyActivity,
            'audit_logs' => $auditLogs->take(100), // Limit for display
            'generated_at' => now()->format('d/m/Y H:i:s')
        ];

        if ($format === 'pdf') {
            return $this->generatePdfReport('audit', $reportData);
        } elseif ($format === 'json') {
            return response()->json($reportData);
        }

        return view('admin.reports.audit', $reportData);
    }

    /**
     * Generate specialty analysis report
     */
    public function specialtyReport(Request $request)
    {
        $startDate = Carbon::parse($request->get('start_date', now()->subMonth()));
        $endDate = Carbon::parse($request->get('end_date', now()));
        $format = $request->get('format', 'html');

        $specialtyData = SolicitudMedica::whereBetween('fecha_recepcion_email', [$startDate, $endDate])
            ->select('especialidad_solicitada')
            ->selectRaw('
                COUNT(*) as total_requests,
                SUM(CASE WHEN prioridad_ia = "Alta" THEN 1 ELSE 0 END) as urgent_requests,
                SUM(CASE WHEN decision_medica = "aceptar" THEN 1 ELSE 0 END) as accepted_requests,
                SUM(CASE WHEN decision_medica = "rechazar" THEN 1 ELSE 0 END) as rejected_requests,
                AVG(CASE WHEN fecha_evaluacion IS NOT NULL THEN 
                    TIMESTAMPDIFF(HOUR, fecha_recepcion_email, fecha_evaluacion) 
                    ELSE NULL END) as avg_response_time_hours
            ')
            ->groupBy('especialidad_solicitada')
            ->orderByDesc('total_requests')
            ->get();

        // Calculate additional metrics
        $specialtyData = $specialtyData->map(function ($item) {
            $item->acceptance_rate = $item->accepted_requests + $item->rejected_requests > 0 ? 
                round(($item->accepted_requests / ($item->accepted_requests + $item->rejected_requests)) * 100, 2) : 0;
            $item->urgency_rate = round(($item->urgent_requests / $item->total_requests) * 100, 2);
            return $item;
        });

        $reportData = [
            'period' => [
                'start' => $startDate->format('d/m/Y'),
                'end' => $endDate->format('d/m/Y')
            ],
            'specialty_data' => $specialtyData,
            'total_specialties' => $specialtyData->count(),
            'generated_at' => now()->format('d/m/Y H:i:s')
        ];

        if ($format === 'pdf') {
            return $this->generatePdfReport('specialty', $reportData);
        } elseif ($format === 'json') {
            return response()->json($reportData);
        }

        return view('admin.reports.specialty', $reportData);
    }

    /**
     * Calculate average response time for a collection
     */
    private function calculateAverageResponseTime($collection)
    {
        $evaluatedCases = $collection->whereNotNull('fecha_evaluacion');
        
        if ($evaluatedCases->isEmpty()) {
            return 0;
        }

        $totalHours = $evaluatedCases->sum(function ($case) {
            return $case->fecha_recepcion_email->diffInHours($case->fecha_evaluacion);
        });

        return round($totalHours / $evaluatedCases->count(), 2);
    }

    /**
     * Calculate SLA compliance
     */
    private function calculateSLACompliance($startDate, $endDate)
    {
        $slaTargets = [
            'Alta' => 2,   // 2 hours
            'Media' => 24, // 24 hours
            'Baja' => 72   // 72 hours
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
                'compliance_rate' => $total > 0 ? round(($withinSLA / $total) * 100, 2) : 0,
                'target_hours' => $targetHours
            ];
        }

        return $compliance;
    }

    /**
     * Generate PDF report
     */
    private function generatePdfReport($type, $data)
    {
        $pdf = Pdf::loadView("admin.reports.pdf.{$type}", $data);
        
        $filename = "reporte_{$type}_" . now()->format('Y-m-d_H-i-s') . '.pdf';
        
        return $pdf->download($filename);
    }

    /**
     * Generate Excel report
     */
    private function generateExcelReport($type, $data)
    {
        // This would require a package like Laravel Excel
        // For now, return CSV format
        
        $filename = "reporte_{$type}_" . now()->format('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($data, $type) {
            $file = fopen('php://output', 'w');
            
            if ($type === 'medical_requests') {
                // CSV headers
                fputcsv($file, [
                    'ID', 'Paciente', 'Institución', 'Especialidad', 'Prioridad', 
                    'Estado', 'Fecha Recepción', 'Fecha Evaluación', 'Decisión'
                ]);
                
                // Data rows
                foreach ($data['solicitudes'] as $solicitud) {
                    fputcsv($file, [
                        $solicitud->id,
                        $solicitud->paciente_nombre,
                        $solicitud->institucion_remitente,
                        $solicitud->especialidad_solicitada,
                        $solicitud->prioridad_ia,
                        $solicitud->estado,
                        $solicitud->fecha_recepcion_email->format('d/m/Y H:i'),
                        $solicitud->fecha_evaluacion ? $solicitud->fecha_evaluacion->format('d/m/Y H:i') : '',
                        $solicitud->decision_medica ?? ''
                    ]);
                }
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get available report types
     */
    public function getReportTypes(): JsonResponse
    {
        $reportTypes = [
            [
                'id' => 'medical_requests',
                'name' => 'Solicitudes Médicas',
                'description' => 'Análisis completo de solicitudes médicas recibidas',
                'icon' => 'fas fa-file-medical'
            ],
            [
                'id' => 'performance',
                'name' => 'Rendimiento del Sistema',
                'description' => 'Métricas de rendimiento y tiempos de respuesta',
                'icon' => 'fas fa-tachometer-alt'
            ],
            [
                'id' => 'audit',
                'name' => 'Auditoría del Sistema',
                'description' => 'Registro de actividades y acciones de usuarios',
                'icon' => 'fas fa-shield-alt'
            ],
            [
                'id' => 'specialty',
                'name' => 'Análisis por Especialidades',
                'description' => 'Estadísticas agrupadas por especialidad médica',
                'icon' => 'fas fa-user-md'
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $reportTypes
        ]);
    }
}
