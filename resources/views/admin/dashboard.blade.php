@extends('layouts.app')

@section('title', 'Dashboard Administrativo')

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-tachometer-alt mr-2"></i>Dashboard Administrativo
        </h1>
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-primary" onclick="refreshDashboard()">
                <i class="fas fa-sync-alt mr-1"></i>Actualizar
            </button>
            <a href="{{ route('admin.reports') }}" class="btn btn-sm btn-primary">
                <i class="fas fa-chart-bar mr-1"></i>Reportes
            </a>
        </div>
    </div>

    <!-- Content Row - Overview Cards -->
    <div class="row">
        <!-- Total Requests Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Solicitudes (Mes)
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="total-requests">
                                {{ $metrics['overview']['today']['total_solicitudes'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-medical fa-2x text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Urgent Cases Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Casos Urgentes Pendientes
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="urgent-pending">
                                {{ $metrics['alerts']['urgent_pending'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Acceptance Rate Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Tasa de Aceptación
                            </div>
                            <div class="row no-gutters align-items-center">
                                <div class="col-auto">
                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800" id="acceptance-rate">
                                        {{ $metrics['overview']['rates']['acceptance_rate'] ?? 0 }}%
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="progress progress-sm mr-2">
                                        <div class="progress-bar bg-success" role="progressbar"
                                             style="width: {{ $metrics['overview']['rates']['acceptance_rate'] ?? 0 }}%"
                                             aria-valuenow="{{ $metrics['overview']['rates']['acceptance_rate'] ?? 0 }}" 
                                             aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Status Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Estado del Sistema
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <span class="badge badge-success" id="system-status">Operativo</span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-server fa-2x text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Row - Charts and Tables -->
    <div class="row">
        <!-- Daily Activity Chart -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-line mr-2"></i>Actividad Diaria (Últimos 7 días)
                    </h6>
                    <div class="dropdown no-arrow">
                        <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink"
                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in"
                            aria-labelledby="dropdownMenuLink">
                            <div class="dropdown-header">Opciones:</div>
                            <a class="dropdown-item" href="#" onclick="exportChart('daily-activity')">
                                <i class="fas fa-download fa-sm fa-fw mr-2 text-gray-400"></i>
                                Exportar Gráfico
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="dailyActivityChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Priority Distribution -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-pie mr-2"></i>Distribución por Prioridad
                    </h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <canvas id="priorityChart"></canvas>
                    </div>
                    <div class="mt-4 text-center small">
                        <span class="mr-2">
                            <i class="fas fa-circle text-danger"></i> Alta
                        </span>
                        <span class="mr-2">
                            <i class="fas fa-circle text-warning"></i> Media
                        </span>
                        <span class="mr-2">
                            <i class="fas fa-circle text-info"></i> Baja
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Row - Recent Activity and Alerts -->
    <div class="row">
        <!-- Recent Medical Requests -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-clock mr-2"></i>Solicitudes Recientes
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Paciente</th>
                                    <th>Especialidad</th>
                                    <th>Prioridad</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody id="recent-requests">
                                @forelse($recentRequests ?? [] as $request)
                                <tr>
                                    <td>{{ $request->paciente_nombre }}</td>
                                    <td><span class="badge badge-light">{{ $request->especialidad_solicitada }}</span></td>
                                    <td>
                                        <span class="badge badge-{{ $request->prioridad_ia === 'Alta' ? 'danger' : 
                                            ($request->prioridad_ia === 'Media' ? 'warning' : 'info') }}">
                                            {{ $request->prioridad_ia }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ 
                                            $request->estado === 'pendiente_evaluacion' ? 'warning' :
                                            ($request->estado === 'evaluada' ? 'success' : 'secondary')
                                        }}">
                                            {{ ucfirst(str_replace('_', ' ', $request->estado)) }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No hay solicitudes recientes</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center">
                        <a class="small" href="{{ route('admin.solicitudes') }}">Ver Todas las Solicitudes →</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Alerts -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-bell mr-2"></i>Alertas del Sistema
                    </h6>
                </div>
                <div class="card-body">
                    <div id="system-alerts">
                        @forelse($metrics['alerts']['system_alerts'] ?? [] as $alert)
                        <div class="alert alert-{{ $alert['severity'] === 'high' ? 'danger' : 
                            ($alert['severity'] === 'medium' ? 'warning' : 'info') }} alert-dismissible fade show" role="alert">
                            <strong>{{ $alert['type'] }}:</strong> {{ $alert['message'] }}
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                        @empty
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-check-circle fa-2x mb-2"></i>
                            <br>No hay alertas activas
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Row - Performance Metrics -->
    <div class="row">
        <!-- Performance Metrics -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-tachometer-alt mr-2"></i>Métricas de Rendimiento
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <div class="mb-3">
                                <div class="h4 font-weight-bold text-primary" id="avg-processing-time">
                                    {{ number_format($metrics['performance']['processing_times']['avg_ai_processing_seconds'] ?? 0, 1) }}s
                                </div>
                                <div class="text-xs text-gray-600">Tiempo Promedio de Procesamiento IA</div>
                            </div>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="mb-3">
                                <div class="h4 font-weight-bold text-success" id="avg-evaluation-time">
                                    {{ number_format($metrics['performance']['processing_times']['avg_medical_evaluation_minutes'] ?? 0, 1) }}min
                                </div>
                                <div class="text-xs text-gray-600">Tiempo Promedio de Evaluación Médica</div>
                            </div>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="mb-3">
                                <div class="h4 font-weight-bold text-info" id="notification-success-rate">
                                    {{ $metrics['performance']['notifications']['success_rate'] ?? 0 }}%
                                </div>
                                <div class="text-xs text-gray-600">Tasa de Éxito de Notificaciones</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Specialties -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-list-ol mr-2"></i>Top Especialidades
                    </h6>
                </div>
                <div class="card-body">
                    @forelse($metrics['solicitudes']['specialty_distribution'] ?? [] as $specialty => $count)
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-sm">{{ $specialty }}</span>
                        <span class="badge badge-primary">{{ $count }}</span>
                    </div>
                    @empty
                    <div class="text-center text-muted">No hay datos disponibles</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Gmail Monitor Status -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-envelope mr-2"></i>Estado del Monitor de Gmail
                    </h6>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-success" onclick="startGmailMonitor()">
                            <i class="fas fa-play mr-1"></i>Iniciar
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="stopGmailMonitor()">
                            <i class="fas fa-stop mr-1"></i>Detener
                        </button>
                        <button class="btn btn-sm btn-outline-info" onclick="checkGmailStatus()">
                            <i class="fas fa-sync mr-1"></i>Estado
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="h5 font-weight-bold" id="gmail-status">
                                    <span class="badge badge-secondary">Verificando...</span>
                                </div>
                                <div class="text-xs text-gray-600">Estado del Servicio</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="h5 font-weight-bold text-primary" id="emails-processed">0</div>
                                <div class="text-xs text-gray-600">Emails Procesados Hoy</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="h5 font-weight-bold text-success" id="medical-emails-found">0</div>
                                <div class="text-xs text-gray-600">Emails Médicos Detectados</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="h5 font-weight-bold text-info" id="last-check">--</div>
                                <div class="text-xs text-gray-600">Última Verificación</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    // Initialize charts
    initializeDailyActivityChart();
    initializePriorityChart();
    
    // Check Gmail status on load
    checkGmailStatus();
    
    // Auto-refresh every 30 seconds
    setInterval(function() {
        refreshDashboard();
    }, 30000);
    
    // Real-time updates
    if (typeof Echo !== 'undefined') {
        Echo.channel('admin-notifications')
            .listen('UrgentMedicalCaseDetected', (e) => {
                updateUrgentCount();
                showNotification('Nuevo caso urgente detectado: ' + e.patient_name, 'warning');
            })
            .listen('MedicalRequestEvaluated', (e) => {
                updateMetrics();
                showNotification('Solicitud evaluada: ' + e.patient_name, 'info');
            });
    }
});

function initializeDailyActivityChart() {
    const ctx = document.getElementById('dailyActivityChart').getContext('2d');
    const dailyData = @json($metrics['activity']['daily_activity'] ?? []);
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: Object.keys(dailyData),
            datasets: [{
                label: 'Solicitudes',
                data: Object.values(dailyData),
                borderColor: '#4e73df',
                backgroundColor: 'rgba(78, 115, 223, 0.1)',
                borderWidth: 2,
                fill: true
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

function initializePriorityChart() {
    const ctx = document.getElementById('priorityChart').getContext('2d');
    const priorityData = @json($metrics['solicitudes']['priority_distribution'] ?? []);
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: Object.keys(priorityData),
            datasets: [{
                data: Object.values(priorityData),
                backgroundColor: ['#dc3545', '#ffc107', '#17a2b8'],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
}

function refreshDashboard() {
    $.ajax({
        url: '/api/metrics/dashboard',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                updateDashboardMetrics(response.data);
            }
        },
        error: function() {
            console.log('Error refreshing dashboard');
        }
    });
}

function updateDashboardMetrics(data) {
    // Update overview cards
    $('#total-requests').text(data.overview.today.total_solicitudes || 0);
    $('#urgent-pending').text(data.alerts.urgent_pending || 0);
    $('#acceptance-rate').text((data.overview.rates.acceptance_rate || 0) + '%');
    
    // Update progress bar
    $('.progress-bar').css('width', (data.overview.rates.acceptance_rate || 0) + '%');
    
    // Update performance metrics
    $('#avg-processing-time').text((data.performance.processing_times.avg_ai_processing_seconds || 0).toFixed(1) + 's');
    $('#avg-evaluation-time').text((data.performance.processing_times.avg_medical_evaluation_minutes || 0).toFixed(1) + 'min');
    $('#notification-success-rate').text((data.performance.notifications.success_rate || 0) + '%');
}

function checkGmailStatus() {
    $.ajax({
        url: '/api/gmail-monitor/status',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                updateGmailStatus(response.data);
            } else {
                $('#gmail-status').html('<span class="badge badge-danger">Error</span>');
            }
        },
        error: function() {
            $('#gmail-status').html('<span class="badge badge-danger">Desconectado</span>');
        }
    });
}

function updateGmailStatus(data) {
    const isRunning = data.is_running;
    const statusBadge = isRunning ? 
        '<span class="badge badge-success">Activo</span>' : 
        '<span class="badge badge-secondary">Inactivo</span>';
    
    $('#gmail-status').html(statusBadge);
    $('#emails-processed').text(data.emails_processed_today || 0);
    $('#medical-emails-found').text(data.medical_emails_found || 0);
    $('#last-check').text(data.last_check || '--');
}

function startGmailMonitor() {
    $.ajax({
        url: '/api/gmail-monitor/start',
        method: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                showNotification('Monitor de Gmail iniciado correctamente', 'success');
                checkGmailStatus();
            } else {
                showNotification('Error al iniciar el monitor: ' + response.message, 'error');
            }
        },
        error: function() {
            showNotification('Error de conexión al iniciar el monitor', 'error');
        }
    });
}

function stopGmailMonitor() {
    $.ajax({
        url: '/api/gmail-monitor/stop',
        method: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                showNotification('Monitor de Gmail detenido', 'info');
                checkGmailStatus();
            } else {
                showNotification('Error al detener el monitor: ' + response.message, 'error');
            }
        },
        error: function() {
            showNotification('Error de conexión al detener el monitor', 'error');
        }
    });
}

function showNotification(message, type = 'info') {
    const alertClass = type === 'error' ? 'alert-danger' : 
                      type === 'success' ? 'alert-success' : 
                      type === 'warning' ? 'alert-warning' : 'alert-info';
    
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show notification-alert" role="alert">
            ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    `;
    
    $('.container-fluid').prepend(alertHtml);
    
    setTimeout(function() {
        $('.notification-alert').fadeOut();
    }, 5000);
}

function exportChart(chartType) {
    // Implementation for chart export
    console.log('Exporting chart:', chartType);
}
</script>
@endsection

@section('styles')
<style>
.notification-alert {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 1050;
    min-width: 300px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.card {
    border: none;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
}

.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}

.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}

.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}

.border-left-danger {
    border-left: 0.25rem solid #e74a3b !important;
}

.text-xs {
    font-size: 0.7rem;
}

.progress-sm {
    height: 0.5rem;
}

.chart-area {
    position: relative;
    height: 320px;
}

.chart-pie {
    position: relative;
    height: 245px;
}

.table-sm th,
.table-sm td {
    padding: 0.3rem;
}

.badge {
    font-size: 0.75em;
}

@media (max-width: 768px) {
    .container-fluid {
        padding: 0.5rem;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .h5 {
        font-size: 1rem;
    }
}
</style>
@endsection
