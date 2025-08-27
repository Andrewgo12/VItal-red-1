<template>
  <div class="supervision-container">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="h3 mb-0 text-primary">
          <i class="fas fa-eye me-2"></i>
          Supervisión del Sistema
        </h1>
        <p class="text-muted mb-0">
          Monitoreo en tiempo real de la actividad del sistema médico
        </p>
      </div>
      <div class="d-flex gap-2">
        <button
          class="btn btn-outline-primary btn-sm"
          @click="refreshData"
          :disabled="loading"
        >
          <i class="fas fa-sync-alt" :class="{ 'fa-spin': loading }"></i>
          Actualizar
        </button>
        <div class="form-check form-switch">
          <input
            class="form-check-input"
            type="checkbox"
            id="autoRefresh"
            v-model="autoRefresh"
          >
          <label class="form-check-label" for="autoRefresh">
            Auto-actualizar
          </label>
        </div>
      </div>
    </div>

    <!-- System Status Cards -->
    <div class="row mb-4">
      <div class="col-md-3 mb-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body text-center">
            <div class="status-icon mb-3" :class="systemStatus.overall === 'healthy' ? 'text-success' : 'text-danger'">
              <i class="fas fa-heartbeat fa-2x"></i>
            </div>
            <h5 class="card-title">Estado del Sistema</h5>
            <span class="badge fs-6" :class="systemStatus.overall === 'healthy' ? 'bg-success' : 'bg-danger'">
              {{ systemStatus.overall === 'healthy' ? 'Operativo' : 'Con Problemas' }}
            </span>
            <p class="text-muted mt-2 mb-0">
              <small>Última verificación: {{ formatDate(systemStatus.last_check, 'HH:mm:ss') }}</small>
            </p>
          </div>
        </div>
      </div>

      <div class="col-md-3 mb-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body text-center">
            <div class="status-icon mb-3 text-info">
              <i class="fas fa-users fa-2x"></i>
            </div>
            <h5 class="card-title">Usuarios Activos</h5>
            <h3 class="text-primary mb-0">{{ activeUsers.count }}</h3>
            <p class="text-muted mt-2 mb-0">
              <small>
                <i class="fas fa-arrow-up text-success me-1"></i>
                +{{ activeUsers.increase }}% vs ayer
              </small>
            </p>
          </div>
        </div>
      </div>

      <div class="col-md-3 mb-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body text-center">
            <div class="status-icon mb-3 text-warning">
              <i class="fas fa-envelope fa-2x"></i>
            </div>
            <h5 class="card-title">Emails Procesados</h5>
            <h3 class="text-primary mb-0">{{ emailStats.processed_today }}</h3>
            <p class="text-muted mt-2 mb-0">
              <small>{{ emailStats.pending }} pendientes</small>
            </p>
          </div>
        </div>
      </div>

      <div class="col-md-3 mb-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body text-center">
            <div class="status-icon mb-3 text-danger">
              <i class="fas fa-exclamation-triangle fa-2x"></i>
            </div>
            <h5 class="card-title">Casos Urgentes</h5>
            <h3 class="text-danger mb-0">{{ urgentCases.count }}</h3>
            <p class="text-muted mt-2 mb-0">
              <small>Requieren atención inmediata</small>
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Real-time Activity Feed -->
    <div class="row">
      <div class="col-lg-8 mb-4">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white">
            <div class="d-flex justify-content-between align-items-center">
              <h5 class="card-title mb-0">
                <i class="fas fa-stream me-2"></i>
                Actividad en Tiempo Real
              </h5>
              <div class="d-flex gap-2">
                <select v-model="activityFilter" class="form-select form-select-sm">
                  <option value="">Todas las actividades</option>
                  <option value="login">Inicios de sesión</option>
                  <option value="case_created">Casos creados</option>
                  <option value="case_evaluated">Casos evaluados</option>
                  <option value="email_processed">Emails procesados</option>
                  <option value="system_alert">Alertas del sistema</option>
                </select>
                <button class="btn btn-outline-secondary btn-sm" @click="clearActivityFeed">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </div>
          </div>
          <div class="card-body p-0">
            <div class="activity-feed" style="max-height: 500px; overflow-y: auto;">
              <div v-if="filteredActivities.length === 0" class="text-center py-4">
                <i class="fas fa-stream fa-2x text-muted mb-2"></i>
                <p class="text-muted mb-0">No hay actividad reciente</p>
              </div>
              <div
                v-else
                v-for="activity in filteredActivities"
                :key="activity.id"
                class="activity-item p-3 border-bottom"
                :class="{ 'activity-urgent': activity.type === 'urgent_case' }"
              >
                <div class="d-flex align-items-start">
                  <div class="activity-icon me-3">
                    <i :class="getActivityIcon(activity.type)"></i>
                  </div>
                  <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start">
                      <div>
                        <h6 class="mb-1">{{ activity.title }}</h6>
                        <p class="text-muted mb-1">{{ activity.description }}</p>
                        <small class="text-muted">
                          <i class="fas fa-user me-1"></i>
                          {{ activity.user_name }}
                          <span class="mx-2">•</span>
                          <i class="fas fa-clock me-1"></i>
                          {{ timeAgo(activity.created_at) }}
                        </small>
                      </div>
                      <span class="badge" :class="getActivityBadgeClass(activity.type)">
                        {{ getActivityTypeText(activity.type) }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-4 mb-4">
        <!-- System Health -->
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header bg-white">
            <h5 class="card-title mb-0">
              <i class="fas fa-heartbeat me-2"></i>
              Salud del Sistema
            </h5>
          </div>
          <div class="card-body">
            <div class="health-check-item mb-3" v-for="check in healthChecks" :key="check.name">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <span class="fw-semibold">{{ check.name }}</span>
                  <br>
                  <small class="text-muted">{{ check.description }}</small>
                </div>
                <div class="text-end">
                  <i
                    :class="check.status === 'healthy' ? 'fas fa-check-circle text-success' : 'fas fa-exclamation-circle text-danger'"
                  ></i>
                  <br>
                  <small class="text-muted">{{ check.response_time }}ms</small>
                </div>
              </div>
            </div>
            <button class="btn btn-outline-primary btn-sm w-100" @click="runHealthCheck">
              <i class="fas fa-stethoscope me-1"></i>
              Ejecutar Verificación
            </button>
          </div>
        </div>

        <!-- Performance Metrics -->
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white">
            <h5 class="card-title mb-0">
              <i class="fas fa-tachometer-alt me-2"></i>
              Métricas de Rendimiento
            </h5>
          </div>
          <div class="card-body">
            <div class="metric-item mb-3">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="text-muted">Uso de CPU</span>
                <span class="fw-semibold">{{ performance.cpu_usage }}%</span>
              </div>
              <div class="progress" style="height: 6px;">
                <div
                  class="progress-bar"
                  :class="performance.cpu_usage > 80 ? 'bg-danger' : performance.cpu_usage > 60 ? 'bg-warning' : 'bg-success'"
                  :style="{ width: performance.cpu_usage + '%' }"
                ></div>
              </div>
            </div>

            <div class="metric-item mb-3">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="text-muted">Uso de Memoria</span>
                <span class="fw-semibold">{{ performance.memory_usage }}%</span>
              </div>
              <div class="progress" style="height: 6px;">
                <div
                  class="progress-bar"
                  :class="performance.memory_usage > 80 ? 'bg-danger' : performance.memory_usage > 60 ? 'bg-warning' : 'bg-success'"
                  :style="{ width: performance.memory_usage + '%' }"
                ></div>
              </div>
            </div>

            <div class="metric-item mb-3">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="text-muted">Espacio en Disco</span>
                <span class="fw-semibold">{{ performance.disk_usage }}%</span>
              </div>
              <div class="progress" style="height: 6px;">
                <div
                  class="progress-bar"
                  :class="performance.disk_usage > 80 ? 'bg-danger' : performance.disk_usage > 60 ? 'bg-warning' : 'bg-success'"
                  :style="{ width: performance.disk_usage + '%' }"
                ></div>
              </div>
            </div>

            <div class="metric-item">
              <div class="d-flex justify-content-between align-items-center">
                <span class="text-muted">Tiempo de Respuesta</span>
                <span class="fw-semibold">{{ performance.response_time }}ms</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Alerts -->
    <div class="card border-0 shadow-sm" v-if="recentAlerts.length > 0">
      <div class="card-header bg-white">
        <h5 class="card-title mb-0">
          <i class="fas fa-bell me-2"></i>
          Alertas Recientes
        </h5>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>Tipo</th>
                <th>Mensaje</th>
                <th>Severidad</th>
                <th>Fecha</th>
                <th>Estado</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="alert in recentAlerts" :key="alert.id">
                <td>
                  <i :class="getAlertIcon(alert.type)" class="me-2"></i>
                  {{ getAlertTypeText(alert.type) }}
                </td>
                <td>{{ alert.message }}</td>
                <td>
                  <span class="badge" :class="getSeverityBadgeClass(alert.severity)">
                    {{ alert.severity }}
                  </span>
                </td>
                <td>{{ formatDate(alert.created_at, 'DD/MM/YYYY HH:mm') }}</td>
                <td>
                  <span class="badge" :class="alert.resolved ? 'bg-success' : 'bg-warning'">
                    {{ alert.resolved ? 'Resuelto' : 'Pendiente' }}
                  </span>
                </td>
                <td>
                  <button
                    v-if="!alert.resolved"
                    class="btn btn-outline-success btn-sm"
                    @click="resolveAlert(alert)"
                  >
                    <i class="fas fa-check"></i>
                  </button>
                  <button
                    class="btn btn-outline-info btn-sm ms-1"
                    @click="viewAlertDetails(alert)"
                  >
                    <i class="fas fa-eye"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Link } from '@inertiajs/vue3'

export default {
  name: 'Supervision',
  components: {
    Link
  },
  props: {
    systemStatus: {
      type: Object,
      default: () => ({
        overall: 'healthy',
        last_check: new Date().toISOString()
      })
    },
    activeUsers: {
      type: Object,
      default: () => ({
        count: 12,
        increase: 8
      })
    },
    emailStats: {
      type: Object,
      default: () => ({
        processed_today: 45,
        pending: 3
      })
    },
    urgentCases: {
      type: Object,
      default: () => ({
        count: 2
      })
    },
    activities: {
      type: Array,
      default: () => []
    },
    healthChecks: {
      type: Array,
      default: () => [
        { name: 'Base de Datos', description: 'Conectividad MySQL', status: 'healthy', response_time: 45 },
        { name: 'Cache Redis', description: 'Sistema de cache', status: 'healthy', response_time: 12 },
        { name: 'Gmail API', description: 'Integración email', status: 'healthy', response_time: 234 },
        { name: 'Gemini AI', description: 'Servicio de IA', status: 'healthy', response_time: 567 },
        { name: 'Almacenamiento', description: 'Sistema de archivos', status: 'healthy', response_time: 23 }
      ]
    },
    performance: {
      type: Object,
      default: () => ({
        cpu_usage: 35,
        memory_usage: 62,
        disk_usage: 45,
        response_time: 156
      })
    },
    recentAlerts: {
      type: Array,
      default: () => []
    }
  },
  data() {
    return {
      loading: false,
      autoRefresh: true,
      activityFilter: '',
      refreshInterval: null,
      realTimeActivities: []
    }
  },
  computed: {
    filteredActivities() {
      const allActivities = [...this.activities, ...this.realTimeActivities]

      if (!this.activityFilter) {
        return allActivities.slice(0, 50) // Limit to 50 most recent
      }

      return allActivities
        .filter(activity => activity.type === this.activityFilter)
        .slice(0, 50)
    }
  },
  mounted() {
    this.startAutoRefresh()
    this.simulateRealTimeActivity()
  },
  beforeUnmount() {
    this.stopAutoRefresh()
  },
  methods: {
    refreshData() {
      this.loading = true
      this.$inertia.reload({
        only: ['systemStatus', 'activeUsers', 'emailStats', 'urgentCases', 'activities', 'healthChecks', 'performance', 'recentAlerts'],
        onFinish: () => {
          this.loading = false
          this.$showToast('Datos actualizados', 'success')
        }
      })
    },

    startAutoRefresh() {
      if (this.autoRefresh) {
        this.refreshInterval = setInterval(() => {
          this.refreshData()
        }, 30000) // Refresh every 30 seconds
      }
    },

    stopAutoRefresh() {
      if (this.refreshInterval) {
        clearInterval(this.refreshInterval)
        this.refreshInterval = null
      }
    },

    simulateRealTimeActivity() {
      // Simulate real-time activity for demo purposes
      setInterval(() => {
        if (Math.random() > 0.7) { // 30% chance every 5 seconds
          const activities = [
            {
              id: Date.now(),
              type: 'login',
              title: 'Nuevo inicio de sesión',
              description: 'Dr. María González ha iniciado sesión',
              user_name: 'Dr. María González',
              created_at: new Date().toISOString()
            },
            {
              id: Date.now() + 1,
              type: 'case_created',
              title: 'Nuevo caso médico',
              description: 'Caso de cardiología recibido por email',
              user_name: 'Sistema',
              created_at: new Date().toISOString()
            },
            {
              id: Date.now() + 2,
              type: 'email_processed',
              title: 'Email procesado',
              description: 'Solicitud médica analizada por IA',
              user_name: 'Sistema IA',
              created_at: new Date().toISOString()
            }
          ]

          const randomActivity = activities[Math.floor(Math.random() * activities.length)]
          this.realTimeActivities.unshift(randomActivity)

          // Keep only last 20 real-time activities
          if (this.realTimeActivities.length > 20) {
            this.realTimeActivities = this.realTimeActivities.slice(0, 20)
          }
        }
      }, 5000)
    },

    clearActivityFeed() {
      this.realTimeActivities = []
      this.$showToast('Feed de actividad limpiado', 'info')
    },

    runHealthCheck() {
      this.loading = true
      this.$inertia.post(route('admin.system.health-check'), {}, {
        onSuccess: () => {
          this.$showToast('Verificación de salud completada', 'success')
        },
        onError: () => {
          this.$showToast('Error en la verificación de salud', 'error')
        },
        onFinish: () => {
          this.loading = false
        }
      })
    },

    resolveAlert(alert) {
      this.$inertia.patch(route('admin.alerts.resolve', alert.id), {}, {
        onSuccess: () => {
          this.$showToast('Alerta marcada como resuelta', 'success')
        }
      })
    },

    viewAlertDetails(alert) {
      this.$inertia.visit(route('admin.alerts.show', alert.id))
    },

    getActivityIcon(type) {
      const icons = {
        'login': 'fas fa-sign-in-alt text-success',
        'logout': 'fas fa-sign-out-alt text-muted',
        'case_created': 'fas fa-file-medical text-info',
        'case_evaluated': 'fas fa-user-md text-primary',
        'email_processed': 'fas fa-envelope text-warning',
        'system_alert': 'fas fa-exclamation-triangle text-danger',
        'urgent_case': 'fas fa-exclamation-circle text-danger',
        'backup_created': 'fas fa-save text-success',
        'user_created': 'fas fa-user-plus text-info'
      }
      return icons[type] || 'fas fa-info-circle text-muted'
    },

    getActivityBadgeClass(type) {
      const classes = {
        'login': 'bg-success',
        'logout': 'bg-secondary',
        'case_created': 'bg-info',
        'case_evaluated': 'bg-primary',
        'email_processed': 'bg-warning',
        'system_alert': 'bg-danger',
        'urgent_case': 'bg-danger',
        'backup_created': 'bg-success',
        'user_created': 'bg-info'
      }
      return classes[type] || 'bg-secondary'
    },

    getActivityTypeText(type) {
      const texts = {
        'login': 'Acceso',
        'logout': 'Salida',
        'case_created': 'Caso Nuevo',
        'case_evaluated': 'Evaluación',
        'email_processed': 'Email',
        'system_alert': 'Alerta',
        'urgent_case': 'Urgente',
        'backup_created': 'Backup',
        'user_created': 'Usuario'
      }
      return texts[type] || 'Actividad'
    },

    getAlertIcon(type) {
      const icons = {
        'system_error': 'fas fa-exclamation-triangle',
        'high_cpu': 'fas fa-microchip',
        'low_disk': 'fas fa-hdd',
        'failed_backup': 'fas fa-save',
        'security_breach': 'fas fa-shield-alt',
        'api_error': 'fas fa-plug'
      }
      return icons[type] || 'fas fa-bell'
    },

    getAlertTypeText(type) {
      const texts = {
        'system_error': 'Error del Sistema',
        'high_cpu': 'Alto Uso de CPU',
        'low_disk': 'Poco Espacio en Disco',
        'failed_backup': 'Fallo en Backup',
        'security_breach': 'Brecha de Seguridad',
        'api_error': 'Error de API'
      }
      return texts[type] || 'Alerta'
    },

    getSeverityBadgeClass(severity) {
      const classes = {
        'critical': 'bg-danger',
        'high': 'bg-warning',
        'medium': 'bg-info',
        'low': 'bg-secondary'
      }
      return classes[severity] || 'bg-secondary'
    },

    formatDate(date, format = 'DD/MM/YYYY HH:mm') {
      if (!date) return ''
      const d = new Date(date)
      return d.toLocaleString('es-ES')
    },

    timeAgo(date) {
      if (!date) return ''
      const now = new Date()
      const past = new Date(date)
      const diffInMinutes = Math.floor((now - past) / (1000 * 60))

      if (diffInMinutes < 1) return 'Ahora'
      if (diffInMinutes < 60) return `${diffInMinutes}m`
      if (diffInMinutes < 1440) return `${Math.floor(diffInMinutes / 60)}h`
      return `${Math.floor(diffInMinutes / 1440)}d`
    }
  },

  watch: {
    autoRefresh(newValue) {
      if (newValue) {
        this.startAutoRefresh()
      } else {
        this.stopAutoRefresh()
      }
    }
  }
}
</script>

<style scoped>
.supervision-container {
  padding: 1rem;
}

.status-icon {
  opacity: 0.8;
}

.activity-feed {
  border-radius: 0.375rem;
}

.activity-item {
  transition: background-color 0.2s ease;
}

.activity-item:hover {
  background-color: #f8f9fa;
}

.activity-item.activity-urgent {
  border-left: 4px solid #dc3545;
  background-color: rgba(220, 53, 69, 0.05);
}

.activity-icon {
  width: 40px;
  height: 40px;
  background: #f8f9fa;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
}

.health-check-item {
  padding: 0.75rem;
  background: #f8f9fa;
  border-radius: 0.375rem;
  border-left: 4px solid #28a745;
}

.metric-item .progress {
  border-radius: 3px;
}

.card {
  transition: box-shadow 0.15s ease-in-out;
}

.card:hover {
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

@media (max-width: 768px) {
  .supervision-container {
    padding: 0.5rem;
  }

  .activity-item {
    padding: 1rem !important;
  }

  .activity-icon {
    width: 32px;
    height: 32px;
    font-size: 1rem;
  }
}
</style>
