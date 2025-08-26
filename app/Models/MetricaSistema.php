<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class MetricaSistema extends Model
{
    protected $table = 'metricas_sistema';

    protected $fillable = [
        'fecha',
        'periodo',
        'total_solicitudes_recibidas',
        'solicitudes_procesadas',
        'solicitudes_aceptadas',
        'solicitudes_rechazadas',
        'solicitudes_pendientes',
        'solicitudes_prioridad_alta',
        'solicitudes_prioridad_media',
        'solicitudes_prioridad_baja',
        'solicitudes_por_especialidad',
        'tiempo_promedio_procesamiento_ia',
        'tiempo_promedio_evaluacion_medica',
        'tiempo_promedio_respuesta_total',
        'emails_procesados',
        'errores_procesamiento',
        'tasa_exito_ia',
        'precision_clasificacion_ia',
        'medicos_activos',
        'evaluaciones_realizadas',
        'actividad_por_usuario',
        'notificaciones_enviadas',
        'notificaciones_fallidas',
        'tasa_entrega_notificaciones',
        'solicitudes_por_institucion',
        'datos_adicionales',
    ];

    protected $casts = [
        'fecha' => 'date',
        'solicitudes_por_especialidad' => 'array',
        'actividad_por_usuario' => 'array',
        'solicitudes_por_institucion' => 'array',
        'datos_adicionales' => 'array',
        'tiempo_promedio_procesamiento_ia' => 'decimal:2',
        'tiempo_promedio_evaluacion_medica' => 'decimal:2',
        'tiempo_promedio_respuesta_total' => 'decimal:2',
        'tasa_exito_ia' => 'decimal:2',
        'precision_clasificacion_ia' => 'decimal:2',
        'tasa_entrega_notificaciones' => 'decimal:2',
    ];

    /**
     * Scope para métricas por período
     */
    public function scopeByPeriodo($query, $periodo)
    {
        return $query->where('periodo', $periodo);
    }

    /**
     * Scope para métricas entre fechas
     */
    public function scopeEntreFechas($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('fecha', [$fechaInicio, $fechaFin]);
    }

    /**
     * Generar métricas diarias para una fecha específica
     */
    public static function generarMetricasDiarias(Carbon $fecha)
    {
        $fechaInicio = $fecha->copy()->startOfDay();
        $fechaFin = $fecha->copy()->endOfDay();

        // Obtener datos de solicitudes
        $solicitudes = SolicitudMedica::whereBetween('fecha_recepcion_email', [$fechaInicio, $fechaFin]);
        
        $totalSolicitudes = $solicitudes->count();
        $solicitudesProcesadas = $solicitudes->whereNotNull('fecha_procesamiento_ia')->count();
        $solicitudesAceptadas = $solicitudes->where('decision_medica', 'aceptar')->count();
        $solicitudesRechazadas = $solicitudes->where('decision_medica', 'rechazar')->count();
        $solicitudesPendientes = $solicitudes->whereIn('estado', ['recibida', 'en_revision'])->count();

        // Métricas por prioridad
        $prioridadAlta = $solicitudes->where('prioridad_ia', 'Alta')->count();
        $prioridadMedia = $solicitudes->where('prioridad_ia', 'Media')->count();
        $prioridadBaja = $solicitudes->where('prioridad_ia', 'Baja')->count();

        // Métricas por especialidad
        $especialidades = $solicitudes->get()
            ->groupBy('especialidad_solicitada')
            ->map->count()
            ->toArray();

        // Métricas de tiempo
        $tiemposIA = $solicitudes->whereNotNull('fecha_procesamiento_ia')
            ->get()
            ->map(function ($solicitud) {
                return $solicitud->fecha_procesamiento_ia->diffInSeconds($solicitud->fecha_recepcion_email);
            });

        $tiemposEvaluacion = $solicitudes->whereNotNull('fecha_evaluacion')
            ->get()
            ->map(function ($solicitud) {
                return $solicitud->fecha_evaluacion->diffInMinutes($solicitud->fecha_procesamiento_ia ?? $solicitud->fecha_recepcion_email);
            });

        // Métricas de notificaciones
        $notificaciones = NotificacionInterna::whereBetween('created_at', [$fechaInicio, $fechaFin]);
        $notificacionesEnviadas = $notificaciones->where('estado', 'enviada')->count();
        $notificacionesFallidas = $notificaciones->where('estado', 'fallida')->count();

        // Crear o actualizar métrica
        return static::updateOrCreate(
            ['fecha' => $fecha->toDateString(), 'periodo' => 'diario'],
            [
                'total_solicitudes_recibidas' => $totalSolicitudes,
                'solicitudes_procesadas' => $solicitudesProcesadas,
                'solicitudes_aceptadas' => $solicitudesAceptadas,
                'solicitudes_rechazadas' => $solicitudesRechazadas,
                'solicitudes_pendientes' => $solicitudesPendientes,
                'solicitudes_prioridad_alta' => $prioridadAlta,
                'solicitudes_prioridad_media' => $prioridadMedia,
                'solicitudes_prioridad_baja' => $prioridadBaja,
                'solicitudes_por_especialidad' => $especialidades,
                'tiempo_promedio_procesamiento_ia' => $tiemposIA->avg(),
                'tiempo_promedio_evaluacion_medica' => $tiemposEvaluacion->avg(),
                'notificaciones_enviadas' => $notificacionesEnviadas,
                'notificaciones_fallidas' => $notificacionesFallidas,
                'tasa_entrega_notificaciones' => $notificacionesEnviadas > 0 ? 
                    ($notificacionesEnviadas / ($notificacionesEnviadas + $notificacionesFallidas)) * 100 : 0,
            ]
        );
    }

    /**
     * Obtener resumen de métricas para dashboard
     */
    public static function obtenerResumenDashboard()
    {
        $hoy = now()->toDateString();
        $ayer = now()->subDay()->toDateString();
        
        $metricasHoy = static::where('fecha', $hoy)->where('periodo', 'diario')->first();
        $metricasAyer = static::where('fecha', $ayer)->where('periodo', 'diario')->first();

        return [
            'hoy' => $metricasHoy,
            'ayer' => $metricasAyer,
            'cambio_solicitudes' => $metricasHoy && $metricasAyer ? 
                $metricasHoy->total_solicitudes_recibidas - $metricasAyer->total_solicitudes_recibidas : 0,
            'cambio_porcentual' => $metricasHoy && $metricasAyer && $metricasAyer->total_solicitudes_recibidas > 0 ?
                (($metricasHoy->total_solicitudes_recibidas - $metricasAyer->total_solicitudes_recibidas) / $metricasAyer->total_solicitudes_recibidas) * 100 : 0,
        ];
    }
}
