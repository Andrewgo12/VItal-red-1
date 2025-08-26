<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\PythonGmailService;
use App\Models\SolicitudMedica;
use App\Models\NotificacionInterna;
use App\Models\AuditoriaSolicitud;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class GmailMonitorController extends Controller
{
    private PythonGmailService $pythonGmailService;
    
    public function __construct(PythonGmailService $pythonGmailService)
    {
        $this->pythonGmailService = $pythonGmailService;
    }
    
    /**
     * Start Gmail monitoring service
     */
    public function startMonitoring(): JsonResponse
    {
        try {
            $result = $this->pythonGmailService->startGmailMonitoring();
            
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Gmail monitoring started successfully',
                    'data' => $result
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to start Gmail monitoring',
                    'error' => $result['error'] ?? 'Unknown error'
                ], 500);
            }
            
        } catch (\Exception $e) {
            Log::error('Error in startMonitoring: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Stop Gmail monitoring service
     */
    public function stopMonitoring(): JsonResponse
    {
        try {
            $result = $this->pythonGmailService->stopGmailMonitoring();
            
            return response()->json([
                'success' => true,
                'message' => 'Gmail monitoring stop signal sent',
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in stopMonitoring: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get Gmail monitoring status
     */
    public function getStatus(): JsonResponse
    {
        try {
            $result = $this->pythonGmailService->getMonitoringStatus();
            
            return response()->json([
                'success' => $result['success'],
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in getStatus: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Process a single email manually
     */
    public function processSingleEmail(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email_id' => 'required|string'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }
            
            $result = $this->pythonGmailService->processSingleEmail($request->email_id);
            
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Email processed successfully',
                    'data' => $result
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to process email',
                    'error' => $result['error'] ?? 'Unknown error'
                ], 500);
            }
            
        } catch (\Exception $e) {
            Log::error('Error in processSingleEmail: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Receive processed medical case from Python service
     * This endpoint is called by the Python service when a medical email is processed
     */
    public function receiveMedicalCase(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email_unique_id' => 'required|string',
                'paciente_nombre' => 'required|string',
                'institucion_remitente' => 'required|string',
                'email_remitente' => 'required|email',
                'diagnostico_principal' => 'required|string',
                'motivo_consulta' => 'required|string',
                'especialidad_solicitada' => 'required|string',
                'tipo_solicitud' => 'required|in:consulta,hospitalizacion,cirugia,urgencia,otro',
                'motivo_remision' => 'required|string',
                'prioridad' => 'required|in:Alta,Media,Baja'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }
            
            // Check if email already processed
            $existingSolicitud = SolicitudMedica::where('email_unique_id', $request->email_unique_id)->first();
            if ($existingSolicitud) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email already processed',
                    'solicitud_id' => $existingSolicitud->id
                ], 409);
            }
            
            // Create solicitud médica
            $solicitud = $this->pythonGmailService->receiveMedicalCase($request->all());
            
            // Create urgent notification if high priority
            if ($solicitud->prioridad_ia === 'Alta') {
                $this->createUrgentNotification($solicitud);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Medical case received successfully',
                'solicitud_id' => $solicitud->id,
                'data' => [
                    'id' => $solicitud->id,
                    'email_unique_id' => $solicitud->email_unique_id,
                    'paciente_nombre' => $solicitud->paciente_nombre,
                    'prioridad_ia' => $solicitud->prioridad_ia,
                    'estado' => $solicitud->estado,
                    'fecha_recepcion' => $solicitud->fecha_recepcion_email
                ]
            ], 201);
            
        } catch (\Exception $e) {
            Log::error('Error in receiveMedicalCase: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Receive urgent notification from Python service
     */
    public function receiveUrgentNotification(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'type' => 'required|string',
                'patient_name' => 'required|string',
                'institution' => 'required|string',
                'priority' => 'required|string',
                'specialty' => 'required|string'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }
            
            // Find the solicitud by patient name and institution
            $solicitud = SolicitudMedica::where('paciente_nombre', $request->patient_name)
                ->where('institucion_remitente', $request->institution)
                ->orderBy('created_at', 'desc')
                ->first();
            
            if ($solicitud) {
                $this->createUrgentNotification($solicitud);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Urgent notification created',
                    'solicitud_id' => $solicitud->id
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Solicitud not found for urgent notification'
                ], 404);
            }
            
        } catch (\Exception $e) {
            Log::error('Error in receiveUrgentNotification: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get processing statistics
     */
    public function getStatistics(): JsonResponse
    {
        try {
            $pythonStats = $this->pythonGmailService->getProcessingStatistics();
            
            // Get Laravel statistics
            $laravelStats = [
                'total_solicitudes' => SolicitudMedica::count(),
                'solicitudes_pendientes' => SolicitudMedica::pendientesEvaluacion()->count(),
                'solicitudes_urgentes' => SolicitudMedica::urgentes()->count(),
                'solicitudes_hoy' => SolicitudMedica::whereDate('created_at', today())->count(),
                'notificaciones_pendientes' => NotificacionInterna::pendientes()->count(),
                'ultima_actualizacion' => now()->toISOString()
            ];
            
            return response()->json([
                'success' => true,
                'data' => [
                    'python_statistics' => $pythonStats,
                    'laravel_statistics' => $laravelStats
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in getStatistics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Test Python environment
     */
    public function testEnvironment(): JsonResponse
    {
        try {
            $result = $this->pythonGmailService->testPythonEnvironment();
            
            return response()->json([
                'success' => $result['success'],
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in testEnvironment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Create urgent notification for high-priority cases
     */
    private function createUrgentNotification(SolicitudMedica $solicitud): void
    {
        try {
            NotificacionInterna::create([
                'solicitud_medica_id' => $solicitud->id,
                'tipo_notificacion' => 'caso_urgente',
                'titulo' => 'Caso Médico Urgente Detectado',
                'mensaje' => "Se ha detectado un caso médico urgente: {$solicitud->paciente_nombre} de {$solicitud->institucion_remitente}. Especialidad: {$solicitud->especialidad_solicitada}",
                'datos_adicionales' => [
                    'paciente_nombre' => $solicitud->paciente_nombre,
                    'institucion_remitente' => $solicitud->institucion_remitente,
                    'especialidad_solicitada' => $solicitud->especialidad_solicitada,
                    'prioridad_ia' => $solicitud->prioridad_ia,
                    'score_urgencia' => $solicitud->score_urgencia
                ],
                'prioridad' => 'critica',
                'notificar_email' => true,
                'notificar_dashboard' => true,
                'departamento_destinatario' => 'Urgencias'
            ]);
            
            Log::info("Urgent notification created for solicitud {$solicitud->id}");
            
        } catch (\Exception $e) {
            Log::error("Error creating urgent notification for solicitud {$solicitud->id}: " . $e->getMessage());
        }
    }
}
