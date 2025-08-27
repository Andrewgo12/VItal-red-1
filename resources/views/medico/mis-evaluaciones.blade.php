@extends('layouts.app')

@section('title', 'Mis Evaluaciones')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-clipboard-check me-2"></i>
                            Mis Evaluaciones Médicas
                        </h4>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-primary btn-sm" onclick="exportEvaluations()">
                                <i class="fas fa-download me-1"></i>
                                Exportar
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" onclick="showFilters()">
                                <i class="fas fa-filter me-1"></i>
                                Filtros
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Statistics Summary -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h3 class="mb-1" id="totalEvaluations">{{ $evaluaciones->total() }}</h3>
                                    <small>Total Evaluaciones</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h3 class="mb-1" id="acceptedCases">{{ $evaluaciones->where('decision_medica', 'aceptar')->count() }}</h3>
                                    <small>Casos Aceptados</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <h3 class="mb-1" id="rejectedCases">{{ $evaluaciones->where('decision_medica', 'rechazar')->count() }}</h3>
                                    <small>Casos Rechazados</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h3 class="mb-1" id="infoRequested">{{ $evaluaciones->where('decision_medica', 'solicitar_info')->count() }}</h3>
                                    <small>Info Solicitada</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters Panel -->
                    <div class="card mb-3" id="filters-panel" style="display: none;">
                        <div class="card-body">
                            <form method="GET" action="{{ route('medico.mis-evaluaciones') }}">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label class="form-label">Decisión</label>
                                        <select class="form-select" name="decision">
                                            <option value="">Todas</option>
                                            <option value="aceptar" {{ request('decision') == 'aceptar' ? 'selected' : '' }}>Aceptar</option>
                                            <option value="rechazar" {{ request('decision') == 'rechazar' ? 'selected' : '' }}>Rechazar</option>
                                            <option value="solicitar_info" {{ request('decision') == 'solicitar_info' ? 'selected' : '' }}>Solicitar Info</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Fecha Desde</label>
                                        <input type="date" class="form-control" name="fecha_desde" value="{{ request('fecha_desde') }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Fecha Hasta</label>
                                        <input type="date" class="form-control" name="fecha_hasta" value="{{ request('fecha_hasta') }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">&nbsp;</label>
                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-search me-1"></i>Filtrar
                                            </button>
                                            <a href="{{ route('medico.mis-evaluaciones') }}" class="btn btn-outline-secondary">
                                                <i class="fas fa-times me-1"></i>Limpiar
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Evaluations Table -->
                    <div class="table-responsive">
                        <table class="table table-hover" id="evaluationsTable">
                            <thead>
                                <tr>
                                    <th>Paciente</th>
                                    <th>Especialidad</th>
                                    <th>Prioridad</th>
                                    <th>Decisión</th>
                                    <th>Fecha Evaluación</th>
                                    <th>Tiempo Respuesta</th>
                                    <th>Institución</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($evaluaciones as $evaluacion)
                                <tr>
                                    <td>
                                        <div>
                                            <strong>{{ $evaluacion->paciente_nombre }} {{ $evaluacion->paciente_apellidos }}</strong><br>
                                            <small class="text-muted">{{ Str::limit($evaluacion->diagnostico_principal, 40) }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark">{{ $evaluacion->especialidad_solicitada }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $evaluacion->prioridad_ia === 'Alta' ? 'danger' : ($evaluacion->prioridad_ia === 'Media' ? 'warning' : 'info') }}">
                                            {{ $evaluacion->prioridad_ia }}
                                        </span>
                                        @if($evaluacion->prioridad_medica && $evaluacion->prioridad_medica !== $evaluacion->prioridad_ia)
                                            <br><small class="text-muted">Médica: {{ $evaluacion->prioridad_medica }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $decisionColors = [
                                                'aceptar' => 'success',
                                                'rechazar' => 'danger',
                                                'solicitar_info' => 'warning'
                                            ];
                                            $decisionTexts = [
                                                'aceptar' => 'Aceptado',
                                                'rechazar' => 'Rechazado',
                                                'solicitar_info' => 'Info Solicitada'
                                            ];
                                        @endphp
                                        <span class="badge bg-{{ $decisionColors[$evaluacion->decision_medica] ?? 'secondary' }}">
                                            {{ $decisionTexts[$evaluacion->decision_medica] ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td>
                                        <small>{{ $evaluacion->fecha_evaluacion->format('d/m/Y') }}</small><br>
                                        <small class="text-muted">{{ $evaluacion->fecha_evaluacion->format('H:i') }}</small>
                                    </td>
                                    <td>
                                        @php
                                            $responseTime = $evaluacion->fecha_recepcion_email->diffInHours($evaluacion->fecha_evaluacion);
                                            $timeColor = $responseTime > 24 ? 'danger' : ($responseTime > 8 ? 'warning' : 'success');
                                        @endphp
                                        <span class="text-{{ $timeColor }}">
                                            {{ $responseTime }}h
                                        </span>
                                    </td>
                                    <td>
                                        <small>{{ Str::limit($evaluacion->institucion_remitente, 25) }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="viewEvaluation({{ $evaluacion->id }})" title="Ver Detalles">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="printEvaluation({{ $evaluacion->id }})" title="Imprimir">
                                                <i class="fas fa-print"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div>
                            <small class="text-muted">
                                Mostrando {{ $evaluaciones->firstItem() ?? 0 }} a {{ $evaluaciones->lastItem() ?? 0 }} de {{ $evaluaciones->total() }} evaluaciones
                            </small>
                        </div>
                        <div>
                            {{ $evaluaciones->appends(request()->query())->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Evaluation Details Modal -->
<div class="modal fade" id="evaluationDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-clipboard-check me-2"></i>
                    Detalles de la Evaluación
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="evaluationDetailsContent">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="printFromModal" onclick="printCurrentEvaluation()">
                    <i class="fas fa-print me-1"></i>Imprimir
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let currentEvaluationId = null;

function showFilters() {
    const panel = document.getElementById('filters-panel');
    panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
}

function viewEvaluation(evaluationId) {
    currentEvaluationId = evaluationId;
    
    const modal = new bootstrap.Modal(document.getElementById('evaluationDetailsModal'));
    modal.show();
    
    fetch(`/api/solicitudes-medicas/${evaluationId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showEvaluationDetails(data.data);
            } else {
                document.getElementById('evaluationDetailsContent').innerHTML = 
                    '<div class="alert alert-danger">Error al cargar los detalles de la evaluación</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('evaluationDetailsContent').innerHTML = 
                '<div class="alert alert-danger">Error al cargar los detalles de la evaluación</div>';
        });
}

function showEvaluationDetails(evaluacion) {
    const decisionColors = {
        'aceptar': 'success',
        'rechazar': 'danger',
        'solicitar_info': 'warning'
    };
    
    const decisionTexts = {
        'aceptar': 'Aceptado',
        'rechazar': 'Rechazado',
        'solicitar_info': 'Información Solicitada'
    };

    const content = `
        <div class="row">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="fas fa-user me-2"></i>Información del Paciente</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr><td><strong>Nombre:</strong></td><td>${evaluacion.paciente_nombre} ${evaluacion.paciente_apellidos || ''}</td></tr>
                            <tr><td><strong>Edad:</strong></td><td>${evaluacion.paciente_edad || 'No especificada'}</td></tr>
                            <tr><td><strong>Sexo:</strong></td><td>${evaluacion.paciente_sexo || 'No especificado'}</td></tr>
                            <tr><td><strong>Identificación:</strong></td><td>${evaluacion.paciente_identificacion || 'No especificada'}</td></tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="fas fa-stethoscope me-2"></i>Información Médica</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr><td><strong>Especialidad:</strong></td><td>${evaluacion.especialidad_solicitada}</td></tr>
                            <tr><td><strong>Prioridad IA:</strong></td><td><span class="badge bg-${evaluacion.prioridad_ia === 'Alta' ? 'danger' : (evaluacion.prioridad_ia === 'Media' ? 'warning' : 'info')}">${evaluacion.prioridad_ia}</span></td></tr>
                            <tr><td><strong>Prioridad Médica:</strong></td><td><span class="badge bg-${evaluacion.prioridad_medica === 'Alta' ? 'danger' : (evaluacion.prioridad_medica === 'Media' ? 'warning' : 'info')}">${evaluacion.prioridad_medica || 'No asignada'}</span></td></tr>
                            <tr><td><strong>Score Urgencia:</strong></td><td>${evaluacion.score_urgencia || 0}/100</td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="fas fa-notes-medical me-2"></i>Información Clínica</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Diagnóstico Principal</h6>
                                <p class="border p-2 rounded">${evaluacion.diagnostico_principal}</p>
                                
                                <h6>Motivo de Consulta</h6>
                                <p class="border p-2 rounded">${evaluacion.motivo_consulta || 'No especificado'}</p>
                            </div>
                            <div class="col-md-6">
                                ${evaluacion.antecedentes_medicos ? `
                                <h6>Antecedentes Médicos</h6>
                                <p class="border p-2 rounded">${evaluacion.antecedentes_medicos}</p>
                                ` : ''}
                                
                                ${evaluacion.medicamentos_actuales ? `
                                <h6>Medicamentos Actuales</h6>
                                <p class="border p-2 rounded">${evaluacion.medicamentos_actuales}</p>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>Evaluación Médica</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Decisión Médica</h6>
                                <p><span class="badge bg-${decisionColors[evaluacion.decision_medica]} fs-6">${decisionTexts[evaluacion.decision_medica]}</span></p>
                                
                                <h6>Observaciones Médicas</h6>
                                <p class="border p-2 rounded">${evaluacion.observaciones_medico}</p>
                                
                                ${evaluacion.decision_medica === 'aceptar' ? `
                                <h6>Servicio de Destino</h6>
                                <p>${evaluacion.servicio_destino || 'No especificado'}</p>
                                
                                <h6>Fecha Programada</h6>
                                <p>${evaluacion.fecha_programada ? formatDateTime(evaluacion.fecha_programada) : 'No especificada'}</p>
                                ` : ''}
                                
                                ${evaluacion.decision_medica === 'rechazar' ? `
                                <h6>Motivo del Rechazo</h6>
                                <p class="border p-2 rounded">${evaluacion.motivo_rechazo || 'No especificado'}</p>
                                ` : ''}
                                
                                ${evaluacion.decision_medica === 'solicitar_info' ? `
                                <h6>Información Requerida</h6>
                                <p class="border p-2 rounded">${evaluacion.informacion_requerida || 'No especificada'}</p>
                                ` : ''}
                            </div>
                            <div class="col-md-6">
                                <h6>Información Temporal</h6>
                                <table class="table table-sm">
                                    <tr><td><strong>Fecha Recepción:</strong></td><td>${formatDateTime(evaluacion.fecha_recepcion_email)}</td></tr>
                                    <tr><td><strong>Fecha Evaluación:</strong></td><td>${formatDateTime(evaluacion.fecha_evaluacion)}</td></tr>
                                    <tr><td><strong>Tiempo de Respuesta:</strong></td><td>${calculateResponseTime(evaluacion.fecha_recepcion_email, evaluacion.fecha_evaluacion)}</td></tr>
                                </table>
                                
                                <h6>Institución Remitente</h6>
                                <table class="table table-sm">
                                    <tr><td><strong>Institución:</strong></td><td>${evaluacion.institucion_remitente}</td></tr>
                                    <tr><td><strong>Médico:</strong></td><td>${evaluacion.medico_remitente || 'No especificado'}</td></tr>
                                    <tr><td><strong>Email:</strong></td><td>${evaluacion.email_remitente || 'No especificado'}</td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('evaluationDetailsContent').innerHTML = content;
}

function printEvaluation(evaluationId) {
    currentEvaluationId = evaluationId;
    printCurrentEvaluation();
}

function printCurrentEvaluation() {
    if (!currentEvaluationId) return;
    
    // Create a new window for printing
    const printWindow = window.open('', '_blank');
    const content = document.getElementById('evaluationDetailsContent').innerHTML;
    
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Evaluación Médica - ${currentEvaluationId}</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                @media print {
                    .no-print { display: none !important; }
                    body { font-size: 12px; }
                    .card { border: 1px solid #dee2e6 !important; }
                    .card-header { background-color: #f8f9fa !important; color: #000 !important; }
                }
                body { font-family: Arial, sans-serif; }
                .header { text-align: center; margin-bottom: 20px; }
                .logo { max-width: 200px; }
            </style>
        </head>
        <body>
            <div class="container-fluid">
                <div class="header">
                    <h2>Sistema Vital Red</h2>
                    <h4>Evaluación Médica</h4>
                    <p>Fecha de impresión: ${new Date().toLocaleDateString('es-ES')}</p>
                </div>
                ${content}
            </div>
            <script>
                window.onload = function() {
                    window.print();
                    window.onafterprint = function() {
                        window.close();
                    };
                };
            </script>
        </body>
        </html>
    `);
    
    printWindow.document.close();
}

function exportEvaluations() {
    const params = new URLSearchParams(window.location.search);
    params.append('format', 'csv');
    
    window.location.href = '{{ route("medico.mis-evaluaciones") }}?' + params.toString();
}

function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES') + ' ' + date.toLocaleTimeString('es-ES', {hour: '2-digit', minute: '2-digit'});
}

function calculateResponseTime(startDate, endDate) {
    const start = new Date(startDate);
    const end = new Date(endDate);
    const diffInHours = Math.floor((end - start) / (1000 * 60 * 60));
    
    if (diffInHours < 1) return 'Menos de 1 hora';
    if (diffInHours < 24) return `${diffInHours} horas`;
    
    const days = Math.floor(diffInHours / 24);
    const hours = diffInHours % 24;
    return `${days} días${hours > 0 ? ` y ${hours} horas` : ''}`;
}

// Initialize DataTable
$(document).ready(function() {
    $('#evaluationsTable').DataTable({
        "pageLength": 15,
        "order": [[ 4, "desc" ]],
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        },
        "columnDefs": [
            { "orderable": false, "targets": [7] }
        ]
    });
});
</script>
@endpush
