<?php

namespace App\Http\Controllers\Medico;

use App\Http\Controllers\Controller;
use App\Models\SolicitudMedica;
use App\Services\NotificationService;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class EvaluacionController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->middleware('auth');
        $this->middleware('role:medico');
        $this->notificationService = $notificationService;
    }

    /**
     * Show evaluation form for a medical request
     */
    public function mostrarEvaluacion($id)
    {
        $solicitud = SolicitudMedica::findOrFail($id);

        // Check if already evaluated
        if ($solicitud->estado === 'evaluada') {
            return redirect()->route('medico.bandeja-casos')
                ->with('warning', 'Esta solicitud ya ha sido evaluada.');
        }

        // Mark as "en_evaluacion" if it's pending
        if ($solicitud->estado === 'pendiente_evaluacion') {
            $solicitud->update([
                'estado' => 'en_evaluacion',
                'medico_evaluador_id' => Auth::id()
            ]);
        }

        $especialidades = [
            'Cardiología', 'Neurología', 'Ortopedia', 'Pediatría',
            'Ginecología', 'Urología', 'Oftalmología', 'Dermatología',
            'Psiquiatría', 'Medicina Interna', 'Cirugía General'
        ];

        $serviciosDestino = [
            'urgencias' => 'Urgencias',
            'hospitalizacion' => 'Hospitalización',
            'consulta_externa' => 'Consulta Externa',
            'cirugia' => 'Cirugía',
            'uci' => 'UCI',
            'observacion' => 'Observación'
        ];

        $motivosRechazo = [
            'no_cumple_criterios' => 'No cumple criterios de ingreso',
            'informacion_insuficiente' => 'Información insuficiente',
            'puede_resolver_origen' => 'Puede resolverse en institución de origen',
            'no_disponibilidad' => 'No hay disponibilidad de recursos',
            'requiere_otra_especialidad' => 'Requiere otra especialidad',
            'otros' => 'Otros motivos'
        ];

        return view('medico.evaluar-solicitud', compact(
            'solicitud', 
            'especialidades', 
            'serviciosDestino', 
            'motivosRechazo'
        ));
    }

    /**
     * Save medical evaluation
     */
    public function guardarEvaluacion(Request $request, $id)
    {
        $solicitud = SolicitudMedica::findOrFail($id);

        // Validate that the case can be evaluated
        if ($solicitud->estado === 'evaluada') {
            return redirect()->route('medico.bandeja-casos')
                ->with('error', 'Esta solicitud ya ha sido evaluada.');
        }

        // Validation rules
        $rules = [
            'decision_medica' => 'required|in:aceptar,rechazar,solicitar_info',
            'observaciones_medico' => 'required|string|max:2000',
            'prioridad_medica' => 'nullable|in:Alta,Media,Baja'
        ];

        // Additional validation based on decision
        if ($request->decision_medica === 'aceptar') {
            $rules['fecha_programada'] = 'required|date|after:now';
            $rules['servicio_destino'] = 'required|string';
        } elseif ($request->decision_medica === 'rechazar') {
            $rules['motivo_rechazo'] = 'required|string';
        } elseif ($request->decision_medica === 'solicitar_info') {
            $rules['informacion_requerida'] = 'required|string|max:1000';
        }

        $validator = Validator::make($request->all(), $rules, [
            'decision_medica.required' => 'Debe seleccionar una decisión médica.',
            'observaciones_medico.required' => 'Las observaciones médicas son obligatorias.',
            'fecha_programada.required' => 'La fecha programada es obligatoria para casos aceptados.',
            'fecha_programada.after' => 'La fecha programada debe ser posterior a la fecha actual.',
            'servicio_destino.required' => 'Debe seleccionar el servicio de destino.',
            'motivo_rechazo.required' => 'Debe especificar el motivo del rechazo.',
            'informacion_requerida.required' => 'Debe especificar qué información se requiere.'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            // Prepare evaluation data
            $evaluationData = [
                'decision_medica' => $request->decision_medica,
                'observaciones_medico' => $request->observaciones_medico,
                'prioridad_medica' => $request->prioridad_medica ?? $solicitud->prioridad_ia,
                'fecha_evaluacion' => now(),
                'medico_evaluador_id' => Auth::id(),
                'estado' => 'evaluada'
            ];

            // Add specific fields based on decision
            if ($request->decision_medica === 'aceptar') {
                $evaluationData['fecha_programada'] = $request->fecha_programada;
                $evaluationData['servicio_destino'] = $request->servicio_destino;
                $evaluationData['estado'] = 'aceptada';
            } elseif ($request->decision_medica === 'rechazar') {
                $evaluationData['motivo_rechazo'] = $request->motivo_rechazo;
                $evaluationData['estado'] = 'rechazada';
            } elseif ($request->decision_medica === 'solicitar_info') {
                $evaluationData['informacion_requerida'] = $request->informacion_requerida;
                $evaluationData['estado'] = 'pendiente_informacion';
            }

            // Update the medical request
            $solicitud->update($evaluationData);

            // Log the evaluation in audit
            AuditLog::logActivity(
                'evaluate_medical_request',
                'solicitud_medica',
                $solicitud->id,
                [
                    'decision' => $request->decision_medica,
                    'priority' => $evaluationData['prioridad_medica'],
                    'medico' => Auth::user()->name
                ],
                'Evaluación médica completada'
            );

            // Send notifications
            $this->notificationService->sendEvaluationNotification(
                $solicitud, 
                Auth::user(), 
                $request->all()
            );

            // If it's an acceptance, send additional notifications
            if ($request->decision_medica === 'aceptar') {
                $this->notificationService->sendAcceptanceNotification($solicitud);
            }

            DB::commit();

            $message = match($request->decision_medica) {
                'aceptar' => 'Caso aceptado exitosamente. Se han enviado las notificaciones correspondientes.',
                'rechazar' => 'Caso rechazado. Se ha notificado a la institución remitente.',
                'solicitar_info' => 'Solicitud de información enviada. Se ha notificado a la institución remitente.',
                default => 'Evaluación guardada exitosamente.'
            };

            return redirect()->route('medico.bandeja-casos')
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'Error al guardar la evaluación: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Get case details for AJAX requests
     */
    public function obtenerDetalles($id)
    {
        try {
            $solicitud = SolicitudMedica::with(['medicoEvaluador'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $solicitud
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener detalles del caso: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel evaluation (return to pending)
     */
    public function cancelarEvaluacion($id)
    {
        try {
            $solicitud = SolicitudMedica::findOrFail($id);

            // Only allow cancellation if currently being evaluated by this user
            if ($solicitud->estado !== 'en_evaluacion' || $solicitud->medico_evaluador_id !== Auth::id()) {
                return redirect()->route('medico.bandeja-casos')
                    ->with('error', 'No se puede cancelar esta evaluación.');
            }

            $solicitud->update([
                'estado' => 'pendiente_evaluacion',
                'medico_evaluador_id' => null
            ]);

            // Log the cancellation
            AuditLog::logActivity(
                'cancel_evaluation',
                'solicitud_medica',
                $solicitud->id,
                ['medico' => Auth::user()->name],
                'Evaluación médica cancelada'
            );

            return redirect()->route('medico.bandeja-casos')
                ->with('info', 'Evaluación cancelada. El caso ha vuelto a la bandeja general.');

        } catch (\Exception $e) {
            return redirect()->route('medico.bandeja-casos')
                ->with('error', 'Error al cancelar la evaluación: ' . $e->getMessage());
        }
    }

    /**
     * Get evaluation history for a case
     */
    public function historialEvaluacion($id)
    {
        try {
            $solicitud = SolicitudMedica::with(['medicoEvaluador'])->findOrFail($id);

            // Get audit logs for this case
            $historial = AuditLog::where('resource_type', 'solicitud_medica')
                ->where('resource_id', $id)
                ->with(['user'])
                ->orderBy('timestamp', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'solicitud' => $solicitud,
                    'historial' => $historial
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener historial: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get my evaluations (for the authenticated medico)
     */
    public function misEvaluaciones(Request $request)
    {
        $query = SolicitudMedica::where('medico_evaluador_id', Auth::id())
            ->whereNotNull('fecha_evaluacion');

        // Apply filters
        if ($request->filled('decision')) {
            $query->where('decision_medica', $request->decision);
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_evaluacion', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_evaluacion', '<=', $request->fecha_hasta);
        }

        $evaluaciones = $query->orderBy('fecha_evaluacion', 'desc')
            ->paginate(15);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $evaluaciones
            ]);
        }

        return view('medico.mis-evaluaciones', compact('evaluaciones'));
    }

    /**
     * Get evaluation statistics for the authenticated medico
     */
    public function estadisticasEvaluacion()
    {
        $medico = Auth::user();
        $today = Carbon::today();
        $thisWeek = Carbon::now()->startOfWeek();
        $thisMonth = Carbon::now()->startOfMonth();

        $stats = [
            'total_evaluaciones' => SolicitudMedica::where('medico_evaluador_id', $medico->id)->count(),
            'evaluaciones_hoy' => SolicitudMedica::where('medico_evaluador_id', $medico->id)
                ->whereDate('fecha_evaluacion', $today)->count(),
            'evaluaciones_semana' => SolicitudMedica::where('medico_evaluador_id', $medico->id)
                ->where('fecha_evaluacion', '>=', $thisWeek)->count(),
            'evaluaciones_mes' => SolicitudMedica::where('medico_evaluador_id', $medico->id)
                ->where('fecha_evaluacion', '>=', $thisMonth)->count(),
            'casos_aceptados' => SolicitudMedica::where('medico_evaluador_id', $medico->id)
                ->where('decision_medica', 'aceptar')->count(),
            'casos_rechazados' => SolicitudMedica::where('medico_evaluador_id', $medico->id)
                ->where('decision_medica', 'rechazar')->count(),
            'info_solicitada' => SolicitudMedica::where('medico_evaluador_id', $medico->id)
                ->where('decision_medica', 'solicitar_info')->count(),
            'tiempo_promedio_respuesta' => $this->getTiempoPromedioRespuesta($medico),
            'por_especialidad' => $this->getEvaluacionesPorEspecialidad($medico),
            'por_prioridad' => $this->getEvaluacionesPorPrioridad($medico)
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get average response time for medico
     */
    private function getTiempoPromedioRespuesta($medico): float
    {
        $avgTime = SolicitudMedica::where('medico_evaluador_id', $medico->id)
            ->whereNotNull('fecha_evaluacion')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, fecha_recepcion_email, fecha_evaluacion)) as avg_time')
            ->value('avg_time');

        return round($avgTime ?? 0, 1);
    }

    /**
     * Get evaluations by specialty
     */
    private function getEvaluacionesPorEspecialidad($medico): array
    {
        return SolicitudMedica::where('medico_evaluador_id', $medico->id)
            ->select('especialidad_solicitada')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN decision_medica = "aceptar" THEN 1 ELSE 0 END) as aceptados')
            ->groupBy('especialidad_solicitada')
            ->orderByDesc('total')
            ->get()
            ->toArray();
    }

    /**
     * Get evaluations by priority
     */
    private function getEvaluacionesPorPrioridad($medico): array
    {
        return SolicitudMedica::where('medico_evaluador_id', $medico->id)
            ->select('prioridad_ia')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN decision_medica = "aceptar" THEN 1 ELSE 0 END) as aceptados')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, fecha_recepcion_email, fecha_evaluacion)) as tiempo_promedio')
            ->groupBy('prioridad_ia')
            ->get()
            ->toArray();
    }
}
