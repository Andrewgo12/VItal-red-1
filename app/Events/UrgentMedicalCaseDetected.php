<?php

namespace App\Events;

use App\Models\SolicitudMedica;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UrgentMedicalCaseDetected implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public SolicitudMedica $solicitud;

    /**
     * Create a new event instance.
     */
    public function __construct(SolicitudMedica $solicitud)
    {
        $this->solicitud = $solicitud;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('medical-alerts'),
            new PrivateChannel('medical-staff'),
            new PrivateChannel('admin-notifications')
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'type' => 'urgent_medical_case',
            'solicitud_id' => $this->solicitud->id,
            'patient_name' => $this->solicitud->paciente_nombre,
            'institution' => $this->solicitud->institucion_remitente,
            'specialty' => $this->solicitud->especialidad_solicitada,
            'priority' => $this->solicitud->prioridad_ia,
            'urgency_score' => $this->solicitud->score_urgencia,
            'diagnosis' => $this->solicitud->diagnostico_principal,
            'received_at' => $this->solicitud->fecha_recepcion_email->toISOString(),
            'message' => "Caso médico urgente detectado: {$this->solicitud->paciente_nombre} - {$this->solicitud->especialidad_solicitada}",
            'timestamp' => now()->toISOString()
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'urgent.medical.case';
    }
}
