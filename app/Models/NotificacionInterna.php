<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificacionInterna extends Model
{
    protected $table = 'notificaciones_internas';

    protected $fillable = [
        'solicitud_medica_id',
        'usuario_destinatario_id',
        'email_destinatario',
        'departamento_destinatario',
        'tipo_notificacion',
        'titulo',
        'mensaje',
        'datos_adicionales',
        'estado',
        'fecha_envio',
        'fecha_lectura',
        'notificar_email',
        'notificar_dashboard',
        'notificar_sms',
        'prioridad',
        'intentos_envio',
        'proximo_intento',
        'error_ultimo_intento',
    ];

    protected $casts = [
        'fecha_envio' => 'datetime',
        'fecha_lectura' => 'datetime',
        'proximo_intento' => 'datetime',
        'datos_adicionales' => 'array',
        'notificar_email' => 'boolean',
        'notificar_dashboard' => 'boolean',
        'notificar_sms' => 'boolean',
        'intentos_envio' => 'integer',
    ];

    /**
     * Relación con la solicitud médica
     */
    public function solicitudMedica(): BelongsTo
    {
        return $this->belongsTo(SolicitudMedica::class);
    }

    /**
     * Relación con el usuario destinatario
     */
    public function usuarioDestinatario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_destinatario_id');
    }

    /**
     * Scope para notificaciones pendientes
     */
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    /**
     * Scope para notificaciones por prioridad
     */
    public function scopeByPrioridad($query, $prioridad)
    {
        return $query->where('prioridad', $prioridad);
    }

    /**
     * Scope para notificaciones no leídas
     */
    public function scopeNoLeidas($query)
    {
        return $query->whereIn('estado', ['enviada'])->whereNull('fecha_lectura');
    }

    /**
     * Marcar como leída
     */
    public function marcarComoLeida()
    {
        $this->update([
            'estado' => 'leida',
            'fecha_lectura' => now()
        ]);
    }

    /**
     * Incrementar intentos de envío
     */
    public function incrementarIntentos($error = null)
    {
        $this->increment('intentos_envio');
        
        if ($error) {
            $this->update(['error_ultimo_intento' => $error]);
        }
        
        // Programar próximo intento con backoff exponencial
        $delay = min(pow(2, $this->intentos_envio) * 60, 3600); // máximo 1 hora
        $this->update(['proximo_intento' => now()->addSeconds($delay)]);
    }

    /**
     * Determinar si debe reintentarse el envío
     */
    public function debeReintentar(): bool
    {
        return $this->intentos_envio < 5 && 
               $this->estado === 'fallida' &&
               ($this->proximo_intento === null || $this->proximo_intento <= now());
    }
}
