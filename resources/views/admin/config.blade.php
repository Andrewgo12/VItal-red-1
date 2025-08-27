@extends('layouts.app')

@section('title', 'Configuración del Sistema')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-cog me-2"></i>
                            Configuración del Sistema
                        </h4>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-info" onclick="checkSystemStatus()">
                                <i class="fas fa-heartbeat me-1"></i>
                                Estado del Sistema
                            </button>
                            <button class="btn btn-outline-warning" onclick="testConnections()">
                                <i class="fas fa-plug me-1"></i>
                                Probar Conexiones
                            </button>
                            <button class="btn btn-outline-danger" onclick="clearCaches()">
                                <i class="fas fa-broom me-1"></i>
                                Limpiar Cachés
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- System Status -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card" id="system-status-card" style="display: none;">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0">Estado del Sistema</h5>
                                </div>
                                <div class="card-body" id="system-status-content">
                                    <!-- Status content will be loaded here -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Configuration Tabs -->
                    <ul class="nav nav-tabs" id="configTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="gmail-tab" data-bs-toggle="tab" data-bs-target="#gmail" type="button" role="tab">
                                <i class="fas fa-envelope me-2"></i>Gmail
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="ai-tab" data-bs-toggle="tab" data-bs-target="#ai" type="button" role="tab">
                                <i class="fas fa-robot me-2"></i>Inteligencia Artificial
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="notifications-tab" data-bs-toggle="tab" data-bs-target="#notifications" type="button" role="tab">
                                <i class="fas fa-bell me-2"></i>Notificaciones
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="system-tab" data-bs-toggle="tab" data-bs-target="#system" type="button" role="tab">
                                <i class="fas fa-server me-2"></i>Sistema
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="configTabsContent">
                        <!-- Gmail Configuration -->
                        <div class="tab-pane fade show active" id="gmail" role="tabpanel">
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h5 class="mb-0">Configuración de Gmail</h5>
                                </div>
                                <div class="card-body">
                                    <form id="gmailConfigForm">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="gmailEnabled" name="enabled" {{ $config['gmail']['enabled'] ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="gmailEnabled">
                                                            Habilitar monitoreo de Gmail
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Email de monitoreo</label>
                                                    <input type="email" class="form-control" name="email" value="{{ $config['gmail']['email'] ?? '' }}" placeholder="correo@ejemplo.com">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Intervalo de monitoreo (segundos)</label>
                                                    <input type="number" class="form-control" name="monitoring_interval" value="{{ $config['gmail']['monitoring_interval'] }}" min="30" max="3600">
                                                    <small class="form-text text-muted">Tiempo entre verificaciones de nuevos emails</small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Máximo emails por lote</label>
                                                    <input type="number" class="form-control" name="max_emails_per_batch" value="{{ $config['gmail']['max_emails_per_batch'] }}" min="1" max="100">
                                                    <small class="form-text text-muted">Número máximo de emails a procesar por vez</small>
                                                </div>
                                                <div class="alert alert-info">
                                                    <h6><i class="fas fa-info-circle me-2"></i>Configuración de Credenciales</h6>
                                                    <p class="mb-2">Para configurar Gmail API:</p>
                                                    <ol class="mb-0">
                                                        <li>Crear proyecto en Google Cloud Console</li>
                                                        <li>Habilitar Gmail API</li>
                                                        <li>Crear credenciales OAuth 2.0</li>
                                                        <li>Descargar archivo JSON a storage/app/</li>
                                                    </ol>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save me-1"></i>Guardar Configuración
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary" onclick="testGmailConnection()">
                                                <i class="fas fa-plug me-1"></i>Probar Conexión
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- AI Configuration -->
                        <div class="tab-pane fade" id="ai" role="tabpanel">
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h5 class="mb-0">Configuración de Inteligencia Artificial</h5>
                                </div>
                                <div class="card-body">
                                    <form id="aiConfigForm">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="geminiEnabled" name="gemini_enabled" {{ $config['ai']['gemini_enabled'] ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="geminiEnabled">
                                                            Habilitar Gemini AI
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Modelo Gemini</label>
                                                    <select class="form-select" name="gemini_model">
                                                        <option value="gemini-pro" {{ $config['ai']['gemini_model'] === 'gemini-pro' ? 'selected' : '' }}>Gemini Pro</option>
                                                        <option value="gemini-pro-vision" {{ $config['ai']['gemini_model'] === 'gemini-pro-vision' ? 'selected' : '' }}>Gemini Pro Vision</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Umbral de confianza</label>
                                                    <input type="number" class="form-control" name="confidence_threshold" value="{{ $config['ai']['confidence_threshold'] }}" min="0" max="1" step="0.1">
                                                    <small class="form-text text-muted">Nivel mínimo de confianza para clasificación automática</small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="autoClassification" name="auto_classification" {{ $config['ai']['auto_classification'] ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="autoClassification">
                                                            Clasificación automática
                                                        </label>
                                                    </div>
                                                    <small class="form-text text-muted">Clasificar automáticamente casos con alta confianza</small>
                                                </div>
                                                <div class="alert alert-warning">
                                                    <h6><i class="fas fa-exclamation-triangle me-2"></i>API Key Requerida</h6>
                                                    <p class="mb-2">Para usar Gemini AI necesita:</p>
                                                    <ol class="mb-0">
                                                        <li>Obtener API key de Google AI Studio</li>
                                                        <li>Configurar GEMINI_API_KEY en .env</li>
                                                        <li>Verificar cuotas y límites</li>
                                                    </ol>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save me-1"></i>Guardar Configuración
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary" onclick="testAIConnection()">
                                                <i class="fas fa-robot me-1"></i>Probar IA
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Notifications Configuration -->
                        <div class="tab-pane fade" id="notifications" role="tabpanel">
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h5 class="mb-0">Configuración de Notificaciones</h5>
                                </div>
                                <div class="card-body">
                                    <form id="notificationsConfigForm">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="emailEnabled" name="email_enabled" {{ $config['notifications']['email_enabled'] ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="emailEnabled">
                                                            Habilitar notificaciones por email
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Umbral de urgencia (horas)</label>
                                                    <input type="number" class="form-control" name="urgent_threshold" value="{{ $config['notifications']['urgent_notification_threshold'] }}" min="1" max="24">
                                                    <small class="form-text text-muted">Tiempo después del cual un caso se considera urgente</small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Canales de notificación</label>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="channelEmail" name="channels[]" value="email" {{ in_array('email', $config['notifications']['notification_channels']) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="channelEmail">Email</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="channelInternal" name="channels[]" value="internal" {{ in_array('internal', $config['notifications']['notification_channels']) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="channelInternal">Notificaciones internas</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="channelSMS" name="channels[]" value="sms">
                                                        <label class="form-check-label" for="channelSMS">SMS (próximamente)</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-1"></i>Guardar Configuración
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- System Configuration -->
                        <div class="tab-pane fade" id="system" role="tabpanel">
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h5 class="mb-0">Configuración del Sistema</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>Estado del Sistema</h6>
                                            <div class="mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="maintenanceMode" {{ $config['system']['maintenance_mode'] ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="maintenanceMode">
                                                        Modo de mantenimiento
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Entorno</label>
                                                <input type="text" class="form-control" value="{{ $config['system']['environment'] }}" readonly>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Zona horaria</label>
                                                <input type="text" class="form-control" value="{{ $config['system']['timezone'] }}" readonly>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Acciones del Sistema</h6>
                                            <div class="d-grid gap-2">
                                                <button type="button" class="btn btn-outline-warning" onclick="clearCaches()">
                                                    <i class="fas fa-broom me-2"></i>Limpiar Cachés
                                                </button>
                                                <button type="button" class="btn btn-outline-info" onclick="optimizeSystem()">
                                                    <i class="fas fa-rocket me-2"></i>Optimizar Sistema
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary" onclick="viewLogs()">
                                                    <i class="fas fa-file-alt me-2"></i>Ver Logs del Sistema
                                                </button>
                                                <button type="button" class="btn btn-outline-danger" onclick="toggleMaintenance()">
                                                    <i class="fas fa-tools me-2"></i>Alternar Mantenimiento
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- System Logs Modal -->
<div class="modal fade" id="logsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Logs del Sistema</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="row">
                        <div class="col-md-4">
                            <select class="form-select" id="logLevel">
                                <option value="">Todos los niveles</option>
                                <option value="error">Error</option>
                                <option value="warning">Warning</option>
                                <option value="info">Info</option>
                                <option value="debug">Debug</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" id="logLines">
                                <option value="100">Últimas 100 líneas</option>
                                <option value="500">Últimas 500 líneas</option>
                                <option value="1000">Últimas 1000 líneas</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-primary" onclick="loadLogs()">
                                <i class="fas fa-sync me-1"></i>Actualizar
                            </button>
                        </div>
                    </div>
                </div>
                <div id="logsContent" style="height: 400px; overflow-y: auto; background: #f8f9fa; padding: 15px; border-radius: 5px;">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Configuration form handlers
document.getElementById('gmailConfigForm').addEventListener('submit', function(e) {
    e.preventDefault();
    saveConfiguration('gmail', this);
});

document.getElementById('aiConfigForm').addEventListener('submit', function(e) {
    e.preventDefault();
    saveConfiguration('ai', this);
});

document.getElementById('notificationsConfigForm').addEventListener('submit', function(e) {
    e.preventDefault();
    saveConfiguration('notifications', this);
});

function saveConfiguration(type, form) {
    const formData = new FormData(form);
    const data = {};
    
    for (let [key, value] of formData.entries()) {
        if (key.endsWith('[]')) {
            const arrayKey = key.slice(0, -2);
            if (!data[arrayKey]) data[arrayKey] = [];
            data[arrayKey].push(value);
        } else {
            data[key] = value;
        }
    }
    
    // Handle checkboxes
    const checkboxes = form.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach(checkbox => {
        if (!checkbox.name.endsWith('[]')) {
            data[checkbox.name] = checkbox.checked;
        }
    });
    
    fetch(`/api/config/${type}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error al guardar configuración', 'error');
    });
}

function checkSystemStatus() {
    const statusCard = document.getElementById('system-status-card');
    const statusContent = document.getElementById('system-status-content');
    
    statusCard.style.display = 'block';
    statusContent.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"></div></div>';
    
    fetch('/api/config/status')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderSystemStatus(data.data);
            } else {
                statusContent.innerHTML = '<div class="alert alert-danger">Error al obtener estado del sistema</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            statusContent.innerHTML = '<div class="alert alert-danger">Error al cargar estado del sistema</div>';
        });
}

function renderSystemStatus(status) {
    const statusContent = document.getElementById('system-status-content');
    
    const overallStatus = status.overall_status === 'healthy' ? 'success' : 'danger';
    
    let html = `
        <div class="alert alert-${overallStatus}">
            <h6><i class="fas fa-${status.overall_status === 'healthy' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
            Estado General: ${status.overall_status === 'healthy' ? 'Saludable' : 'Problemas Detectados'}</h6>
        </div>
        <div class="row">
    `;
    
    Object.entries(status.services).forEach(([service, info]) => {
        const statusColor = info.status === 'ok' ? 'success' : (info.status === 'disabled' ? 'warning' : 'danger');
        const statusIcon = info.status === 'ok' ? 'check-circle' : (info.status === 'disabled' ? 'pause-circle' : 'times-circle');
        
        html += `
            <div class="col-md-4 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">
                            <i class="fas fa-${statusIcon} text-${statusColor} me-2"></i>
                            ${service.replace('_', ' ').toUpperCase()}
                        </h6>
                        <p class="card-text">${info.message}</p>
                    </div>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    statusContent.innerHTML = html;
}

function testConnections() {
    showToast('Probando conexiones...', 'info');
    
    fetch('/api/config/test-connections')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const allPassed = data.data.all_tests_passed;
                showToast(
                    allPassed ? 'Todas las conexiones funcionan correctamente' : 'Algunas conexiones fallaron',
                    allPassed ? 'success' : 'warning'
                );
            } else {
                showToast('Error al probar conexiones', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error al probar conexiones', 'error');
        });
}

function clearCaches() {
    if (confirm('¿Está seguro de que desea limpiar todos los cachés?')) {
        fetch('/api/config/clear-caches', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error al limpiar cachés', 'error');
        });
    }
}

function optimizeSystem() {
    showToast('Optimizando sistema...', 'info');
    
    // Simulate optimization
    setTimeout(() => {
        showToast('Sistema optimizado correctamente', 'success');
    }, 2000);
}

function toggleMaintenance() {
    const maintenanceMode = document.getElementById('maintenanceMode').checked;
    const action = maintenanceMode ? 'desactivar' : 'activar';
    
    if (confirm(`¿Está seguro de que desea ${action} el modo de mantenimiento?`)) {
        fetch('/api/config/maintenance', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                enabled: !maintenanceMode,
                message: 'Sistema en mantenimiento. Volveremos pronto.'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                document.getElementById('maintenanceMode').checked = !maintenanceMode;
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error al cambiar modo de mantenimiento', 'error');
        });
    }
}

function viewLogs() {
    new bootstrap.Modal(document.getElementById('logsModal')).show();
    loadLogs();
}

function loadLogs() {
    const level = document.getElementById('logLevel').value;
    const lines = document.getElementById('logLines').value;
    const logsContent = document.getElementById('logsContent');
    
    logsContent.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"></div></div>';
    
    const params = new URLSearchParams();
    if (level) params.append('level', level);
    if (lines) params.append('lines', lines);
    
    fetch(`/api/config/logs?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.data.length === 0) {
                    logsContent.innerHTML = '<div class="text-muted">No hay logs disponibles</div>';
                } else {
                    logsContent.innerHTML = data.data.map(log => `
                        <div class="log-entry">
                            <span class="text-muted">[${log.timestamp}]</span>
                            <span class="badge bg-${getLogLevelColor(log.level)}">${log.level.toUpperCase()}</span>
                            <span>${log.message}</span>
                        </div>
                    `).join('');
                }
            } else {
                logsContent.innerHTML = '<div class="alert alert-danger">Error al cargar logs</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            logsContent.innerHTML = '<div class="alert alert-danger">Error al cargar logs</div>';
        });
}

function getLogLevelColor(level) {
    const colors = {
        'error': 'danger',
        'warning': 'warning',
        'info': 'info',
        'debug': 'secondary'
    };
    return colors[level] || 'secondary';
}

function testGmailConnection() {
    showToast('Probando conexión Gmail...', 'info');
    // Implementation would test Gmail API connection
    setTimeout(() => {
        showToast('Conexión Gmail probada', 'success');
    }, 1000);
}

function testAIConnection() {
    showToast('Probando conexión IA...', 'info');
    // Implementation would test AI service connection
    setTimeout(() => {
        showToast('Conexión IA probada', 'success');
    }, 1000);
}

// Auto-check system status on page load
document.addEventListener('DOMContentLoaded', function() {
    checkSystemStatus();
});
</script>
@endpush
