<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditoriaSolicitud extends Model
{
    protected $table = 'auditoria_solicitudes';

    protected $fillable = [
        'solicitud_medica_id',
        'usuario_id',
        'usuario_nombre',
        'usuario_rol',
        'accion',
        'descripcion',
        'valores_anteriores',
        'valores_nuevos',
        'ip_address',
        'user_agent',
        'metadatos_adicionales',
        'fecha_accion',
    ];

    protected $casts = [
        'fecha_accion' => 'datetime',
        'valores_anteriores' => 'array',
        'valores_nuevos' => 'array',
        'metadatos_adicionales' => 'array',
    ];

    /**
     * Relación con la solicitud médica
     */
    public function solicitudMedica(): BelongsTo
    {
        return $this->belongsTo(SolicitudMedica::class);
    }

    /**
     * Relación con el usuario
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope para filtrar por acción
     */
    public function scopeByAccion($query, $accion)
    {
        return $query->where('accion', $accion);
    }

    /**
     * Scope para filtrar por usuario
     */
    public function scopeByUsuario($query, $usuarioId)
    {
        return $query->where('usuario_id', $usuarioId);
    }

    /**
     * Scope para filtrar por rango de fechas
     */
    public function scopeEntreFechas($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('fecha_accion', [$fechaInicio, $fechaFin]);
    }

    /**
     * Crear registro de auditoría
     */
    public static function registrarAccion(
        int $solicitudMedicaId,
        string $accion,
        string $descripcion,
        array $valoresAnteriores = null,
        array $valoresNuevos = null,
        int $usuarioId = null
    ) {
        $usuario = auth()->user();
        
        return static::create([
            'solicitud_medica_id' => $solicitudMedicaId,
            'usuario_id' => $usuarioId ?? $usuario?->id,
            'usuario_nombre' => $usuario?->name ?? 'Sistema',
            'usuario_rol' => $usuario?->role ?? 'sistema',
            'accion' => $accion,
            'descripcion' => $descripcion,
            'valores_anteriores' => $valoresAnteriores,
            'valores_nuevos' => $valoresNuevos,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'fecha_accion' => now(),
        ]);
    }
}
