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
        Schema::create('solicitudes_medicas', function (Blueprint $table) {
            $table->id();

            // Identificación única del email procesado
            $table->string('email_unique_id')->unique();
            $table->string('email_message_id')->nullable();
            
            // Información del remitente
            $table->string('institucion_remitente');
            $table->string('medico_remitente')->nullable();
            $table->string('email_remitente');
            $table->string('telefono_remitente')->nullable();
            
            // Información del paciente
            $table->string('paciente_nombre');
            $table->string('paciente_apellidos')->nullable();
            $table->string('paciente_identificacion')->nullable();
            $table->enum('paciente_tipo_id', ['CC', 'TI', 'CE', 'PA', 'RC', 'MS'])->nullable();
            $table->integer('paciente_edad')->nullable();
            $table->enum('paciente_sexo', ['masculino', 'femenino', 'otro'])->nullable();
            $table->string('paciente_telefono')->nullable();
            
            // Información clínica
            $table->text('diagnostico_principal');
            $table->text('diagnosticos_secundarios')->nullable();
            $table->text('motivo_consulta');
            $table->text('enfermedad_actual')->nullable();
            $table->text('antecedentes_medicos')->nullable();
            $table->text('medicamentos_actuales')->nullable();
            
            // Signos vitales (si están disponibles)
            $table->integer('frecuencia_cardiaca')->nullable();
            $table->integer('frecuencia_respiratoria')->nullable();
            $table->decimal('temperatura', 4, 1)->nullable();
            $table->integer('tension_sistolica')->nullable();
            $table->integer('tension_diastolica')->nullable();
            $table->integer('saturacion_oxigeno')->nullable();
            $table->string('escala_glasgow')->nullable();
            
            // Información de la solicitud
            $table->string('especialidad_solicitada');
            $table->enum('tipo_solicitud', ['consulta', 'hospitalizacion', 'cirugia', 'urgencia', 'otro']);
            $table->text('motivo_remision');
            $table->enum('requerimiento_oxigeno', ['SI', 'NO'])->default('NO');
            $table->string('tipo_servicio')->nullable();
            $table->text('observaciones_adicionales')->nullable();
            
            // Clasificación automática por IA
            $table->enum('prioridad_ia', ['Alta', 'Media', 'Baja'])->nullable();
            $table->decimal('score_urgencia', 5, 2)->nullable(); // 0.00 a 100.00
            $table->json('criterios_priorizacion')->nullable(); // JSON con criterios detectados
            
            // Estado de la solicitud
            $table->enum('estado', [
                'recibida',      // Recién procesada por IA
                'en_revision',   // Siendo revisada por médico
                'aceptada',      // Aprobada para traslado
                'rechazada',     // Rechazada
                'pendiente_info', // Requiere información adicional
                'completada'     // Proceso finalizado
            ])->default('recibida');
            
            // Evaluación médica
            $table->foreignId('medico_evaluador_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('decision_medica', ['aceptar', 'rechazar', 'solicitar_info'])->nullable();
            $table->text('observaciones_medico')->nullable();
            $table->enum('prioridad_medica', ['Alta', 'Media', 'Baja'])->nullable();
            $table->timestamp('fecha_evaluacion')->nullable();
            
            // Notificaciones y seguimiento
            $table->boolean('notificacion_enviada')->default(false);
            $table->timestamp('fecha_notificacion')->nullable();
            $table->string('destinatario_notificacion')->nullable();
            
            // Archivos adjuntos
            $table->json('archivos_adjuntos')->nullable(); // JSON con rutas de archivos
            $table->text('texto_extraido')->nullable(); // Texto completo extraído por IA
            
            // Metadatos del procesamiento
            $table->timestamp('fecha_recepcion_email');
            $table->timestamp('fecha_procesamiento_ia');
            $table->json('metadatos_procesamiento')->nullable(); // Información técnica del procesamiento
            
            // Auditoría
            $table->timestamps();
            
            // Índices para optimizar consultas
            $table->index(['estado']);
            $table->index(['prioridad_ia']);
            $table->index(['especialidad_solicitada']);
            $table->index(['fecha_recepcion_email']);
            $table->index(['medico_evaluador_id']);
            $table->index(['institucion_remitente']);
            $table->index(['paciente_identificacion']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitudes_medicas');
    }
};
