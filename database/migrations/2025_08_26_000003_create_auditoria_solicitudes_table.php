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
        Schema::create('auditoria_solicitudes', function (Blueprint $table) {
            $table->id();

            // Relación con la solicitud médica
            $table->foreignId('solicitud_medica_id')->constrained('solicitudes_medicas')->onDelete('cascade');
            
            // Usuario que realizó la acción
            $table->foreignId('usuario_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('usuario_nombre')->nullable(); // Backup del nombre por si se elimina el usuario
            $table->string('usuario_rol')->nullable();
            
            // Información de la acción
            $table->enum('accion', [
                'solicitud_recibida',
                'procesamiento_ia_completado',
                'asignacion_medico',
                'inicio_evaluacion',
                'cambio_prioridad',
                'solicitud_aceptada',
                'solicitud_rechazada',
                'solicitud_info_adicional',
                'notificacion_enviada',
                'comentario_agregado',
                'archivo_adjuntado',
                'estado_modificado'
            ]);
            
            $table->text('descripcion'); // Descripción detallada de la acción
            
            // Valores antes y después del cambio
            $table->json('valores_anteriores')->nullable();
            $table->json('valores_nuevos')->nullable();
            
            // Contexto adicional
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('metadatos_adicionales')->nullable();
            
            // Información temporal
            $table->timestamp('fecha_accion');
            
            $table->timestamps();
            
            // Índices para consultas de auditoría
            $table->index(['solicitud_medica_id']);
            $table->index(['usuario_id']);
            $table->index(['accion']);
            $table->index(['fecha_accion']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auditoria_solicitudes');
    }
};
