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

class MedicalRequestEvaluated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public SolicitudMedica $solicitud;
    public string $decision;

    /**
     * Create a new event instance.
     */
    public function __construct(SolicitudMedica $solicitud, string $decision)
    {
        $this->solicitud = $solicitud;
        $this->decision = $decision;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('medical-evaluations'),
            new PrivateChannel('admin-notifications'),
            new PrivateChannel('admissions-department')
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'type' => 'medical_request_evaluated',
            'solicitud_id' => $this->solicitud->id,
            'patient_name' => $this->solicitud->paciente_nombre,
            'institution' => $this->solicitud->institucion_remitente,
            'specialty' => $this->solicitud->especialidad_solicitada,
            'decision' => $this->decision,
            'evaluator' => $this->solicitud->medicoEvaluador?->name,
            'evaluation_date' => $this->solicitud->fecha_evaluacion?->toISOString(),
            'observations' => $this->solicitud->observaciones_medico,
            'message' => $this->getDecisionMessage(),
            'timestamp' => now()->toISOString()
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'medical.request.evaluated';
    }

    /**
     * Get decision message
     */
    private function getDecisionMessage(): string
    {
        $decisionText = match($this->decision) {
            'aceptar' => 'ACEPTADA',
            'rechazar' => 'RECHAZADA',
            'solicitar_info' => 'REQUIERE INFORMACIÓN',
            default => 'EVALUADA'
        };

        return "Solicitud {$decisionText}: {$this->solicitud->paciente_nombre} - {$this->solicitud->especialidad_solicitada}";
    }
}
