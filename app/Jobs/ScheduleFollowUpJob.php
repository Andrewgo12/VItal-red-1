<?php

namespace App\Jobs;

use App\Models\SolicitudMedica;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ScheduleFollowUpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected SolicitudMedica $solicitud;
    protected User $evaluador;

    /**
     * Create a new job instance.
     */
    public function __construct(SolicitudMedica $solicitud, User $evaluador)
    {
        $this->solicitud = $solicitud;
        $this->evaluador = $evaluador;
    }

    /**
     * Execute the job.
     */
    public function handle(NotificationService $notificationService): void
    {
        try {
            // Verificar si la solicitud aún existe y está en estado aceptada
            $solicitud = SolicitudMedica::find($this->solicitud->id);
            
            if (!$solicitud) {
                Log::warning('Solicitud no encontrada para seguimiento', [
                    'solicitud_id' => $this->solicitud->id
                ]);
                return;
            }

            if ($solicitud->estado !== 'aceptada') {
                Log::info('Solicitud ya no está en estado aceptada, cancelando seguimiento', [
                    'solicitud_id' => $solicitud->id,
                    'estado_actual' => $solicitud->estado
                ]);
                return;
            }

            // Verificar si el evaluador aún existe y está activo
            $evaluador = User::find($this->evaluador->id);
            
            if (!$evaluador || !$evaluador->activo) {
                Log::warning('Evaluador no encontrado o inactivo para seguimiento', [
                    'evaluador_id' => $this->evaluador->id,
                    'solicitud_id' => $solicitud->id
                ]);
                return;
            }

            // Crear notificación de seguimiento
            $notificationService->sendFollowUpReminder($evaluador, $solicitud);

            // Registrar el seguimiento en la base de datos
            \App\Models\Notification::create([
                'user_id' => $evaluador->id,
                'type' => 'follow_up_reminder',
                'title' => 'Recordatorio de Seguimiento',
                'message' => "Recordatorio: Seguimiento del caso de {$solicitud->nombre_paciente}",
                'data' => [
                    'solicitud_id' => $solicitud->id,
                    'patient_name' => $solicitud->nombre_paciente,
                    'specialty' => $solicitud->especialidad_sugerida,
                    'accepted_at' => $solicitud->updated_at->toISOString(),
                    'follow_up_type' => '24_hour_reminder'
                ],
                'read_at' => null
            ]);

            // Programar próximo seguimiento si es necesario
            if ($solicitud->puntuacion_urgencia >= 80) {
                // Para casos urgentes, programar seguimiento adicional en 48 horas
                self::dispatch($solicitud, $evaluador)
                    ->delay(now()->addHours(48));
                
                Log::info('Seguimiento adicional programado para caso urgente', [
                    'solicitud_id' => $solicitud->id,
                    'next_follow_up' => now()->addHours(48)->toISOString()
                ]);
            }

            Log::info('Seguimiento ejecutado exitosamente', [
                'solicitud_id' => $solicitud->id,
                'evaluador_id' => $evaluador->id,
                'patient_name' => $solicitud->nombre_paciente
            ]);

        } catch (\Exception $e) {
            Log::error('Error ejecutando seguimiento', [
                'solicitud_id' => $this->solicitud->id,
                'evaluador_id' => $this->evaluador->id,
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
    public function failed(\Throwable $exception): void
    {
        Log::error('Falló el job de seguimiento', [
            'solicitud_id' => $this->solicitud->id,
            'evaluador_id' => $this->evaluador->id,
            'error' => $exception->getMessage()
        ]);

        // Notificar a administradores sobre el fallo
        try {
            $administradores = User::where('role', 'administrador')
                                  ->where('activo', true)
                                  ->get();

            foreach ($administradores as $admin) {
                \App\Models\Notification::create([
                    'user_id' => $admin->id,
                    'type' => 'system_error',
                    'title' => 'Error en Sistema de Seguimiento',
                    'message' => "Falló el seguimiento para caso ID: {$this->solicitud->id}",
                    'data' => [
                        'solicitud_id' => $this->solicitud->id,
                        'evaluador_id' => $this->evaluador->id,
                        'error' => $exception->getMessage(),
                        'job_type' => 'follow_up'
                    ],
                    'read_at' => null
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error notificando fallo de seguimiento a administradores', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'follow-up',
            'solicitud:' . $this->solicitud->id,
            'evaluador:' . $this->evaluador->id
        ];
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return [60, 300, 900]; // 1 min, 5 min, 15 min
    }

    /**
     * Determine the time at which the job should timeout.
     */
    public function retryUntil(): \DateTime
    {
        return now()->addHours(2);
    }
}
