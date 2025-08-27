@extends('layouts.app')

@section('title', 'Reportes del Sistema')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fas fa-chart-bar me-2"></i>
                        Reportes del Sistema
                    </h4>
                </div>
                <div class="card-body">
                    <!-- Report Types -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white h-100 report-card" onclick="loadReport('medical_requests')">
                                <div class="card-body text-center">
                                    <i class="fas fa-file-medical fa-3x mb-3"></i>
                                    <h5>Solicitudes Médicas</h5>
                                    <p class="mb-0">Análisis de casos médicos recibidos y evaluados</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white h-100 report-card" onclick="loadReport('performance')">
                                <div class="card-body text-center">
                                    <i class="fas fa-tachometer-alt fa-3x mb-3"></i>
                                    <h5>Rendimiento</h5>
                                    <p class="mb-0">Métricas de rendimiento del sistema y usuarios</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white h-100 report-card" onclick="loadReport('audit')">
                                <div class="card-body text-center">
                                    <i class="fas fa-shield-alt fa-3x mb-3"></i>
                                    <h5>Auditoría</h5>
                                    <p class="mb-0">Registro de actividades y eventos de seguridad</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white h-100 report-card" onclick="loadReport('trends')">
                                <div class="card-body text-center">
                                    <i class="fas fa-chart-line fa-3x mb-3"></i>
                                    <h5>Tendencias</h5>
                                    <p class="mb-0">Análisis de tendencias y patrones</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Report Filters -->
                    <div class="card mb-4" id="report-filters" style="display: none;">
                        <div class="card-header">
                            <h5 class="mb-0">Filtros de Reporte</h5>
                        </div>
                        <div class="card-body">
                            <form id="reportForm">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label class="form-label">Fecha Inicio</label>
                                        <input type="date" class="form-control" name="start_date" value="{{ date('Y-m-01') }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Fecha Fin</label>
                                        <input type="date" class="form-control" name="end_date" value="{{ date('Y-m-d') }}">
                                    </div>
                                    <div class="col-md-2" id="specialty-filter" style="display: none;">
                                        <label class="form-label">Especialidad</label>
                                        <select class="form-select" name="specialty">
                                            <option value="">Todas</option>
                                            <option value="Cardiología">Cardiología</option>
                                            <option value="Neurología">Neurología</option>
                                            <option value="Pediatría">Pediatría</option>
                                            <option value="Medicina Interna">Medicina Interna</option>
                                            <option value="Ginecología">Ginecología</option>
                                            <option value="Urología">Urología</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2" id="priority-filter" style="display: none;">
                                        <label class="form-label">Prioridad</label>
                                        <select class="form-select" name="priority">
                                            <option value="">Todas</option>
                                            <option value="Alta">Alta</option>
                                            <option value="Media">Media</option>
                                            <option value="Baja">Baja</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">&nbsp;</label>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-primary" onclick="generateReport()">
                                                <i class="fas fa-chart-bar me-1"></i>Generar
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary" onclick="exportReport()">
                                                <i class="fas fa-download me-1"></i>Exportar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Report Content -->
                    <div id="report-content" style="display: none;">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0" id="report-title">Reporte</h5>
                            </div>
                            <div class="card-body">
                                <div id="report-loading" class="text-center" style="display: none;">
                                    <div class="spinner-border" role="status">
                                        <span class="visually-hidden">Cargando...</span>
                                    </div>
                                    <p class="mt-2">Generando reporte...</p>
                                </div>
                                <div id="report-data"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.report-card {
    cursor: pointer;
    transition: all 0.3s ease;
}

.report-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.report-card.active {
    border: 3px solid #fff;
    box-shadow: 0 0 20px rgba(255, 255, 255, 0.5);
}

.chart-container {
    position: relative;
    height: 400px;
    margin: 20px 0;
}
</style>
@endsection

@push('scripts')
<script>
let currentReportType = null;

function loadReport(reportType) {
    currentReportType = reportType;
    
    // Update active card
    document.querySelectorAll('.report-card').forEach(card => {
        card.classList.remove('active');
    });
    event.currentTarget.classList.add('active');
    
    // Show filters
    document.getElementById('report-filters').style.display = 'block';
    
    // Show/hide specific filters based on report type
    const specialtyFilter = document.getElementById('specialty-filter');
    const priorityFilter = document.getElementById('priority-filter');
    
    if (reportType === 'medical_requests') {
        specialtyFilter.style.display = 'block';
        priorityFilter.style.display = 'block';
    } else {
        specialtyFilter.style.display = 'none';
        priorityFilter.style.display = 'none';
    }
    
    // Update report title
    const titles = {
        'medical_requests': 'Reporte de Solicitudes Médicas',
        'performance': 'Reporte de Rendimiento',
        'audit': 'Reporte de Auditoría',
        'trends': 'Análisis de Tendencias'
    };
    
    document.getElementById('report-title').textContent = titles[reportType];
    
    // Auto-generate report
    generateReport();
}

function generateReport() {
    if (!currentReportType) return;
    
    const formData = new FormData(document.getElementById('reportForm'));
    const params = new URLSearchParams();
    
    for (let [key, value] of formData.entries()) {
        if (value) params.append(key, value);
    }
    
    // Show loading
    document.getElementById('report-content').style.display = 'block';
    document.getElementById('report-loading').style.display = 'block';
    document.getElementById('report-data').innerHTML = '';
    
    // Fetch report data
    fetch(`/api/reports/${currentReportType}?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('report-loading').style.display = 'none';
            
            if (data.success) {
                renderReport(currentReportType, data.data);
            } else {
                document.getElementById('report-data').innerHTML = 
                    '<div class="alert alert-danger">Error al generar reporte: ' + data.message + '</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('report-loading').style.display = 'none';
            document.getElementById('report-data').innerHTML = 
                '<div class="alert alert-danger">Error al cargar reporte</div>';
        });
}

function renderReport(reportType, data) {
    const container = document.getElementById('report-data');
    
    switch (reportType) {
        case 'medical_requests':
            renderMedicalRequestsReport(container, data);
            break;
        case 'performance':
            renderPerformanceReport(container, data);
            break;
        case 'audit':
            renderAuditReport(container, data);
            break;
        case 'trends':
            renderTrendsReport(container, data);
            break;
    }
}

function renderMedicalRequestsReport(container, data) {
    const summary = data.summary;
    
    container.innerHTML = `
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h3>${summary.total_requests}</h3>
                        <small>Total Solicitudes</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <h3>${summary.urgent_cases}</h3>
                        <small>Casos Urgentes</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <h3>${summary.pending_cases}</h3>
                        <small>Casos Pendientes</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h3>${summary.acceptance_rate}%</h3>
                        <small>Tasa Aceptación</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6>Casos por Prioridad</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="priorityChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6>Casos por Especialidad</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="specialtyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h6>Tiempo Promedio de Respuesta: ${summary.avg_response_time} horas</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Especialidad</th>
                                        <th>Total Casos</th>
                                        <th>Casos Urgentes</th>
                                        <th>Tasa Aceptación</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${Object.entries(summary.by_specialty).map(([specialty, count]) => `
                                        <tr>
                                            <td>${specialty}</td>
                                            <td>${count}</td>
                                            <td>-</td>
                                            <td>-</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Render charts
    renderPriorityChart(summary.by_priority);
    renderSpecialtyChart(summary.by_specialty);
}

function renderPerformanceReport(container, data) {
    container.innerHTML = `
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h3>${data.total_evaluations || 0}</h3>
                        <small>Total Evaluaciones</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h3>${data.avg_response_time || 0}h</h3>
                        <small>Tiempo Promedio</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <h3>${data.sla_compliance || 0}%</h3>
                        <small>Cumplimiento SLA</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            Reporte de rendimiento en desarrollo. Próximamente disponible con métricas detalladas.
        </div>
    `;
}

function renderAuditReport(container, data) {
    container.innerHTML = `
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            Reporte de auditoría en desarrollo. Próximamente disponible con logs detallados de actividad.
        </div>
    `;
}

function renderTrendsReport(container, data) {
    container.innerHTML = `
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            Análisis de tendencias en desarrollo. Próximamente disponible con predicciones y patrones.
        </div>
    `;
}

function renderPriorityChart(data) {
    const ctx = document.getElementById('priorityChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: Object.keys(data),
            datasets: [{
                data: Object.values(data),
                backgroundColor: ['#dc3545', '#ffc107', '#17a2b8']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

function renderSpecialtyChart(data) {
    const ctx = document.getElementById('specialtyChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: Object.keys(data),
            datasets: [{
                label: 'Casos',
                data: Object.values(data),
                backgroundColor: '#007bff'
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
                    beginAtZero: true
                }
            }
        }
    });
}

function exportReport() {
    if (!currentReportType) return;
    
    const formData = new FormData(document.getElementById('reportForm'));
    const params = new URLSearchParams();
    
    for (let [key, value] of formData.entries()) {
        if (value) params.append(key, value);
    }
    
    params.append('format', 'csv');
    
    window.location.href = `/admin/reports/export/${currentReportType}?${params.toString()}`;
}
</script>
@endpush
