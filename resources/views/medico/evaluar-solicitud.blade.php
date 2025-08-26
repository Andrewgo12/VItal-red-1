@extends('layouts.app')

@section('title', 'Evaluación Médica - ' . $solicitud->paciente_nombre)

@section('content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-stethoscope mr-2"></i>Evaluación Médica
                    </h1>
                    <p class="text-muted">Caso ID: {{ $solicitud->id }} - {{ $solicitud->paciente_nombre }}</p>
                </div>
                <div>
                    <a href="{{ route('medico.bandeja-casos') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left mr-1"></i>Volver a Bandeja
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Priority Alert -->
    @if($solicitud->prioridad_ia === 'Alta')
    <div class="alert alert-danger" role="alert">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        <strong>Caso de Alta Prioridad</strong> - Requiere evaluación urgente
        @if($solicitud->score_urgencia)
            <span class="badge badge-light ml-2">Score: {{ $solicitud->score_urgencia }}/100</span>
        @endif
    </div>
    @endif

    <div class="row">
        <!-- Patient Information Panel -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-user mr-2"></i>Información del Paciente
                    </h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td><strong>Nombre:</strong></td>
                            <td>{{ $solicitud->paciente_nombre }} {{ $solicitud->paciente_apellidos }}</td>
                        </tr>
                        @if($solicitud->paciente_edad)
                        <tr>
                            <td><strong>Edad:</strong></td>
                            <td>{{ $solicitud->paciente_edad }} años</td>
                        </tr>
                        @endif
                        @if($solicitud->paciente_sexo)
                        <tr>
                            <td><strong>Sexo:</strong></td>
                            <td>{{ $solicitud->paciente_sexo }}</td>
                        </tr>
                        @endif
                        @if($solicitud->paciente_identificacion)
                        <tr>
                            <td><strong>Identificación:</strong></td>
                            <td>{{ $solicitud->paciente_identificacion }}</td>
                        </tr>
                        @endif
                        @if($solicitud->paciente_telefono)
                        <tr>
                            <td><strong>Teléfono:</strong></td>
                            <td>{{ $solicitud->paciente_telefono }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>

            <!-- Referral Information -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-hospital mr-2"></i>Información de Referencia
                    </h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td><strong>Institución:</strong></td>
                            <td>{{ $solicitud->institucion_remitente }}</td>
                        </tr>
                        @if($solicitud->medico_remitente)
                        <tr>
                            <td><strong>Médico:</strong></td>
                            <td>Dr. {{ $solicitud->medico_remitente }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td><strong>Especialidad:</strong></td>
                            <td><span class="badge badge-info">{{ $solicitud->especialidad_solicitada }}</span></td>
                        </tr>
                        <tr>
                            <td><strong>Tipo:</strong></td>
                            <td>{{ ucfirst($solicitud->tipo_solicitud) }}</td>
                        </tr>
                        <tr>
                            <td><strong>Recibido:</strong></td>
                            <td>{{ $solicitud->fecha_recepcion_email->format('d/m/Y H:i') }}</td>
                        </tr>
                        @if($solicitud->requiere_oxigeno === 'SI')
                        <tr>
                            <td><strong>Oxígeno:</strong></td>
                            <td><span class="badge badge-warning">Requerido</span></td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>

            <!-- Vital Signs -->
            @if($solicitud->hasVitalSigns())
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-heartbeat mr-2"></i>Signos Vitales
                    </h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless">
                        @if($solicitud->frecuencia_cardiaca)
                        <tr>
                            <td><strong>FC:</strong></td>
                            <td>{{ $solicitud->frecuencia_cardiaca }} lpm</td>
                        </tr>
                        @endif
                        @if($solicitud->frecuencia_respiratoria)
                        <tr>
                            <td><strong>FR:</strong></td>
                            <td>{{ $solicitud->frecuencia_respiratoria }} rpm</td>
                        </tr>
                        @endif
                        @if($solicitud->tension_sistolica && $solicitud->tension_diastolica)
                        <tr>
                            <td><strong>TA:</strong></td>
                            <td>{{ $solicitud->tension_sistolica }}/{{ $solicitud->tension_diastolica }} mmHg</td>
                        </tr>
                        @endif
                        @if($solicitud->temperatura)
                        <tr>
                            <td><strong>Temp:</strong></td>
                            <td>{{ $solicitud->temperatura }}°C</td>
                        </tr>
                        @endif
                        @if($solicitud->saturacion_oxigeno)
                        <tr>
                            <td><strong>SpO2:</strong></td>
                            <td>{{ $solicitud->saturacion_oxigeno }}%</td>
                        </tr>
                        @endif
                        @if($solicitud->escala_glasgow)
                        <tr>
                            <td><strong>Glasgow:</strong></td>
                            <td>{{ $solicitud->escala_glasgow }}/15</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>
            @endif
        </div>

        <!-- Main Content Panel -->
        <div class="col-lg-8">
            <!-- Clinical Information -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-file-medical mr-2"></i>Información Clínica
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-12 mb-3">
                            <h6 class="font-weight-bold text-dark">Diagnóstico Principal</h6>
                            <div class="p-3 bg-light rounded">
                                {{ $solicitud->diagnostico_principal }}
                            </div>
                        </div>
                        
                        @if($solicitud->motivo_consulta)
                        <div class="col-12 mb-3">
                            <h6 class="font-weight-bold text-dark">Motivo de Consulta</h6>
                            <div class="p-3 bg-light rounded">
                                {{ $solicitud->motivo_consulta }}
                            </div>
                        </div>
                        @endif

                        @if($solicitud->enfermedad_actual)
                        <div class="col-12 mb-3">
                            <h6 class="font-weight-bold text-dark">Enfermedad Actual</h6>
                            <div class="p-3 bg-light rounded">
                                {{ $solicitud->enfermedad_actual }}
                            </div>
                        </div>
                        @endif

                        @if($solicitud->antecedentes_medicos)
                        <div class="col-12 mb-3">
                            <h6 class="font-weight-bold text-dark">Antecedentes Médicos</h6>
                            <div class="p-3 bg-light rounded">
                                {{ $solicitud->antecedentes_medicos }}
                            </div>
                        </div>
                        @endif

                        @if($solicitud->medicamentos_actuales)
                        <div class="col-12 mb-3">
                            <h6 class="font-weight-bold text-dark">Medicamentos Actuales</h6>
                            <div class="p-3 bg-light rounded">
                                {{ $solicitud->medicamentos_actuales }}
                            </div>
                        </div>
                        @endif

                        @if($solicitud->motivo_remision)
                        <div class="col-12 mb-3">
                            <h6 class="font-weight-bold text-dark">Motivo de Remisión</h6>
                            <div class="p-3 bg-light rounded">
                                {{ $solicitud->motivo_remision }}
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- AI Analysis Results -->
            @if($solicitud->criterios_priorizacion)
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-robot mr-2"></i>Análisis de IA
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="font-weight-bold">Prioridad Detectada</h6>
                            <span class="badge badge-{{ $solicitud->prioridad_ia === 'Alta' ? 'danger' : 
                                ($solicitud->prioridad_ia === 'Media' ? 'warning' : 'info') }} p-2">
                                {{ $solicitud->prioridad_ia }}
                            </span>
                            @if($solicitud->score_urgencia)
                                <div class="mt-2">
                                    <small class="text-muted">Score de Urgencia: {{ $solicitud->score_urgencia }}/100</small>
                                </div>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <h6 class="font-weight-bold">Criterios de Priorización</h6>
                            @if(is_array($solicitud->criterios_priorizacion))
                                <ul class="list-unstyled">
                                    @foreach($solicitud->criterios_priorizacion as $criterio)
                                        <li><i class="fas fa-check-circle text-success mr-1"></i>{{ $criterio }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="text-muted">{{ $solicitud->criterios_priorizacion }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Medical Evaluation Form -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-clipboard-check mr-2"></i>Evaluación Médica
                    </h6>
                </div>
                <div class="card-body">
                    <form id="evaluation-form" action="{{ route('medico.guardar-evaluacion', $solicitud->id) }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="decision_medica" class="form-label font-weight-bold">
                                    <i class="fas fa-gavel mr-1"></i>Decisión Médica *
                                </label>
                                <select class="form-control @error('decision_medica') is-invalid @enderror" 
                                        id="decision_medica" name="decision_medica" required>
                                    <option value="">Seleccione una decisión...</option>
                                    <option value="aceptar" {{ old('decision_medica', $solicitud->decision_medica) === 'aceptar' ? 'selected' : '' }}>
                                        ✅ Aceptar Traslado
                                    </option>
                                    <option value="rechazar" {{ old('decision_medica', $solicitud->decision_medica) === 'rechazar' ? 'selected' : '' }}>
                                        ❌ Rechazar Traslado
                                    </option>
                                    <option value="solicitar_info" {{ old('decision_medica', $solicitud->decision_medica) === 'solicitar_info' ? 'selected' : '' }}>
                                        ℹ️ Solicitar Información Adicional
                                    </option>
                                </select>
                                @error('decision_medica')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="prioridad_medica" class="form-label font-weight-bold">
                                    <i class="fas fa-exclamation-circle mr-1"></i>Prioridad Médica
                                </label>
                                <select class="form-control" id="prioridad_medica" name="prioridad_medica">
                                    <option value="">Mantener prioridad IA ({{ $solicitud->prioridad_ia }})</option>
                                    <option value="Alta" {{ old('prioridad_medica') === 'Alta' ? 'selected' : '' }}>Alta</option>
                                    <option value="Media" {{ old('prioridad_medica') === 'Media' ? 'selected' : '' }}>Media</option>
                                    <option value="Baja" {{ old('prioridad_medica') === 'Baja' ? 'selected' : '' }}>Baja</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="observaciones_medico" class="form-label font-weight-bold">
                                <i class="fas fa-comment-medical mr-1"></i>Observaciones y Justificación *
                            </label>
                            <textarea class="form-control @error('observaciones_medico') is-invalid @enderror" 
                                      id="observaciones_medico" name="observaciones_medico" rows="4" 
                                      placeholder="Ingrese sus observaciones médicas, justificación de la decisión y recomendaciones..." required>{{ old('observaciones_medico', $solicitud->observaciones_medico) }}</textarea>
                            @error('observaciones_medico')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Conditional Fields -->
                        <div id="acceptance-fields" style="display: none;">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="fecha_programada" class="form-label font-weight-bold">
                                        <i class="fas fa-calendar mr-1"></i>Fecha Programada de Ingreso
                                    </label>
                                    <input type="datetime-local" class="form-control" id="fecha_programada" 
                                           name="fecha_programada" value="{{ old('fecha_programada') }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="servicio_destino" class="form-label font-weight-bold">
                                        <i class="fas fa-bed mr-1"></i>Servicio de Destino
                                    </label>
                                    <select class="form-control" id="servicio_destino" name="servicio_destino">
                                        <option value="">Seleccione servicio...</option>
                                        <option value="urgencias">Urgencias</option>
                                        <option value="hospitalizacion">Hospitalización</option>
                                        <option value="uci">UCI</option>
                                        <option value="cirugia">Cirugía</option>
                                        <option value="consulta_externa">Consulta Externa</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div id="rejection-fields" style="display: none;">
                            <div class="mb-3">
                                <label for="motivo_rechazo" class="form-label font-weight-bold">
                                    <i class="fas fa-times-circle mr-1"></i>Motivo del Rechazo
                                </label>
                                <select class="form-control" id="motivo_rechazo" name="motivo_rechazo">
                                    <option value="">Seleccione motivo...</option>
                                    <option value="no_cumple_criterios">No cumple criterios de ingreso</option>
                                    <option value="falta_informacion">Falta información clínica</option>
                                    <option value="no_disponibilidad">No hay disponibilidad de camas</option>
                                    <option value="manejo_ambulatorio">Puede manejarse ambulatoriamente</option>
                                    <option value="otra_especialidad">Requiere otra especialidad</option>
                                    <option value="otro">Otro motivo</option>
                                </select>
                            </div>
                        </div>

                        <div id="info-request-fields" style="display: none;">
                            <div class="mb-3">
                                <label for="informacion_requerida" class="form-label font-weight-bold">
                                    <i class="fas fa-info-circle mr-1"></i>Información Requerida
                                </label>
                                <textarea class="form-control" id="informacion_requerida" 
                                          name="informacion_requerida" rows="3" 
                                          placeholder="Especifique qué información adicional necesita...">{{ old('informacion_requerida') }}</textarea>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-between">
                                    <button type="button" class="btn btn-outline-secondary" onclick="history.back()">
                                        <i class="fas fa-arrow-left mr-1"></i>Cancelar
                                    </button>
                                    <button type="submit" class="btn btn-primary" id="submit-btn">
                                        <i class="fas fa-save mr-1"></i>Guardar Evaluación
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Handle decision change
    $('#decision_medica').change(function() {
        const decision = $(this).val();
        
        // Hide all conditional fields
        $('#acceptance-fields, #rejection-fields, #info-request-fields').hide();
        
        // Show relevant fields based on decision
        if (decision === 'aceptar') {
            $('#acceptance-fields').show();
            $('#submit-btn').html('<i class="fas fa-check mr-1"></i>Aceptar Traslado');
            $('#submit-btn').removeClass('btn-primary btn-danger').addClass('btn-success');
        } else if (decision === 'rechazar') {
            $('#rejection-fields').show();
            $('#submit-btn').html('<i class="fas fa-times mr-1"></i>Rechazar Traslado');
            $('#submit-btn').removeClass('btn-primary btn-success').addClass('btn-danger');
        } else if (decision === 'solicitar_info') {
            $('#info-request-fields').show();
            $('#submit-btn').html('<i class="fas fa-question mr-1"></i>Solicitar Información');
            $('#submit-btn').removeClass('btn-success btn-danger').addClass('btn-primary');
        } else {
            $('#submit-btn').html('<i class="fas fa-save mr-1"></i>Guardar Evaluación');
            $('#submit-btn').removeClass('btn-success btn-danger').addClass('btn-primary');
        }
    });

    // Trigger change event on page load if there's a selected value
    if ($('#decision_medica').val()) {
        $('#decision_medica').trigger('change');
    }

    // Form validation
    $('#evaluation-form').submit(function(e) {
        const decision = $('#decision_medica').val();
        const observations = $('#observaciones_medico').val().trim();
        
        if (!decision) {
            e.preventDefault();
            alert('Por favor seleccione una decisión médica.');
            return false;
        }
        
        if (!observations) {
            e.preventDefault();
            alert('Por favor ingrese sus observaciones médicas.');
            return false;
        }
        
        if (decision === 'rechazar' && !$('#motivo_rechazo').val()) {
            e.preventDefault();
            alert('Por favor seleccione el motivo del rechazo.');
            return false;
        }
        
        // Confirm submission
        const confirmMessage = decision === 'aceptar' ? 
            '¿Está seguro de aceptar este traslado?' :
            decision === 'rechazar' ?
            '¿Está seguro de rechazar este traslado?' :
            '¿Está seguro de solicitar información adicional?';
            
        if (!confirm(confirmMessage)) {
            e.preventDefault();
            return false;
        }
        
        // Disable submit button to prevent double submission
        $('#submit-btn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Guardando...');
    });

    // Auto-save draft every 2 minutes
    setInterval(function() {
        saveDraft();
    }, 120000);
});

function saveDraft() {
    const formData = {
        decision_medica: $('#decision_medica').val(),
        observaciones_medico: $('#observaciones_medico').val(),
        prioridad_medica: $('#prioridad_medica').val(),
        _token: $('input[name="_token"]').val()
    };
    
    $.ajax({
        url: '{{ route("medico.guardar-borrador", $solicitud->id) }}',
        method: 'POST',
        data: formData,
        success: function(response) {
            if (response.success) {
                console.log('Draft saved successfully');
            }
        },
        error: function() {
            console.log('Error saving draft');
        }
    });
}
</script>
@endsection

@section('styles')
<style>
.card-header {
    background-color: #f8f9fc;
    border-bottom: 1px solid #e3e6f0;
}

.bg-light {
    background-color: #f8f9fa !important;
}

.table-borderless td {
    border: none;
    padding: 0.25rem 0.5rem;
}

.badge {
    font-size: 0.875em;
}

.form-label {
    margin-bottom: 0.5rem;
}

.alert {
    border-left: 4px solid;
}

.alert-danger {
    border-left-color: #dc3545;
}

#acceptance-fields, #rejection-fields, #info-request-fields {
    border: 1px solid #e3e6f0;
    border-radius: 0.35rem;
    padding: 1rem;
    margin-top: 1rem;
    background-color: #f8f9fc;
}

.btn {
    border-radius: 0.35rem;
}

.form-control:focus {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

@media (max-width: 768px) {
    .container-fluid {
        padding: 0.5rem;
    }
    
    .card-body {
        padding: 1rem;
    }
}
</style>
@endsection
