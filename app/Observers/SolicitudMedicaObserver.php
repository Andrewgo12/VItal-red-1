<?php

namespace App\Observers;

use App\Models\SolicitudMedica;
use App\Models\MetricaSistema;
use App\Jobs\SendUrgentCaseNotificationJob;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class SolicitudMedicaObserver
{
    /**
     * Handle the SolicitudMedica "creating" event.
     */
    public function creating(SolicitudMedica $solicitudMedica): void
    {
        // Set default values if not provided
        if (!$solicitudMedica->fecha_recepcion_email) {
            $solicitudMedica->fecha_recepcion_email = now();
        }

        if (!$solicitudMedica->estado) {
            $solicitudMedica->estado = 'pendiente_evaluacion';
        }

        if (!$solicitudMedica->score_urgencia) {
            $solicitudMedica->score_urgencia = 50;
        }

        if (!$solicitudMedica->prioridad_ia) {
            $solicitudMedica->prioridad_ia = 'Media';
        }

        // Generate unique email_id if not provided
        if (!$solicitudMedica->email_id) {
            $solicitudMedica->email_id = 'manual_' . uniqid();
        }

        Log::info('Creating new medical request', [
            'paciente_nombre' => $solicitudMedica->paciente_nombre,
            'especialidad_solicitada' => $solicitudMedica->especialidad_solicitada,
            'prioridad_ia' => $solicitudMedica->prioridad_ia
        ]);
    }

    /**
     * Handle the SolicitudMedica "created" event.
     */
    public function created(SolicitudMedica $solicitudMedica): void
    {
        // Log the creation
        Log::info('Medical request created successfully', [
            'id' => $solicitudMedica->id,
            'paciente_nombre' => $solicitudMedica->paciente_nombre,
            'especialidad_solicitada' => $solicitudMedica->especialidad_solicitada,
            'prioridad_ia' => $solicitudMedica->prioridad_ia
        ]);

        // Create system metric
        MetricaSistema::create([
            'nombre_metrica' => 'solicitud_creada',
            'valor' => 1,
            'tipo_metrica' => 'counter',
            'etiquetas' => [
                'especialidad' => $solicitudMedica->especialidad_solicitada,
                'prioridad' => $solicitudMedica->prioridad_ia,
                'institucion' => $solicitudMedica->institucion_remitente,
            ],
            'timestamp' => now(),
        ]);

        // Send urgent notification if high priority
        if ($solicitudMedica->prioridad_ia === 'Alta') {
            SendUrgentCaseNotificationJob::dispatch($solicitudMedica);
            
            Log::warning('Urgent medical case created', [
                'id' => $solicitudMedica->id,
                'score_urgencia' => $solicitudMedica->score_urgencia,
                'paciente_nombre' => $solicitudMedica->paciente_nombre
            ]);
        }

        // Create internal notification for relevant doctors
        $this->notifyRelevantDoctors($solicitudMedica);
    }

    /**
     * Handle the SolicitudMedica "updating" event.
     */
    public function updating(SolicitudMedica $solicitudMedica): void
    {
        $originalState = $solicitudMedica->getOriginal('estado');
        $newState = $solicitudMedica->estado;

        // Set evaluation timestamp when state changes to evaluated
        if ($originalState !== $newState && 
            in_array($newState, ['aceptada', 'rechazada', 'derivada']) &&
            !$solicitudMedica->fecha_evaluacion) {
            
            $solicitudMedica->fecha_evaluacion = now();
        }

        // Log state changes
        if ($originalState !== $newState) {
            Log::info('Medical request state changed', [
                'id' => $solicitudMedica->id,
                'from_state' => $originalState,
                'to_state' => $newState,
                'medico_evaluador_id' => $solicitudMedica->medico_evaluador_id
            ]);
        }
    }

    /**
     * Handle the SolicitudMedica "updated" event.
     */
    public function updated(SolicitudMedica $solicitudMedica): void
    {
        $originalState = $solicitudMedica->getOriginal('estado');
        $newState = $solicitudMedica->estado;

        // Handle state change events
        if ($originalState !== $newState) {
            $this->handleStateChange($solicitudMedica, $originalState, $newState);
        }

        // Handle priority changes
        $originalPriority = $solicitudMedica->getOriginal('prioridad_ia');
        $newPriority = $solicitudMedica->prioridad_ia;
        
        if ($originalPriority !== $newPriority) {
            $this->handlePriorityChange($solicitudMedica, $originalPriority, $newPriority);
        }

        // Handle doctor assignment
        $originalDoctor = $solicitudMedica->getOriginal('medico_evaluador_id');
        $newDoctor = $solicitudMedica->medico_evaluador_id;
        
        if ($originalDoctor !== $newDoctor) {
            $this->handleDoctorAssignment($solicitudMedica, $originalDoctor, $newDoctor);
        }
    }

    /**
     * Handle the SolicitudMedica "deleted" event.
     */
    public function deleted(SolicitudMedica $solicitudMedica): void
    {
        Log::warning('Medical request deleted', [
            'id' => $solicitudMedica->id,
            'paciente_nombre' => $solicitudMedica->paciente_nombre,
            'estado' => $solicitudMedica->estado
        ]);

        // Create system metric
        MetricaSistema::create([
            'nombre_metrica' => 'solicitud_eliminada',
            'valor' => 1,
            'tipo_metrica' => 'counter',
            'etiquetas' => [
                'especialidad' => $solicitudMedica->especialidad_solicitada,
                'estado' => $solicitudMedica->estado,
            ],
            'timestamp' => now(),
        ]);
    }

    /**
     * Handle state changes
     */
    private function handleStateChange(SolicitudMedica $solicitudMedica, $oldState, $newState): void
    {
        // Create metric for state change
        MetricaSistema::create([
            'nombre_metrica' => 'cambio_estado',
            'valor' => 1,
            'tipo_metrica' => 'counter',
            'etiquetas' => [
                'from_state' => $oldState,
                'to_state' => $newState,
                'especialidad' => $solicitudMedica->especialidad_solicitada,
                'medico_id' => $solicitudMedica->medico_evaluador_id,
            ],
            'timestamp' => now(),
        ]);

        // Calculate response time for completed evaluations
        if (in_array($newState, ['aceptada', 'rechazada', 'derivada']) && $solicitudMedica->fecha_evaluacion) {
            $responseTime = $solicitudMedica->fecha_recepcion_email->diffInHours($solicitudMedica->fecha_evaluacion);
            
            MetricaSistema::create([
                'nombre_metrica' => 'tiempo_respuesta',
                'valor' => $responseTime,
                'tipo_metrica' => 'gauge',
                'etiquetas' => [
                    'especialidad' => $solicitudMedica->especialidad_solicitada,
                    'prioridad' => $solicitudMedica->prioridad_ia,
                    'medico_id' => $solicitudMedica->medico_evaluador_id,
                ],
                'timestamp' => now(),
            ]);
        }

        // Send notifications based on state
        switch ($newState) {
            case 'aceptada':
                $this->notifyAcceptedCase($solicitudMedica);
                break;
            case 'rechazada':
                $this->notifyRejectedCase($solicitudMedica);
                break;
            case 'derivada':
                $this->notifyDerivedCase($solicitudMedica);
                break;
            case 'completada':
                $this->notifyCompletedCase($solicitudMedica);
                break;
        }
    }

    /**
     * Handle priority changes
     */
    private function handlePriorityChange(SolicitudMedica $solicitudMedica, $oldPriority, $newPriority): void
    {
        Log::info('Medical request priority changed', [
            'id' => $solicitudMedica->id,
            'from_priority' => $oldPriority,
            'to_priority' => $newPriority
        ]);

        // Send urgent notification if priority increased to high
        if ($oldPriority !== 'Alta' && $newPriority === 'Alta') {
            SendUrgentCaseNotificationJob::dispatch($solicitudMedica);
        }

        // Create metric
        MetricaSistema::create([
            'nombre_metrica' => 'cambio_prioridad',
            'valor' => 1,
            'tipo_metrica' => 'counter',
            'etiquetas' => [
                'from_priority' => $oldPriority,
                'to_priority' => $newPriority,
                'especialidad' => $solicitudMedica->especialidad_solicitada,
            ],
            'timestamp' => now(),
        ]);
    }

    /**
     * Handle doctor assignment
     */
    private function handleDoctorAssignment(SolicitudMedica $solicitudMedica, $oldDoctorId, $newDoctorId): void
    {
        if ($newDoctorId) {
            $notificationService = app(NotificationService::class);
            
            $notificationService->createInternalNotification(
                $newDoctorId,
                'case_assigned',
                'Caso Asignado',
                "Se le ha asignado el caso de {$solicitudMedica->paciente_nombre} - {$solicitudMedica->especialidad_solicitada}",
                [
                    'solicitud_id' => $solicitudMedica->id,
                    'url' => route('medico.evaluar-solicitud', $solicitudMedica->id)
                ]
            );

            Log::info('Medical case assigned to doctor', [
                'solicitud_id' => $solicitudMedica->id,
                'doctor_id' => $newDoctorId,
                'previous_doctor_id' => $oldDoctorId
            ]);
        }
    }

    /**
     * Notify relevant doctors about new case
     */
    private function notifyRelevantDoctors(SolicitudMedica $solicitudMedica): void
    {
        $notificationService = app(NotificationService::class);
        
        // Find doctors with matching specialty
        $relevantDoctors = \App\Models\User::where('role', 'medico')
            ->where('is_active', true)
            ->whereJsonContains('specialties', $solicitudMedica->especialidad_solicitada)
            ->get();

        foreach ($relevantDoctors as $doctor) {
            $notificationService->createInternalNotification(
                $doctor->id,
                'new_case',
                'Nuevo Caso Disponible',
                "Nuevo caso de {$solicitudMedica->especialidad_solicitada}: {$solicitudMedica->paciente_nombre}",
                [
                    'solicitud_id' => $solicitudMedica->id,
                    'prioridad' => $solicitudMedica->prioridad_ia,
                    'url' => route('medico.evaluar-solicitud', $solicitudMedica->id)
                ]
            );
        }
    }

    /**
     * Notify about accepted case
     */
    private function notifyAcceptedCase(SolicitudMedica $solicitudMedica): void
    {
        // Implementation for accepted case notifications
        Log::info('Case accepted notification', ['solicitud_id' => $solicitudMedica->id]);
    }

    /**
     * Notify about rejected case
     */
    private function notifyRejectedCase(SolicitudMedica $solicitudMedica): void
    {
        // Implementation for rejected case notifications
        Log::info('Case rejected notification', ['solicitud_id' => $solicitudMedica->id]);
    }

    /**
     * Notify about derived case
     */
    private function notifyDerivedCase(SolicitudMedica $solicitudMedica): void
    {
        // Implementation for derived case notifications
        Log::info('Case derived notification', ['solicitud_id' => $solicitudMedica->id]);
    }

    /**
     * Notify about completed case
     */
    private function notifyCompletedCase(SolicitudMedica $solicitudMedica): void
    {
        // Implementation for completed case notifications
        Log::info('Case completed notification', ['solicitud_id' => $solicitudMedica->id]);
    }
}
