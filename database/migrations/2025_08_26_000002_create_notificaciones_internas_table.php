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
        Schema::create('notificaciones_internas', function (Blueprint $table) {
            $table->id();

            // Relación con la solicitud médica
            $table->foreignId('solicitud_medica_id')->constrained('solicitudes_medicas')->onDelete('cascade');
            
            // Información del destinatario
            $table->foreignId('usuario_destinatario_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('email_destinatario')->nullable();
            $table->string('departamento_destinatario')->nullable(); // ej: "Urgencias", "Admisiones"
            
            // Tipo y contenido de la notificación
            $table->enum('tipo_notificacion', [
                'solicitud_aceptada',
                'solicitud_rechazada', 
                'solicitud_pendiente_info',
                'caso_urgente',
                'recordatorio_evaluacion',
                'traslado_autorizado'
            ]);
            
            $table->string('titulo');
            $table->text('mensaje');
            $table->json('datos_adicionales')->nullable(); // Información específica de la notificación
            
            // Estado de la notificación
            $table->enum('estado', ['pendiente', 'enviada', 'leida', 'fallida'])->default('pendiente');
            $table->timestamp('fecha_envio')->nullable();
            $table->timestamp('fecha_lectura')->nullable();
            
            // Canales de notificación
            $table->boolean('notificar_email')->default(true);
            $table->boolean('notificar_dashboard')->default(true);
            $table->boolean('notificar_sms')->default(false);
            
            // Prioridad de la notificación
            $table->enum('prioridad', ['baja', 'media', 'alta', 'critica'])->default('media');
            
            // Intentos de envío
            $table->integer('intentos_envio')->default(0);
            $table->timestamp('proximo_intento')->nullable();
            $table->text('error_ultimo_intento')->nullable();
            
            $table->timestamps();
            
            // Índices
            $table->index(['usuario_destinatario_id']);
            $table->index(['estado']);
            $table->index(['tipo_notificacion']);
            $table->index(['prioridad']);
            $table->index(['fecha_envio']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notificaciones_internas');
    }
};
