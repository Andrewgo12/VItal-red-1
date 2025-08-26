<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('metricas_sistema', function (Blueprint $table) {
            $table->id();

            // Fecha y período de la métrica
            $table->date('fecha');
            $table->enum('periodo', ['diario', 'semanal', 'mensual'])->default('diario');
            
            // Métricas de solicitudes
            $table->integer('total_solicitudes_recibidas')->default(0);
            $table->integer('solicitudes_procesadas')->default(0);
            $table->integer('solicitudes_aceptadas')->default(0);
            $table->integer('solicitudes_rechazadas')->default(0);
            $table->integer('solicitudes_pendientes')->default(0);
            
            // Métricas por prioridad
            $table->integer('solicitudes_prioridad_alta')->default(0);
            $table->integer('solicitudes_prioridad_media')->default(0);
            $table->integer('solicitudes_prioridad_baja')->default(0);
            
            // Métricas por especialidad (JSON para flexibilidad)
            $table->json('solicitudes_por_especialidad')->nullable();
            
            // Métricas de tiempo
            $table->decimal('tiempo_promedio_procesamiento_ia', 8, 2)->nullable(); // en segundos
            $table->decimal('tiempo_promedio_evaluacion_medica', 8, 2)->nullable(); // en minutos
            $table->decimal('tiempo_promedio_respuesta_total', 8, 2)->nullable(); // en horas
            
            // Métricas de rendimiento del sistema
            $table->integer('emails_procesados')->default(0);
            $table->integer('errores_procesamiento')->default(0);
            $table->decimal('tasa_exito_ia', 5, 2)->nullable(); // porcentaje
            $table->decimal('precision_clasificacion_ia', 5, 2)->nullable(); // porcentaje
            
            // Métricas de usuarios
            $table->integer('medicos_activos')->default(0);
            $table->integer('evaluaciones_realizadas')->default(0);
            $table->json('actividad_por_usuario')->nullable();
            
            // Métricas de notificaciones
            $table->integer('notificaciones_enviadas')->default(0);
            $table->integer('notificaciones_fallidas')->default(0);
            $table->decimal('tasa_entrega_notificaciones', 5, 2)->nullable();
            
            // Métricas de instituciones remitentes
            $table->json('solicitudes_por_institucion')->nullable();
            
            // Metadatos adicionales
            $table->json('datos_adicionales')->nullable();
            
            $table->timestamps();
            
            // Índices
            $table->unique(['fecha', 'periodo']);
            $table->index(['fecha']);
            $table->index(['periodo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metricas_sistema');
    }
};
