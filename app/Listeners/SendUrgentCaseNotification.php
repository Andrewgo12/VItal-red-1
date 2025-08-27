<?php

namespace App\Listeners;

use App\Events\UrgentMedicalCaseDetected;
use App\Services\NotificationService;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendUrgentCaseNotification implements ShouldQueue
{
    use InteractsWithQueue;

    protected NotificationService $notificationService;

    /**
     * Create the event listener.
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the event.
     */
    public function handle(UrgentMedicalCaseDetected $event): void
    {
        try {
            $solicitud = $event->solicitud;
            
            // Obtener médicos de la especialidad correspondiente
            $medicos = User::where('role', 'medico')
                          ->where('especialidad', $solicitud->especialidad_sugerida)
                          ->where('activo', true)
                          ->get();

            // Obtener administradores
            $administradores = User::where('role', 'administrador')
                                  ->where('activo', true)
                                  ->get();

            // Combinar destinatarios
            $destinatarios = $medicos->merge($administradores);

            foreach ($destinatarios as $destinatario) {
                // Enviar notificación por email
                $this->notificationService->sendUrgentCaseEmail(
                    $destinatario,
                    $solicitud
                );

                // Enviar notificación push si está habilitada
                if ($destinatario->push_notifications_enabled) {
                    $this->notificationService->sendPushNotification(
                        $destinatario,
                        'Caso Médico Urgente',
                        "Nuevo caso urgente: {$solicitud->nombre_paciente} - {$solicitud->especialidad_sugerida}",
                        [
                            'type' => 'urgent_case',
                            'solicitud_id' => $solicitud->id,
                            'priority' => $solicitud->prioridad_ia,
                            'urgency_score' => $solicitud->puntuacion_urgencia
                        ]
                    );
                }

                // Enviar SMS si está habilitado y es muy urgente
                if ($solicitud->puntuacion_urgencia >= 90 && 
                    $destinatario->sms_notifications_enabled && 
                    $destinatario->telefono) {
                    
                    $this->notificationService->sendSMS(
                        $destinatario->telefono,
                        "URGENTE: Nuevo caso médico crítico en Vital Red. Paciente: {$solicitud->nombre_paciente}. Ingresar al sistema inmediatamente."
                    );
                }
            }

            // Registrar en el log
            Log::info('Notificaciones de caso urgente enviadas', [
                'solicitud_id' => $solicitud->id,
                'paciente' => $solicitud->nombre_paciente,
                'especialidad' => $solicitud->especialidad_sugerida,
                'puntuacion_urgencia' => $solicitud->puntuacion_urgencia,
                'destinatarios_count' => $destinatarios->count()
            ]);

            // Crear registro de notificación en la base de datos
            foreach ($destinatarios as $destinatario) {
                \App\Models\Notification::create([
                    'user_id' => $destinatario->id,
                    'type' => 'urgent_medical_case',
                    'title' => 'Caso Médico Urgente',
                    'message' => "Nuevo caso urgente: {$solicitud->nombre_paciente} - {$solicitud->especialidad_sugerida}",
                    'data' => [
                        'solicitud_id' => $solicitud->id,
                        'priority' => $solicitud->prioridad_ia,
                        'urgency_score' => $solicitud->puntuacion_urgencia,
                        'patient_name' => $solicitud->nombre_paciente,
                        'specialty' => $solicitud->especialidad_sugerida
                    ],
                    'read_at' => null,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error enviando notificaciones de caso urgente', [
                'solicitud_id' => $event->solicitud->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Re-lanzar la excepción para que el job falle y se reintente
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(UrgentMedicalCaseDetected $event, \Throwable $exception): void
    {
        Log::error('Falló el envío de notificaciones de caso urgente', [
            'solicitud_id' => $event->solicitud->id,
            'error' => $exception->getMessage()
        ]);

        // Notificar a los administradores sobre el fallo
        $administradores = User::where('role', 'administrador')
                              ->where('activo', true)
                              ->get();

        foreach ($administradores as $admin) {
            try {
                $this->notificationService->sendSystemAlert(
                    $admin,
                    'Error en Sistema de Notificaciones',
                    "Falló el envío de notificaciones para caso urgente ID: {$event->solicitud->id}. Error: {$exception->getMessage()}"
                );
            } catch (\Exception $e) {
                Log::error('Error enviando alerta de sistema', [
                    'admin_id' => $admin->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}
