@extends('layouts.app')

@section('title', 'Bandeja de Casos Médicos')

@section('content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-inbox mr-2"></i>Bandeja de Casos Médicos
                    </h1>
                    <p class="text-muted">Gestión y evaluación de solicitudes de referencia médica</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary" onclick="refreshCases()">
                        <i class="fas fa-sync-alt mr-1"></i>Actualizar
                    </button>
                    <button class="btn btn-primary" onclick="showFilters()">
                        <i class="fas fa-filter mr-1"></i>Filtros
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Casos Urgentes
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="urgent-count">
                                {{ $statistics['urgent_cases'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Pendientes Evaluación
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="pending-count">
                                {{ $statistics['pending_cases'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Evaluadas Hoy
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="today-count">
                                {{ $statistics['today_evaluated'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Total del Mes
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="month-count">
                                {{ $statistics['month_total'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar fa-2x text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Panel -->
    <div class="card shadow mb-4" id="filters-panel" style="display: none;">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-filter mr-2"></i>Filtros de Búsqueda
            </h6>
        </div>
        <div class="card-body">
            <form id="filters-form">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="filter-priority">Prioridad</label>
                            <select class="form-control" id="filter-priority" name="prioridad">
                                <option value="">Todas las prioridades</option>
                                <option value="Alta">Alta</option>
                                <option value="Media">Media</option>
                                <option value="Baja">Baja</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="filter-status">Estado</label>
                            <select class="form-control" id="filter-status" name="estado">
                                <option value="">Todos los estados</option>
                                <option value="pendiente_evaluacion">Pendiente Evaluación</option>
                                <option value="en_evaluacion">En Evaluación</option>
                                <option value="evaluada">Evaluada</option>
                                <option value="aceptada">Aceptada</option>
                                <option value="rechazada">Rechazada</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="filter-specialty">Especialidad</label>
                            <select class="form-control" id="filter-specialty" name="especialidad">
                                <option value="">Todas las especialidades</option>
                                @foreach($specialties as $specialty)
                                    <option value="{{ $specialty }}">{{ $specialty }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="filter-date">Fecha Recepción</label>
                            <input type="date" class="form-control" id="filter-date" name="fecha">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="filter-institution">Institución Remitente</label>
                            <input type="text" class="form-control" id="filter-institution" 
                                   name="institucion" placeholder="Buscar por institución...">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="filter-patient">Paciente</label>
                            <input type="text" class="form-control" id="filter-patient" 
                                   name="paciente" placeholder="Buscar por nombre del paciente...">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <button type="button" class="btn btn-primary" onclick="applyFilters()">
                            <i class="fas fa-search mr-1"></i>Aplicar Filtros
                        </button>
                        <button type="button" class="btn btn-secondary ml-2" onclick="clearFilters()">
                            <i class="fas fa-times mr-1"></i>Limpiar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Cases Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list mr-2"></i>Lista de Casos
            </h6>
            <div class="dropdown no-arrow">
                <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink"
                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in"
                    aria-labelledby="dropdownMenuLink">
                    <div class="dropdown-header">Acciones:</div>
                    <a class="dropdown-item" href="#" onclick="exportCases()">
                        <i class="fas fa-download fa-sm fa-fw mr-2 text-gray-400"></i>
                        Exportar Lista
                    </a>
                    <a class="dropdown-item" href="#" onclick="printCases()">
                        <i class="fas fa-print fa-sm fa-fw mr-2 text-gray-400"></i>
                        Imprimir
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="cases-table" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Prioridad</th>
                            <th>Paciente</th>
                            <th>Institución</th>
                            <th>Especialidad</th>
                            <th>Diagnóstico</th>
                            <th>Fecha Recepción</th>
                            <th>Estado</th>
                            <th>Tiempo Espera</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="cases-tbody">
                        @forelse($solicitudes as $solicitud)
                        <tr class="case-row" data-id="{{ $solicitud->id }}" 
                            data-priority="{{ $solicitud->prioridad_ia }}">
                            <td>
                                <span class="badge badge-{{ $solicitud->prioridad_ia === 'Alta' ? 'danger' : 
                                    ($solicitud->prioridad_ia === 'Media' ? 'warning' : 'info') }}">
                                    {{ $solicitud->prioridad_ia }}
                                    @if($solicitud->score_urgencia)
                                        <small>({{ $solicitud->score_urgencia }})</small>
                                    @endif
                                </span>
                            </td>
                            <td>
                                <div class="font-weight-bold">{{ $solicitud->paciente_nombre }}</div>
                                @if($solicitud->paciente_apellidos)
                                    <small class="text-muted">{{ $solicitud->paciente_apellidos }}</small>
                                @endif
                                @if($solicitud->paciente_edad)
                                    <br><small class="text-info">{{ $solicitud->paciente_edad }} años</small>
                                @endif
                            </td>
                            <td>
                                <div class="text-truncate" style="max-width: 150px;" 
                                     title="{{ $solicitud->institucion_remitente }}">
                                    {{ $solicitud->institucion_remitente }}
                                </div>
                                @if($solicitud->medico_remitente)
                                    <small class="text-muted">Dr. {{ $solicitud->medico_remitente }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-light">{{ $solicitud->especialidad_solicitada }}</span>
                            </td>
                            <td>
                                <div class="text-truncate" style="max-width: 200px;" 
                                     title="{{ $solicitud->diagnostico_principal }}">
                                    {{ $solicitud->diagnostico_principal }}
                                </div>
                            </td>
                            <td>
                                <div>{{ $solicitud->fecha_recepcion_email->format('d/m/Y') }}</div>
                                <small class="text-muted">{{ $solicitud->fecha_recepcion_email->format('H:i') }}</small>
                            </td>
                            <td>
                                <span class="badge badge-{{ 
                                    $solicitud->estado === 'pendiente_evaluacion' ? 'warning' :
                                    ($solicitud->estado === 'evaluada' ? 'success' :
                                    ($solicitud->estado === 'aceptada' ? 'primary' :
                                    ($solicitud->estado === 'rechazada' ? 'danger' : 'secondary')))
                                }}">
                                    {{ ucfirst(str_replace('_', ' ', $solicitud->estado)) }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $horasEspera = $solicitud->fecha_recepcion_email->diffInHours(now());
                                @endphp
                                <span class="text-{{ $horasEspera > 24 ? 'danger' : ($horasEspera > 8 ? 'warning' : 'success') }}">
                                    {{ $horasEspera }}h
                                </span>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                            onclick="viewCase({{ $solicitud->id }})" 
                                            title="Ver Detalles">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    @if($solicitud->estado === 'pendiente_evaluacion')
                                        <button type="button" class="btn btn-sm btn-primary" 
                                                onclick="evaluateCase({{ $solicitud->id }})" 
                                                title="Evaluar">
                                            <i class="fas fa-stethoscope"></i>
                                        </button>
                                    @endif
                                    @if($solicitud->estado === 'evaluada' && $solicitud->medico_evaluador_id === auth()->id())
                                        <button type="button" class="btn btn-sm btn-outline-secondary" 
                                                onclick="editEvaluation({{ $solicitud->id }})" 
                                                title="Editar Evaluación">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <br>No hay casos disponibles con los filtros actuales
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            @if($solicitudes->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $solicitudes->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Case Details Modal -->
<div class="modal fade" id="caseDetailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-file-medical mr-2"></i>Detalles del Caso
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="case-details-content">
                <!-- Content loaded dynamically -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="evaluate-from-modal" style="display: none;">
                    <i class="fas fa-stethoscope mr-1"></i>Evaluar Caso
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#cases-table').DataTable({
        "pageLength": 25,
        "order": [[ 5, "desc" ]], // Order by reception date
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json"
        },
        "columnDefs": [
            { "orderable": false, "targets": 8 } // Actions column
        ]
    });

    // Auto-refresh every 5 minutes
    setInterval(function() {
        if (!$('#filters-panel').is(':visible')) {
            refreshCases();
        }
    }, 300000);

    // Real-time notifications
    if (typeof Echo !== 'undefined') {
        Echo.channel('medical-alerts')
            .listen('UrgentMedicalCaseDetected', (e) => {
                showUrgentAlert(e);
                refreshCases();
            });
    }
});

function showFilters() {
    $('#filters-panel').slideToggle();
}

function applyFilters() {
    const formData = new FormData($('#filters-form')[0]);
    const params = new URLSearchParams(formData);
    
    window.location.href = '{{ route("medico.bandeja-casos") }}?' + params.toString();
}

function clearFilters() {
    $('#filters-form')[0].reset();
    window.location.href = '{{ route("medico.bandeja-casos") }}';
}

function refreshCases() {
    location.reload();
}

function viewCase(caseId) {
    $.ajax({
        url: `/api/solicitudes-medicas/${caseId}`,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                renderCaseDetails(response.data);
                $('#caseDetailsModal').modal('show');
            } else {
                showAlert('Error al cargar los detalles del caso', 'error');
            }
        },
        error: function() {
            showAlert('Error de conexión al cargar el caso', 'error');
        }
    });
}

function evaluateCase(caseId) {
    window.location.href = `/medico/evaluar-solicitud/${caseId}`;
}

function editEvaluation(caseId) {
    window.location.href = `/medico/evaluar-solicitud/${caseId}?edit=1`;
}

function renderCaseDetails(caseData) {
    const content = `
        <div class="row">
            <div class="col-md-6">
                <h6 class="font-weight-bold text-primary">Información del Paciente</h6>
                <table class="table table-sm">
                    <tr><td><strong>Nombre:</strong></td><td>${caseData.paciente_nombre} ${caseData.paciente_apellidos || ''}</td></tr>
                    <tr><td><strong>Edad:</strong></td><td>${caseData.paciente_edad || 'No especificada'}</td></tr>
                    <tr><td><strong>Sexo:</strong></td><td>${caseData.paciente_sexo || 'No especificado'}</td></tr>
                    <tr><td><strong>Identificación:</strong></td><td>${caseData.paciente_identificacion || 'No especificada'}</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6 class="font-weight-bold text-primary">Información de Referencia</h6>
                <table class="table table-sm">
                    <tr><td><strong>Institución:</strong></td><td>${caseData.institucion_remitente}</td></tr>
                    <tr><td><strong>Médico:</strong></td><td>${caseData.medico_remitente || 'No especificado'}</td></tr>
                    <tr><td><strong>Especialidad:</strong></td><td>${caseData.especialidad_solicitada}</td></tr>
                    <tr><td><strong>Tipo:</strong></td><td>${caseData.tipo_solicitud}</td></tr>
                </table>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-12">
                <h6 class="font-weight-bold text-primary">Información Clínica</h6>
                <div class="card">
                    <div class="card-body">
                        <p><strong>Diagnóstico Principal:</strong><br>${caseData.diagnostico_principal}</p>
                        ${caseData.motivo_consulta ? `<p><strong>Motivo de Consulta:</strong><br>${caseData.motivo_consulta}</p>` : ''}
                        ${caseData.enfermedad_actual ? `<p><strong>Enfermedad Actual:</strong><br>${caseData.enfermedad_actual}</p>` : ''}
                        ${caseData.antecedentes_medicos ? `<p><strong>Antecedentes:</strong><br>${caseData.antecedentes_medicos}</p>` : ''}
                    </div>
                </div>
            </div>
        </div>
        ${caseData.estado === 'pendiente_evaluacion' ? 
            '<div class="alert alert-warning mt-3"><i class="fas fa-clock mr-2"></i>Este caso está pendiente de evaluación médica.</div>' : ''}
    `;
    
    $('#case-details-content').html(content);
    
    // Show evaluate button if case is pending
    if (caseData.estado === 'pendiente_evaluacion') {
        $('#evaluate-from-modal').show().off('click').on('click', function() {
            evaluateCase(caseData.id);
        });
    } else {
        $('#evaluate-from-modal').hide();
    }
}

function showUrgentAlert(eventData) {
    const alertHtml = `
        <div class="alert alert-danger alert-dismissible fade show urgent-alert" role="alert">
            <strong><i class="fas fa-exclamation-triangle mr-2"></i>Caso Urgente Detectado!</strong>
            <br>Paciente: ${eventData.patient_name} - ${eventData.specialty}
            <br>Institución: ${eventData.institution}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    `;
    
    $('.container-fluid').prepend(alertHtml);
    
    // Auto-remove after 10 seconds
    setTimeout(function() {
        $('.urgent-alert').fadeOut();
    }, 10000);
}

function exportCases() {
    window.open('/medico/bandeja-casos/export', '_blank');
}

function printCases() {
    window.print();
}

function showAlert(message, type = 'info') {
    const alertClass = type === 'error' ? 'alert-danger' : 'alert-info';
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    `;
    
    $('.container-fluid').prepend(alertHtml);
    
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);
}
</script>
@endsection

@section('styles')
<style>
.case-row[data-priority="Alta"] {
    border-left: 4px solid #dc3545;
}

.case-row[data-priority="Media"] {
    border-left: 4px solid #ffc107;
}

.case-row[data-priority="Baja"] {
    border-left: 4px solid #17a2b8;
}

.urgent-alert {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 1050;
    min-width: 300px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.table-responsive {
    max-height: 600px;
}

.btn-group .btn {
    margin-right: 2px;
}

.badge {
    font-size: 0.75em;
}

@media print {
    .btn, .dropdown, .card-header .dropdown {
        display: none !important;
    }
}
</style>
@endsection
