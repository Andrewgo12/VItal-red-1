@extends('layouts.app')

@section('title', 'Dashboard Médico')

@section('content')
<div class="container-fluid">
    <!-- Welcome Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-1">¡Bienvenido, Dr. {{ Auth::user()->name }}!</h2>
                            <p class="mb-0">
                                <i class="fas fa-clock me-2"></i>
                                {{ now()->format('l, d \d\e F \d\e Y - H:i') }}
                            </p>
                        </div>
                        <div class="text-end">
                            <div class="badge bg-light text-primary fs-6 px-3 py-2">
                                <i class="fas fa-user-md me-2"></i>
                                {{ ucfirst(Auth::user()->role) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-danger text-white h-100">
                <div class="card-body text-center">
                    <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                    <h3 class="mb-1">{{ $urgentCases->count() }}</h3>
                    <p class="mb-0">Casos Urgentes</p>
                    <small class="opacity-75">Requieren atención inmediata</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white h-100">
                <div class="card-body text-center">
                    <i class="fas fa-inbox fa-2x mb-3"></i>
                    <h3 class="mb-1">{{ $metrics['pending_cases'] }}</h3>
                    <p class="mb-0">Casos Pendientes</p>
                    <small class="opacity-75">Esperando evaluación</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white h-100">
                <div class="card-body text-center">
                    <i class="fas fa-clipboard-check fa-2x mb-3"></i>
                    <h3 class="mb-1">{{ $metrics['my_evaluations_today'] }}</h3>
                    <p class="mb-0">Evaluaciones Hoy</p>
                    <small class="opacity-75">Casos evaluados</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body text-center">
                    <i class="fas fa-chart-line fa-2x mb-3"></i>
                    <h3 class="mb-1">{{ $metrics['avg_response_time'] }}h</h3>
                    <p class="mb-0">Tiempo Promedio</p>
                    <small class="opacity-75">Respuesta promedio</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Row -->
    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- Urgent Cases Alert -->
            @if($urgentCases->isNotEmpty())
            <div class="card border-danger mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Casos Urgentes Pendientes
                    </h5>
                </div>
                <div class="card-body">
                    @foreach($urgentCases->take(3) as $caso)
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                        <div>
                            <strong>{{ $caso->paciente_nombre }} {{ $caso->paciente_apellidos }}</strong><br>
                            <small class="text-muted">
                                {{ $caso->especialidad_solicitada }} - {{ $caso->institucion_remitente }}
                            </small><br>
                            <small class="text-danger">
                                <i class="fas fa-clock me-1"></i>
                                Recibido hace {{ $caso->fecha_recepcion_email->diffForHumans() }}
                            </small>
                        </div>
                        <div>
                            <a href="{{ route('medico.evaluar-solicitud', $caso->id) }}" class="btn btn-danger btn-sm">
                                <i class="fas fa-stethoscope me-1"></i>
                                Evaluar
                            </a>
                        </div>
                    </div>
                    @endforeach
                    
                    @if($urgentCases->count() > 3)
                    <div class="text-center mt-3">
                        <a href="{{ route('medico.bandeja-casos', ['prioridad' => 'Alta']) }}" class="btn btn-outline-danger">
                            Ver todos los casos urgentes ({{ $urgentCases->count() }})
                        </a>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Recent Cases -->
            <div class="card mb-4">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-clock me-2"></i>
                            Casos Recientes
                        </h5>
                        <a href="{{ route('medico.bandeja-casos') }}" class="btn btn-outline-primary btn-sm">
                            Ver todos
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($recentCases->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Paciente</th>
                                        <th>Especialidad</th>
                                        <th>Prioridad</th>
                                        <th>Recibido</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentCases->take(5) as $caso)
                                    <tr>
                                        <td>
                                            <strong>{{ $caso->paciente_nombre }}</strong><br>
                                            <small class="text-muted">{{ Str::limit($caso->diagnostico_principal, 30) }}</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark">{{ $caso->especialidad_solicitada }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $caso->prioridad_ia === 'Alta' ? 'danger' : ($caso->prioridad_ia === 'Media' ? 'warning' : 'info') }}">
                                                {{ $caso->prioridad_ia }}
                                            </span>
                                        </td>
                                        <td>
                                            <small>{{ $caso->fecha_recepcion_email->format('d/m H:i') }}</small>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="viewCase({{ $caso->id }})">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                @if($caso->estado === 'pendiente_evaluacion')
                                                <a href="{{ route('medico.evaluar-solicitud', $caso->id) }}" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-stethoscope"></i>
                                                </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>No hay casos recientes</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- My Recent Evaluations -->
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-clipboard-check me-2"></i>
                            Mis Evaluaciones Recientes
                        </h5>
                        <a href="{{ route('medico.mis-evaluaciones') }}" class="btn btn-outline-primary btn-sm">
                            Ver todas
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($myEvaluationsToday->isNotEmpty())
                        <div class="list-group list-group-flush">
                            @foreach($myEvaluationsToday as $evaluacion)
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>{{ $evaluacion->paciente_nombre }}</strong> - {{ $evaluacion->especialidad_solicitada }}<br>
                                    <small class="text-muted">
                                        Evaluado: {{ $evaluacion->fecha_evaluacion->format('H:i') }} - 
                                        <span class="badge bg-{{ $evaluacion->decision_medica === 'aceptar' ? 'success' : 'warning' }}">
                                            {{ ucfirst($evaluacion->decision_medica) }}
                                        </span>
                                    </small>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="viewCase({{ $evaluacion->id }})">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-clipboard fa-3x mb-3"></i>
                            <p>No hay evaluaciones hoy</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-lg-4">
            <!-- Performance Summary -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar me-2"></i>
                        Mi Rendimiento
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 border-end">
                            <h4 class="text-success">{{ $metrics['my_evaluations_week'] }}</h4>
                            <small class="text-muted">Esta Semana</small>
                        </div>
                        <div class="col-6">
                            <h4 class="text-info">{{ $metrics['acceptance_rate'] }}%</h4>
                            <small class="text-muted">Tasa Aceptación</small>
                        </div>
                    </div>
                    <hr>
                    <div class="row text-center">
                        <div class="col-6 border-end">
                            <h4 class="text-warning">{{ $metrics['my_evaluations_month'] }}</h4>
                            <small class="text-muted">Este Mes</small>
                        </div>
                        <div class="col-6">
                            <h4 class="text-primary">{{ $metrics['avg_response_time'] }}h</h4>
                            <small class="text-muted">Tiempo Promedio</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cases by Specialty -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie me-2"></i>
                        Casos por Especialidad
                    </h5>
                </div>
                <div class="card-body">
                    @if(!empty($metrics['cases_by_specialty']))
                        @foreach($metrics['cases_by_specialty'] as $specialty)
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>{{ $specialty['especialidad_solicitada'] }}</span>
                            <span class="badge bg-primary">{{ $specialty['total'] }}</span>
                        </div>
                        @endforeach
                    @else
                        <div class="text-center text-muted">
                            <i class="fas fa-chart-pie fa-2x mb-2"></i>
                            <p>No hay datos disponibles</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Daily Activity Chart -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-activity me-2"></i>
                        Actividad Semanal
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="activityChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Case Details Modal -->
<div class="modal fade" id="caseDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalles del Caso</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="caseDetailsContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Activity Chart
const ctx = document.getElementById('activityChart').getContext('2d');
const activityChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: @json(collect($metrics['daily_activity'])->pluck('day')),
        datasets: [{
            label: 'Casos Recibidos',
            data: @json(collect($metrics['daily_activity'])->pluck('count')),
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.1)',
            tension: 0.1,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// Auto-refresh dashboard every 5 minutes
setInterval(function() {
    window.location.reload();
}, 300000);

// Check for urgent cases every 30 seconds
setInterval(function() {
    checkUrgentCases();
}, 30000);

function checkUrgentCases() {
    fetch('/api/solicitudes-medicas/urgentes')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.length > 0) {
                // Update urgent cases count
                const urgentCount = document.querySelector('.bg-danger .card-body h3');
                if (urgentCount) {
                    urgentCount.textContent = data.data.length;
                }
                
                // Show notification if there are new urgent cases
                const latestUrgent = data.data[0];
                const caseTime = new Date(latestUrgent.fecha_recepcion_email);
                const now = new Date();
                const diffMinutes = (now - caseTime) / (1000 * 60);
                
                if (diffMinutes < 5) { // Case received in last 5 minutes
                    showUrgentNotification(latestUrgent);
                }
            }
        })
        .catch(error => console.error('Error checking urgent cases:', error));
}

function showUrgentNotification(caso) {
    if (Notification.permission === 'granted') {
        new Notification('Nuevo Caso Urgente', {
            body: `${caso.paciente_nombre} - ${caso.especialidad_solicitada}`,
            icon: '/favicon.ico',
            tag: 'urgent-case'
        });
    }
    
    // Also show in-app notification
    showToast(`Nuevo caso urgente: ${caso.paciente_nombre} - ${caso.especialidad_solicitada}`, 'danger');
}

function viewCase(caseId) {
    fetch(`/api/solicitudes-medicas/${caseId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showCaseDetails(data.data);
            } else {
                alert('Error al cargar los detalles del caso');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cargar los detalles del caso');
        });
}

function showCaseDetails(caso) {
    const content = `
        <div class="row">
            <div class="col-md-6">
                <h6>Información del Paciente</h6>
                <p><strong>Nombre:</strong> ${caso.paciente_nombre} ${caso.paciente_apellidos || ''}</p>
                <p><strong>Edad:</strong> ${caso.paciente_edad || 'No especificada'}</p>
                <p><strong>Sexo:</strong> ${caso.paciente_sexo || 'No especificado'}</p>
            </div>
            <div class="col-md-6">
                <h6>Información Médica</h6>
                <p><strong>Especialidad:</strong> ${caso.especialidad_solicitada}</p>
                <p><strong>Prioridad:</strong> <span class="badge bg-${caso.prioridad_ia === 'Alta' ? 'danger' : (caso.prioridad_ia === 'Media' ? 'warning' : 'info')}">${caso.prioridad_ia}</span></p>
                <p><strong>Estado:</strong> ${caso.estado}</p>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <h6>Diagnóstico Principal</h6>
                <p>${caso.diagnostico_principal}</p>
                
                <h6>Motivo de Consulta</h6>
                <p>${caso.motivo_consulta || 'No especificado'}</p>
                
                <h6>Institución Remitente</h6>
                <p>${caso.institucion_remitente}</p>
            </div>
        </div>
    `;
    
    document.getElementById('caseDetailsContent').innerHTML = content;
    new bootstrap.Modal(document.getElementById('caseDetailsModal')).show();
}

// Request notification permission
if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission();
}

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
@endpush
