<?php

namespace App\Services;

use App\Models\NotificacionInterna;
use App\Models\SolicitudMedica;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Event;
use App\Events\UrgentMedicalCaseDetected;
use App\Events\MedicalRequestEvaluated;

class NotificationService
{
    /**
     * Create urgent notification for high-priority medical cases
     */
    public function createUrgentNotification(SolicitudMedica $solicitud): NotificacionInterna
    {
        try {
            $notification = NotificacionInterna::create([
                'solicitud_medica_id' => $solicitud->id,
                'tipo_notificacion' => 'caso_urgente',
                'titulo' => 'Caso Médico Urgente Detectado',
                'mensaje' => $this->buildUrgentMessage($solicitud),
                'datos_adicionales' => [
                    'paciente_nombre' => $solicitud->paciente_nombre,
                    'institucion_remitente' => $solicitud->institucion_remitente,
                    'especialidad_solicitada' => $solicitud->especialidad_solicitada,
                    'prioridad_ia' => $solicitud->prioridad_ia,
                    'score_urgencia' => $solicitud->score_urgencia,
                    'fecha_recepcion' => $solicitud->fecha_recepcion_email,
                    'criterios_priorizacion' => $solicitud->criterios_priorizacion
                ],
                'prioridad' => 'critica',
                'notificar_email' => true,
                'notificar_dashboard' => true,
                'departamento_destinatario' => 'Urgencias'
            ]);

            // Send immediate notifications
            $this->processNotification($notification);

            // Fire event for real-time updates
            Event::dispatch(new UrgentMedicalCaseDetected($solicitud));

            Log::info("Urgent notification created for solicitud {$solicitud->id}");

            return $notification;

        } catch (\Exception $e) {
            Log::error("Error creating urgent notification for solicitud {$solicitud->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create notification for medical request evaluation
     */
    public function createEvaluationNotification(SolicitudMedica $solicitud, string $decision): NotificacionInterna
    {
        try {
            $notificationType = match($decision) {
                'aceptar' => 'solicitud_aceptada',
                'rechazar' => 'solicitud_rechazada',
                'solicitar_info' => 'solicitud_pendiente_info',
                default => 'solicitud_evaluada'
            };

            $notification = NotificacionInterna::create([
                'solicitud_medica_id' => $solicitud->id,
                'tipo_notificacion' => $notificationType,
                'titulo' => $this->getEvaluationTitle($decision),
                'mensaje' => $this->buildEvaluationMessage($solicitud, $decision),
                'datos_adicionales' => [
                    'paciente_nombre' => $solicitud->paciente_nombre,
                    'institucion_remitente' => $solicitud->institucion_remitente,
                    'especialidad_solicitada' => $solicitud->especialidad_solicitada,
                    'decision_medica' => $decision,
                    'medico_evaluador' => $solicitud->medicoEvaluador?->name,
                    'fecha_evaluacion' => $solicitud->fecha_evaluacion,
                    'observaciones_medico' => $solicitud->observaciones_medico
                ],
                'prioridad' => $decision === 'aceptar' ? 'alta' : 'media',
                'notificar_email' => true,
                'notificar_dashboard' => true,
                'departamento_destinatario' => $decision === 'aceptar' ? 'Admisiones' : 'Coordinación'
            ]);

            // Send notifications
            $this->processNotification($notification);

            // Fire event
            Event::dispatch(new MedicalRequestEvaluated($solicitud, $decision));

            Log::info("Evaluation notification created for solicitud {$solicitud->id} with decision: {$decision}");

            return $notification;

        } catch (\Exception $e) {
            Log::error("Error creating evaluation notification for solicitud {$solicitud->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create reminder notification for pending evaluations
     */
    public function createReminderNotification(SolicitudMedica $solicitud): NotificacionInterna
    {
        try {
            $hoursWaiting = $solicitud->fecha_recepcion_email->diffInHours(now());

            $notification = NotificacionInterna::create([
                'solicitud_medica_id' => $solicitud->id,
                'tipo_notificacion' => 'recordatorio_evaluacion',
                'titulo' => 'Recordatorio: Solicitud Pendiente de Evaluación',
                'mensaje' => "La solicitud de {$solicitud->paciente_nombre} lleva {$hoursWaiting} horas esperando evaluación médica.",
                'datos_adicionales' => [
                    'paciente_nombre' => $solicitud->paciente_nombre,
                    'institucion_remitente' => $solicitud->institucion_remitente,
                    'especialidad_solicitada' => $solicitud->especialidad_solicitada,
                    'horas_esperando' => $hoursWaiting,
                    'prioridad_ia' => $solicitud->prioridad_ia
                ],
                'prioridad' => $solicitud->prioridad_ia === 'Alta' ? 'alta' : 'media',
                'notificar_email' => false,
                'notificar_dashboard' => true,
                'departamento_destinatario' => 'Médicos'
            ]);

            $this->processNotification($notification);

            return $notification;

        } catch (\Exception $e) {
            Log::error("Error creating reminder notification for solicitud {$solicitud->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Process notification (send emails, update dashboard, etc.)
     */
    public function processNotification(NotificacionInterna $notification): void
    {
        try {
            // Send email notification if enabled
            if ($notification->notificar_email) {
                $this->sendEmailNotification($notification);
            }

            // Update dashboard notifications if enabled
            if ($notification->notificar_dashboard) {
                $this->updateDashboardNotifications($notification);
            }

            // Mark as sent
            $notification->update([
                'estado' => 'enviada',
                'fecha_envio' => now()
            ]);

        } catch (\Exception $e) {
            Log::error("Error processing notification {$notification->id}: " . $e->getMessage());
            
            $notification->update([
                'estado' => 'fallida',
                'error_ultimo_intento' => $e->getMessage()
            ]);
            
            $notification->incrementarIntentos($e->getMessage());
        }
    }

    /**
     * Send email notification
     */
    private function sendEmailNotification(NotificacionInterna $notification): void
    {
        try {
            // Get recipients based on department and notification type
            $recipients = $this->getNotificationRecipients($notification);

            if (empty($recipients)) {
                Log::warning("No recipients found for notification {$notification->id}");
                return;
            }

            foreach ($recipients as $recipient) {
                Mail::send('emails.medical-notification', [
                    'notification' => $notification,
                    'recipient' => $recipient,
                    'solicitud' => $notification->solicitudMedica
                ], function ($message) use ($notification, $recipient) {
                    $message->to($recipient['email'], $recipient['name'])
                           ->subject($notification->titulo)
                           ->priority($this->getEmailPriority($notification->prioridad));
                });
            }

            Log::info("Email notifications sent for notification {$notification->id} to " . count($recipients) . " recipients");

        } catch (\Exception $e) {
            Log::error("Error sending email notification {$notification->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update dashboard notifications
     */
    private function updateDashboardNotifications(NotificacionInterna $notification): void
    {
        try {
            // This would integrate with a real-time notification system
            // For now, we'll just log and mark as processed
            
            // In a real implementation, this might:
            // - Push to WebSocket connections
            // - Update Redis cache for dashboard
            // - Send push notifications to mobile apps
            
            Log::info("Dashboard notification updated for notification {$notification->id}");

        } catch (\Exception $e) {
            Log::error("Error updating dashboard notification {$notification->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get notification recipients based on department and type
     */
    private function getNotificationRecipients(NotificacionInterna $notification): array
    {
        try {
            $recipients = [];

            // Get users based on department
            switch ($notification->departamento_destinatario) {
                case 'Urgencias':
                    $recipients = User::where('role', 'medico')
                                    ->where('department', 'urgencias')
                                    ->orWhere('role', 'administrador')
                                    ->get(['name', 'email'])
                                    ->toArray();
                    break;

                case 'Admisiones':
                    $recipients = User::where('role', 'administrador')
                                    ->orWhere('department', 'admisiones')
                                    ->get(['name', 'email'])
                                    ->toArray();
                    break;

                case 'Médicos':
                    $recipients = User::where('role', 'medico')
                                    ->get(['name', 'email'])
                                    ->toArray();
                    break;

                default:
                    $recipients = User::where('role', 'administrador')
                                    ->get(['name', 'email'])
                                    ->toArray();
                    break;
            }

            // If specific user is set, add them
            if ($notification->usuario_destinatario_id) {
                $specificUser = User::find($notification->usuario_destinatario_id);
                if ($specificUser) {
                    $recipients[] = [
                        'name' => $specificUser->name,
                        'email' => $specificUser->email
                    ];
                }
            }

            // If specific email is set, add it
            if ($notification->email_destinatario) {
                $recipients[] = [
                    'name' => 'Destinatario Específico',
                    'email' => $notification->email_destinatario
                ];
            }

            return array_unique($recipients, SORT_REGULAR);

        } catch (\Exception $e) {
            Log::error("Error getting notification recipients: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Build urgent message for notification
     */
    private function buildUrgentMessage(SolicitudMedica $solicitud): string
    {
        $message = "Se ha detectado un caso médico urgente que requiere atención inmediata:\n\n";
        $message .= "Paciente: {$solicitud->paciente_nombre}\n";
        $message .= "Institución: {$solicitud->institucion_remitente}\n";
        $message .= "Especialidad: {$solicitud->especialidad_solicitada}\n";
        $message .= "Diagnóstico: {$solicitud->diagnostico_principal}\n";
        $message .= "Prioridad IA: {$solicitud->prioridad_ia}";
        
        if ($solicitud->score_urgencia) {
            $message .= " (Score: {$solicitud->score_urgencia}/100)";
        }
        
        $message .= "\n\nPor favor, evalúe esta solicitud con la mayor brevedad posible.";

        return $message;
    }

    /**
     * Build evaluation message for notification
     */
    private function buildEvaluationMessage(SolicitudMedica $solicitud, string $decision): string
    {
        $decisionText = match($decision) {
            'aceptar' => 'ACEPTADA',
            'rechazar' => 'RECHAZADA',
            'solicitar_info' => 'REQUIERE INFORMACIÓN ADICIONAL',
            default => 'EVALUADA'
        };

        $message = "La solicitud médica ha sido {$decisionText}:\n\n";
        $message .= "Paciente: {$solicitud->paciente_nombre}\n";
        $message .= "Institución: {$solicitud->institucion_remitente}\n";
        $message .= "Especialidad: {$solicitud->especialidad_solicitada}\n";
        $message .= "Evaluado por: " . ($solicitud->medicoEvaluador?->name ?? 'Sistema') . "\n";
        
        if ($solicitud->observaciones_medico) {
            $message .= "Observaciones: {$solicitud->observaciones_medico}\n";
        }

        if ($decision === 'aceptar') {
            $message .= "\nProceda con los trámites de admisión correspondientes.";
        } elseif ($decision === 'solicitar_info') {
            $message .= "\nContacte a la institución remitente para obtener la información adicional requerida.";
        }

        return $message;
    }

    /**
     * Get evaluation title based on decision
     */
    private function getEvaluationTitle(string $decision): string
    {
        return match($decision) {
            'aceptar' => 'Solicitud de Traslado Aceptada',
            'rechazar' => 'Solicitud de Traslado Rechazada',
            'solicitar_info' => 'Solicitud Requiere Información Adicional',
            default => 'Solicitud Médica Evaluada'
        };
    }

    /**
     * Get email priority based on notification priority
     */
    private function getEmailPriority(string $priority): int
    {
        return match($priority) {
            'critica' => 1, // High priority
            'alta' => 2,    // High priority
            'media' => 3,   // Normal priority
            'baja' => 4,    // Low priority
            default => 3    // Normal priority
        };
    }

    /**
     * Process pending notifications (for scheduled job)
     */
    public function processPendingNotifications(): int
    {
        try {
            $pendingNotifications = NotificacionInterna::pendientes()
                ->where('created_at', '<=', now()->subMinutes(1)) // Wait at least 1 minute
                ->limit(50) // Process in batches
                ->get();

            $processed = 0;

            foreach ($pendingNotifications as $notification) {
                try {
                    $this->processNotification($notification);
                    $processed++;
                } catch (\Exception $e) {
                    Log::error("Error processing pending notification {$notification->id}: " . $e->getMessage());
                }
            }

            if ($processed > 0) {
                Log::info("Processed {$processed} pending notifications");
            }

            return $processed;

        } catch (\Exception $e) {
            Log::error("Error processing pending notifications: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Retry failed notifications
     */
    public function retryFailedNotifications(): int
    {
        try {
            $failedNotifications = NotificacionInterna::where('estado', 'fallida')
                ->where(function ($query) {
                    $query->whereNull('proximo_intento')
                          ->orWhere('proximo_intento', '<=', now());
                })
                ->where('intentos_envio', '<', 5)
                ->limit(20)
                ->get();

            $retried = 0;

            foreach ($failedNotifications as $notification) {
                try {
                    $notification->update(['estado' => 'pendiente']);
                    $this->processNotification($notification);
                    $retried++;
                } catch (\Exception $e) {
                    Log::error("Error retrying failed notification {$notification->id}: " . $e->getMessage());
                }
            }

            if ($retried > 0) {
                Log::info("Retried {$retried} failed notifications");
            }

            return $retried;

        } catch (\Exception $e) {
            Log::error("Error retrying failed notifications: " . $e->getMessage());
            return 0;
        }
    }
}
