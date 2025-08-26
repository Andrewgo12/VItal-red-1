<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SolicitudMedica extends Model
{
    protected $table = 'solicitudes_medicas';

    protected $fillable = [
        // Identificación del email
        'email_unique_id',
        'email_message_id',
        
        // Información del remitente
        'institucion_remitente',
        'medico_remitente',
        'email_remitente',
        'telefono_remitente',
        
        // Información del paciente
        'paciente_nombre',
        'paciente_apellidos',
        'paciente_identificacion',
        'paciente_tipo_id',
        'paciente_edad',
        'paciente_sexo',
        'paciente_telefono',
        
        // Información clínica
        'diagnostico_principal',
        'diagnosticos_secundarios',
        'motivo_consulta',
        'enfermedad_actual',
        'antecedentes_medicos',
        'medicamentos_actuales',
        
        // Signos vitales
        'frecuencia_cardiaca',
        'frecuencia_respiratoria',
        'temperatura',
        'tension_sistolica',
        'tension_diastolica',
        'saturacion_oxigeno',
        'escala_glasgow',
        
        // Información de la solicitud
        'especialidad_solicitada',
        'tipo_solicitud',
        'motivo_remision',
        'requerimiento_oxigeno',
        'tipo_servicio',
        'observaciones_adicionales',
        
        // Clasificación automática por IA
        'prioridad_ia',
        'score_urgencia',
        'criterios_priorizacion',
        
        // Estado de la solicitud
        'estado',
        
        // Evaluación médica
        'medico_evaluador_id',
        'decision_medica',
        'observaciones_medico',
        'prioridad_medica',
        'fecha_evaluacion',
        
        // Notificaciones y seguimiento
        'notificacion_enviada',
        'fecha_notificacion',
        'destinatario_notificacion',
        
        // Archivos adjuntos
        'archivos_adjuntos',
        'texto_extraido',
        
        // Metadatos del procesamiento
        'fecha_recepcion_email',
        'fecha_procesamiento_ia',
        'metadatos_procesamiento',
    ];

    protected $casts = [
        'fecha_recepcion_email' => 'datetime',
        'fecha_procesamiento_ia' => 'datetime',
        'fecha_evaluacion' => 'datetime',
        'fecha_notificacion' => 'datetime',
        'criterios_priorizacion' => 'array',
        'archivos_adjuntos' => 'array',
        'metadatos_procesamiento' => 'array',
        'score_urgencia' => 'decimal:2',
        'temperatura' => 'decimal:1',
        'notificacion_enviada' => 'boolean',
        'paciente_edad' => 'integer',
        'frecuencia_cardiaca' => 'integer',
        'frecuencia_respiratoria' => 'integer',
        'tension_sistolica' => 'integer',
        'tension_diastolica' => 'integer',
        'saturacion_oxigeno' => 'integer',
    ];

    /**
     * Relación con el médico evaluador
     */
    public function medicoEvaluador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'medico_evaluador_id');
    }

    /**
     * Relación con las notificaciones
     */
    public function notificaciones(): HasMany
    {
        return $this->hasMany(NotificacionInterna::class);
    }

    /**
     * Relación con la auditoría
     */
    public function auditoria(): HasMany
    {
        return $this->hasMany(AuditoriaSolicitud::class);
    }

    /**
     * Scope para filtrar por estado
     */
    public function scopeByEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    /**
     * Scope para filtrar por prioridad
     */
    public function scopeByPrioridad($query, $prioridad)
    {
        return $query->where('prioridad_ia', $prioridad);
    }

    /**
     * Scope para filtrar por especialidad
     */
    public function scopeByEspecialidad($query, $especialidad)
    {
        return $query->where('especialidad_solicitada', $especialidad);
    }

    /**
     * Scope para solicitudes pendientes de evaluación
     */
    public function scopePendientesEvaluacion($query)
    {
        return $query->whereIn('estado', ['recibida', 'en_revision']);
    }

    /**
     * Scope para solicitudes urgentes
     */
    public function scopeUrgentes($query)
    {
        return $query->where('prioridad_ia', 'Alta')
                    ->orWhere('tipo_solicitud', 'urgencia');
    }

    /**
     * Accessor para obtener el nombre completo del paciente
     */
    public function getNombreCompletoPacienteAttribute()
    {
        return trim($this->paciente_nombre . ' ' . $this->paciente_apellidos);
    }

    /**
     * Accessor para obtener el tiempo transcurrido desde la recepción
     */
    public function getTiempoTranscurridoAttribute()
    {
        return $this->fecha_recepcion_email->diffForHumans();
    }

    /**
     * Determinar si la solicitud es urgente
     */
    public function esUrgente(): bool
    {
        return $this->prioridad_ia === 'Alta' || 
               $this->tipo_solicitud === 'urgencia' ||
               $this->score_urgencia >= 80;
    }

    /**
     * Determinar si requiere evaluación inmediata
     */
    public function requiereEvaluacionInmediata(): bool
    {
        return $this->esUrgente() && in_array($this->estado, ['recibida', 'en_revision']);
    }
}
