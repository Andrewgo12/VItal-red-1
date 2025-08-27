<?php

namespace App\Listeners;

use App\Events\MedicalRequestEvaluated;
use App\Services\NotificationService;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class LogMedicalRequestEvaluation implements ShouldQueue
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
    public function handle(MedicalRequestEvaluated $event): void
    {
        try {
            $solicitud = $event->solicitud;
            $evaluador = $event->evaluador;
            $decision = $event->decision;

            // Registrar la evaluación en el log
            Log::info('Solicitud médica evaluada', [
                'solicitud_id' => $solicitud->id,
                'paciente' => $solicitud->nombre_paciente,
                'evaluador_id' => $evaluador->id,
                'evaluador_nombre' => $evaluador->name,
                'decision' => $decision,
                'especialidad' => $solicitud->especialidad_sugerida,
                'puntuacion_urgencia' => $solicitud->puntuacion_urgencia,
                'institucion_origen' => $solicitud->institucion_origen,
                'timestamp' => now()->toISOString()
            ]);

            // Notificar al médico solicitante si la solicitud fue rechazada o derivada
            if (in_array($decision, ['rechazada', 'derivada'])) {
                $this->notifyRequestingPhysician($solicitud, $evaluador, $decision);
            }

            // Notificar a administradores sobre evaluaciones importantes
            if ($solicitud->puntuacion_urgencia >= 80) {
                $this->notifyAdministrators($solicitud, $evaluador, $decision);
            }

            // Actualizar métricas del médico evaluador
            $this->updatePhysicianMetrics($evaluador, $decision);

            // Crear registro de auditoría
            $this->createAuditRecord($solicitud, $evaluador, $decision);

            // Si la solicitud fue aceptada, programar seguimiento
            if ($decision === 'aceptada') {
                $this->scheduleFollowUp($solicitud, $evaluador);
            }

        } catch (\Exception $e) {
            Log::error('Error procesando evaluación de solicitud médica', [
                'solicitud_id' => $event->solicitud->id,
                'evaluador_id' => $event->evaluador->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Notificar al médico solicitante
     */
    private function notifyRequestingPhysician($solicitud, $evaluador, $decision): void
    {
        try {
            // En un sistema real, aquí buscaríamos el email del médico solicitante
            // Por ahora, registramos la intención de notificar
            Log::info('Notificación pendiente para médico solicitante', [
                'solicitud_id' => $solicitud->id,
                'medico_solicitante' => $solicitud->medico_solicitante,
                'decision' => $decision,
                'evaluador' => $evaluador->name
            ]);

            // Si tenemos el email del médico solicitante, enviar notificación
            if ($solicitud->email_medico_solicitante) {
                $this->notificationService->sendEvaluationResultEmail(
                    $solicitud->email_medico_solicitante,
                    $solicitud,
                    $decision,
                    $evaluador
                );
            }

        } catch (\Exception $e) {
            Log::error('Error notificando a médico solicitante', [
                'solicitud_id' => $solicitud->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Notificar a administradores
     */
    private function notifyAdministrators($solicitud, $evaluador, $decision): void
    {
        try {
            $administradores = User::where('role', 'administrador')
                                  ->where('activo', true)
                                  ->get();

            foreach ($administradores as $admin) {
                \App\Models\Notification::create([
                    'user_id' => $admin->id,
                    'type' => 'medical_evaluation',
                    'title' => 'Evaluación de Caso Urgente',
                    'message' => "Caso urgente evaluado: {$solicitud->nombre_paciente} - Decisión: {$decision}",
                    'data' => [
                        'solicitud_id' => $solicitud->id,
                        'evaluador_id' => $evaluador->id,
                        'evaluador_nombre' => $evaluador->name,
                        'decision' => $decision,
                        'urgency_score' => $solicitud->puntuacion_urgencia
                    ],
                    'read_at' => null
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error notificando a administradores', [
                'solicitud_id' => $solicitud->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Actualizar métricas del médico
     */
    private function updatePhysicianMetrics($evaluador, $decision): void
    {
        try {
            // Incrementar contador de evaluaciones
            $evaluador->increment('evaluaciones_realizadas');

            // Actualizar contadores específicos por decisión
            switch ($decision) {
                case 'aceptada':
                    $evaluador->increment('casos_aceptados');
                    break;
                case 'rechazada':
                    $evaluador->increment('casos_rechazados');
                    break;
                case 'derivada':
                    $evaluador->increment('casos_derivados');
                    break;
            }

            // Actualizar timestamp de última evaluación
            $evaluador->update(['ultima_evaluacion' => now()]);

        } catch (\Exception $e) {
            Log::error('Error actualizando métricas del médico', [
                'evaluador_id' => $evaluador->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Crear registro de auditoría
     */
    private function createAuditRecord($solicitud, $evaluador, $decision): void
    {
        try {
            \App\Models\AuditLog::create([
                'user_id' => $evaluador->id,
                'action' => 'medical_evaluation',
                'model_type' => 'App\Models\SolicitudMedica',
                'model_id' => $solicitud->id,
                'old_values' => ['estado' => $solicitud->getOriginal('estado')],
                'new_values' => ['estado' => $solicitud->estado, 'decision' => $decision],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => [
                    'paciente' => $solicitud->nombre_paciente,
                    'especialidad' => $solicitud->especialidad_sugerida,
                    'urgencia' => $solicitud->puntuacion_urgencia,
                    'institucion' => $solicitud->institucion_origen
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error creando registro de auditoría', [
                'solicitud_id' => $solicitud->id,
                'evaluador_id' => $evaluador->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Programar seguimiento
     */
    private function scheduleFollowUp($solicitud, $evaluador): void
    {
        try {
            // Programar recordatorio de seguimiento en 24 horas
            \App\Jobs\ScheduleFollowUpJob::dispatch($solicitud, $evaluador)
                ->delay(now()->addHours(24));

            Log::info('Seguimiento programado', [
                'solicitud_id' => $solicitud->id,
                'evaluador_id' => $evaluador->id,
                'scheduled_for' => now()->addHours(24)->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Error programando seguimiento', [
                'solicitud_id' => $solicitud->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(MedicalRequestEvaluated $event, \Throwable $exception): void
    {
        Log::error('Falló el procesamiento de evaluación médica', [
            'solicitud_id' => $event->solicitud->id,
            'evaluador_id' => $event->evaluador->id,
            'error' => $exception->getMessage()
        ]);
    }
}
