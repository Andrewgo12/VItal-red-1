<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\SolicitudMedica;
use App\Models\NotificacionInterna;
use App\Models\AuditoriaSolicitud;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class SolicitudMedicaController extends Controller
{
    /**
     * Get paginated list of medical requests
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = SolicitudMedica::with(['medicoEvaluador']);
            
            // Apply filters
            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }
            
            if ($request->has('prioridad')) {
                $query->where('prioridad_ia', $request->prioridad);
            }
            
            if ($request->has('especialidad')) {
                $query->where('especialidad_solicitada', 'like', '%' . $request->especialidad . '%');
            }
            
            if ($request->has('institucion')) {
                $query->where('institucion_remitente', 'like', '%' . $request->institucion . '%');
            }
            
            if ($request->has('fecha_desde')) {
                $query->whereDate('fecha_recepcion_email', '>=', $request->fecha_desde);
            }
            
            if ($request->has('fecha_hasta')) {
                $query->whereDate('fecha_recepcion_email', '<=', $request->fecha_hasta);
            }
            
            // Search by patient name or ID
            if ($request->has('buscar')) {
                $search = $request->buscar;
                $query->where(function ($q) use ($search) {
                    $q->where('paciente_nombre', 'like', '%' . $search . '%')
                      ->orWhere('paciente_apellidos', 'like', '%' . $search . '%')
                      ->orWhere('paciente_identificacion', 'like', '%' . $search . '%');
                });
            }
            
            // Order by priority and date
            $query->orderByRaw("
                CASE prioridad_ia 
                    WHEN 'Alta' THEN 1 
                    WHEN 'Media' THEN 2 
                    WHEN 'Baja' THEN 3 
                    ELSE 4 
                END
            ")->orderBy('fecha_recepcion_email', 'desc');
            
            $solicitudes = $query->paginate($request->get('per_page', 15));
            
            return response()->json([
                'success' => true,
                'data' => $solicitudes
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in SolicitudMedicaController@index: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving medical requests',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get specific medical request
     */
    public function show(int $id): JsonResponse
    {
        try {
            $solicitud = SolicitudMedica::with(['medicoEvaluador', 'notificaciones', 'auditoria'])
                ->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $solicitud
            ]);
            
        } catch (\Exception $e) {
            Log::error("Error in SolicitudMedicaController@show for ID {$id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Medical request not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }
    
    /**
     * Create new medical request (manual entry)
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'paciente_nombre' => 'required|string|max:255',
                'institucion_remitente' => 'required|string|max:255',
                'email_remitente' => 'required|email',
                'diagnostico_principal' => 'required|string',
                'motivo_consulta' => 'required|string',
                'especialidad_solicitada' => 'required|string|max:255',
                'tipo_solicitud' => 'required|in:consulta,hospitalizacion,cirugia,urgencia,otro',
                'motivo_remision' => 'required|string',
                'prioridad_ia' => 'required|in:Alta,Media,Baja'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }
            
            DB::beginTransaction();
            
            $solicitudData = $request->all();
            $solicitudData['email_unique_id'] = uniqid('manual_');
            $solicitudData['fecha_recepcion_email'] = now();
            $solicitudData['fecha_procesamiento_ia'] = now();
            $solicitudData['estado'] = 'recibida';
            
            $solicitud = SolicitudMedica::create($solicitudData);
            
            // Register audit entry
            AuditoriaSolicitud::registrarAccion(
                $solicitud->id,
                'solicitud_recibida',
                'Solicitud médica creada manualmente',
                null,
                $solicitudData
            );
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Medical request created successfully',
                'data' => $solicitud
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in SolicitudMedicaController@store: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating medical request',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update medical request
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $solicitud = SolicitudMedica::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'paciente_nombre' => 'sometimes|string|max:255',
                'diagnostico_principal' => 'sometimes|string',
                'motivo_consulta' => 'sometimes|string',
                'especialidad_solicitada' => 'sometimes|string|max:255',
                'tipo_solicitud' => 'sometimes|in:consulta,hospitalizacion,cirugia,urgencia,otro',
                'motivo_remision' => 'sometimes|string',
                'observaciones_adicionales' => 'sometimes|string|nullable'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }
            
            DB::beginTransaction();
            
            $valoresAnteriores = $solicitud->toArray();
            $solicitud->update($request->all());
            
            // Register audit entry
            AuditoriaSolicitud::registrarAccion(
                $solicitud->id,
                'estado_modificado',
                'Solicitud médica actualizada',
                $valoresAnteriores,
                $request->all()
            );
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Medical request updated successfully',
                'data' => $solicitud->fresh()
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error in SolicitudMedicaController@update for ID {$id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating medical request',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Evaluate medical request (accept/reject/request info)
     */
    public function evaluar(Request $request, int $id): JsonResponse
    {
        try {
            $solicitud = SolicitudMedica::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'decision_medica' => 'required|in:aceptar,rechazar,solicitar_info',
                'observaciones_medico' => 'required|string',
                'prioridad_medica' => 'sometimes|in:Alta,Media,Baja'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }
            
            DB::beginTransaction();
            
            $valoresAnteriores = $solicitud->toArray();
            
            // Update solicitud with evaluation
            $updateData = [
                'medico_evaluador_id' => auth()->id(),
                'decision_medica' => $request->decision_medica,
                'observaciones_medico' => $request->observaciones_medico,
                'fecha_evaluacion' => now()
            ];
            
            if ($request->has('prioridad_medica')) {
                $updateData['prioridad_medica'] = $request->prioridad_medica;
            }
            
            // Update status based on decision
            switch ($request->decision_medica) {
                case 'aceptar':
                    $updateData['estado'] = 'aceptada';
                    break;
                case 'rechazar':
                    $updateData['estado'] = 'rechazada';
                    break;
                case 'solicitar_info':
                    $updateData['estado'] = 'pendiente_info';
                    break;
            }
            
            $solicitud->update($updateData);
            
            // Create notification if accepted
            if ($request->decision_medica === 'aceptar') {
                $this->createAcceptanceNotification($solicitud);
            }
            
            // Register audit entry
            AuditoriaSolicitud::registrarAccion(
                $solicitud->id,
                'solicitud_' . $request->decision_medica,
                "Solicitud {$request->decision_medica} por médico evaluador",
                $valoresAnteriores,
                $updateData
            );
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Medical request evaluated successfully',
                'data' => $solicitud->fresh(['medicoEvaluador'])
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error in SolicitudMedicaController@evaluar for ID {$id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error evaluating medical request',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get pending medical requests for evaluation
     */
    public function pendientesEvaluacion(Request $request): JsonResponse
    {
        try {
            $query = SolicitudMedica::pendientesEvaluacion()
                ->with(['medicoEvaluador'])
                ->orderByRaw("
                    CASE prioridad_ia 
                        WHEN 'Alta' THEN 1 
                        WHEN 'Media' THEN 2 
                        WHEN 'Baja' THEN 3 
                        ELSE 4 
                    END
                ")->orderBy('fecha_recepcion_email', 'asc');
            
            $solicitudes = $query->paginate($request->get('per_page', 10));
            
            return response()->json([
                'success' => true,
                'data' => $solicitudes
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in SolicitudMedicaController@pendientesEvaluacion: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving pending requests',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get urgent medical requests
     */
    public function urgentes(Request $request): JsonResponse
    {
        try {
            $query = SolicitudMedica::urgentes()
                ->with(['medicoEvaluador'])
                ->orderBy('fecha_recepcion_email', 'asc');
            
            $solicitudes = $query->paginate($request->get('per_page', 10));
            
            return response()->json([
                'success' => true,
                'data' => $solicitudes
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in SolicitudMedicaController@urgentes: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving urgent requests',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Create acceptance notification
     */
    private function createAcceptanceNotification(SolicitudMedica $solicitud): void
    {
        try {
            NotificacionInterna::create([
                'solicitud_medica_id' => $solicitud->id,
                'tipo_notificacion' => 'solicitud_aceptada',
                'titulo' => 'Solicitud de Traslado Aceptada',
                'mensaje' => "La solicitud de traslado para {$solicitud->paciente_nombre} de {$solicitud->institucion_remitente} ha sido aceptada. Especialidad: {$solicitud->especialidad_solicitada}",
                'datos_adicionales' => [
                    'paciente_nombre' => $solicitud->paciente_nombre,
                    'institucion_remitente' => $solicitud->institucion_remitente,
                    'especialidad_solicitada' => $solicitud->especialidad_solicitada,
                    'medico_evaluador' => auth()->user()->name,
                    'fecha_evaluacion' => $solicitud->fecha_evaluacion
                ],
                'prioridad' => $solicitud->prioridad_ia === 'Alta' ? 'alta' : 'media',
                'notificar_email' => true,
                'notificar_dashboard' => true,
                'departamento_destinatario' => 'Admisiones'
            ]);
            
        } catch (\Exception $e) {
            Log::error("Error creating acceptance notification for solicitud {$solicitud->id}: " . $e->getMessage());
        }
    }
}
