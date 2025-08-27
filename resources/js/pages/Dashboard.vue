<template>
  <div class="dashboard-container">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="h3 mb-0 text-primary">
          <i class="fas fa-tachometer-alt me-2"></i>
          Dashboard Médico
        </h1>
        <p class="text-muted mb-0">
          Bienvenido, <strong>{{ $page.props.auth.user.name }}</strong> - {{ $page.props.auth.user.role }}
        </p>
        <small class="text-success">
          <i class="fas fa-circle me-1"></i>
          Sistema operativo | Última actualización: {{ formatDate(new Date(), 'DD/MM/YYYY HH:mm') }}
        </small>
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
        <div class="dropdown">
          <button
            class="btn btn-outline-secondary btn-sm dropdown-toggle"
            type="button"
            data-bs-toggle="dropdown"
          >
            <i class="fas fa-calendar me-1"></i>
            {{ selectedPeriod.label }}
          </button>
          <ul class="dropdown-menu">
            <li v-for="period in periods" :key="period.value">
              <a
                class="dropdown-item"
                href="#"
                @click.prevent="changePeriod(period)"
                :class="{ active: selectedPeriod.value === period.value }"
              >
                {{ period.label }}
              </a>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Alert for urgent cases -->
    <div
      v-if="metrics.summary?.urgent_cases > 0"
      class="alert alert-danger d-flex align-items-center mb-4"
    >
      <i class="fas fa-exclamation-triangle me-2"></i>
      <div class="flex-grow-1">
        <strong>¡Atención!</strong>
        Hay {{ metrics.summary.urgent_cases }} caso{{ metrics.summary.urgent_cases > 1 ? 's' : '' }} urgente{{ metrics.summary.urgent_cases > 1 ? 's' : '' }} pendiente{{ metrics.summary.urgent_cases > 1 ? 's' : '' }} de evaluación.
      </div>
      <Link
        v-if="can('view-medical-cases')"
        :href="route('medico.casos-urgentes')"
        class="btn btn-sm btn-outline-light"
      >
        Ver Casos
      </Link>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
      <div class="col-md-3 mb-3">
        <div class="card h-100">
          <div class="card-body text-center">
            <div class="display-6 text-primary mb-2">
              <i class="fas fa-file-medical"></i>
            </div>
            <h5 class="card-title">Total de Casos</h5>
            <h2 class="text-primary mb-0">{{ formatNumber(metrics.summary?.total_cases || 0) }}</h2>
            <small class="text-muted">
              +{{ metrics.summary?.cases_today || 0 }} hoy
            </small>
          </div>
        </div>
      </div>

      <div class="col-md-3 mb-3">
        <div class="card h-100">
          <div class="card-body text-center">
            <div class="display-6 text-warning mb-2">
              <i class="fas fa-clock"></i>
            </div>
            <h5 class="card-title">Pendientes</h5>
            <h2 class="text-warning mb-0">{{ formatNumber(metrics.summary?.pending_cases || 0) }}</h2>
            <small class="text-muted">
              {{ metrics.summary?.urgent_cases || 0 }} urgentes
            </small>
          </div>
        </div>
      </div>

      <div class="col-md-3 mb-3">
        <div class="card h-100">
          <div class="card-body text-center">
            <div class="display-6 text-success mb-2">
              <i class="fas fa-check-circle"></i>
            </div>
            <h5 class="card-title">Tasa de Aceptación</h5>
            <h2 class="text-success mb-0">{{ metrics.summary?.acceptance_rate || 0 }}%</h2>
            <small class="text-muted">
              {{ metrics.summary?.evaluations_today || 0 }} evaluaciones hoy
            </small>
          </div>
        </div>
      </div>

      <div class="col-md-3 mb-3">
        <div class="card h-100">
          <div class="card-body text-center">
            <div class="display-6 text-info mb-2">
              <i class="fas fa-stopwatch"></i>
            </div>
            <h5 class="card-title">Tiempo Promedio</h5>
            <h2 class="text-info mb-0">{{ metrics.summary?.avg_response_time || 0 }}h</h2>
            <small class="text-muted">
              tiempo de respuesta
            </small>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
      <!-- Cases by Priority Chart -->
      <div class="col-md-6 mb-3">
        <div class="card h-100">
          <div class="card-header">
            <h5 class="card-title mb-0">
              <i class="fas fa-chart-pie me-2"></i>
              Casos por Prioridad
            </h5>
          </div>
          <div class="card-body">
            <canvas ref="priorityChart" height="300"></canvas>
          </div>
        </div>
      </div>

      <!-- Cases Trend Chart -->
      <div class="col-md-6 mb-3">
        <div class="card h-100">
          <div class="card-header">
            <h5 class="card-title mb-0">
              <i class="fas fa-chart-line me-2"></i>
              Tendencia de Casos (7 días)
            </h5>
          </div>
          <div class="card-body">
            <canvas ref="trendChart" height="300"></canvas>
          </div>
        </div>
      </div>
    </div>

    <!-- Content Row -->
    <div class="row">
      <!-- Recent Cases -->
      <div class="col-md-8 mb-3">
        <div class="card h-100">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
              <i class="fas fa-history me-2"></i>
              Casos Recientes
            </h5>
            <Link
              v-if="can('view-medical-cases')"
              :href="route('medico.bandeja-casos')"
              class="btn btn-sm btn-outline-primary"
            >
              Ver Todos
            </Link>
          </div>
          <div class="card-body p-0">
            <div v-if="metrics.recent_cases?.length === 0" class="p-4 text-center text-muted">
              <i class="fas fa-inbox fa-2x mb-2"></i>
              <p class="mb-0">No hay casos recientes</p>
            </div>
            <div v-else class="table-responsive">
              <table class="table table-hover mb-0">
                <thead>
                  <tr>
                    <th>Paciente</th>
                    <th>Especialidad</th>
                    <th>Prioridad</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="case_item in metrics.recent_cases?.slice(0, 10)"
                    :key="case_item.id"
                    :class="getCaseRowClass(case_item.prioridad_ia)"
                  >
                    <td>
                      <div class="fw-semibold">{{ case_item.paciente_nombre }}</div>
                    </td>
                    <td>
                      <span class="badge bg-light text-dark">
                        {{ case_item.especialidad_solicitada }}
                      </span>
                    </td>
                    <td>
                      <span
                        class="badge"
                        :class="getPriorityClass(case_item.prioridad_ia)"
                      >
                        <span class="priority-indicator" :class="getPriorityIndicatorClass(case_item.prioridad_ia)"></span>
                        {{ case_item.prioridad_ia }}
                      </span>
                    </td>
                    <td>
                      <span
                        class="badge"
                        :class="getStatusClass(case_item.estado)"
                      >
                        {{ getStatusText(case_item.estado) }}
                      </span>
                    </td>
                    <td>
                      <small class="text-muted">
                        {{ formatDate(case_item.fecha_recepcion_email, 'DD/MM HH:mm') }}
                      </small>
                    </td>
                    <td>
                      <Link
                        v-if="can('view-medical-cases')"
                        :href="route('medico.evaluar-solicitud', case_item.id)"
                        class="btn btn-sm btn-outline-primary"
                      >
                        <i class="fas fa-eye"></i>
                      </Link>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Specialties Distribution -->
      <div class="col-md-4 mb-3">
        <div class="card h-100">
          <div class="card-header">
            <h5 class="card-title mb-0">
              <i class="fas fa-chart-bar me-2"></i>
              Por Especialidad
            </h5>
          </div>
          <div class="card-body">
            <div v-if="Object.keys(metrics.distributions?.by_specialty || {}).length === 0" class="text-center text-muted">
              <i class="fas fa-chart-bar fa-2x mb-2"></i>
              <p class="mb-0">Sin datos disponibles</p>
            </div>
            <div v-else>
              <div
                v-for="(count, specialty) in metrics.distributions?.by_specialty"
                :key="specialty"
                class="d-flex justify-content-between align-items-center mb-2"
              >
                <span class="text-truncate me-2">{{ specialty }}</span>
                <div class="d-flex align-items-center">
                  <div class="progress me-2" style="width: 60px; height: 8px;">
                    <div
                      class="progress-bar bg-primary"
                      :style="{ width: getPercentage(count, getTotalSpecialties()) + '%' }"
                    ></div>
                  </div>
                  <span class="badge bg-primary">{{ count }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Doctor Performance (Admin only) -->
    <div v-if="can('view-admin-panel') && metrics.doctor_metrics?.length > 0" class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h5 class="card-title mb-0">
              <i class="fas fa-user-md me-2"></i>
              Rendimiento de Médicos
            </h5>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>Médico</th>
                    <th>Departamento</th>
                    <th>Casos Evaluados</th>
                    <th>Tiempo Promedio (h)</th>
                    <th>Rendimiento</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="doctor in metrics.doctor_metrics" :key="doctor.id">
                    <td>
                      <div class="fw-semibold">{{ doctor.name }}</div>
                    </td>
                    <td>{{ doctor.department }}</td>
                    <td>
                      <span class="badge bg-primary">{{ doctor.cases_evaluated }}</span>
                    </td>
                    <td>{{ doctor.avg_response_time }}h</td>
                    <td>
                      <div class="progress" style="height: 8px;">
                        <div
                          class="progress-bar"
                          :class="getPerformanceClass(doctor.avg_response_time)"
                          :style="{ width: getPerformancePercentage(doctor.avg_response_time) + '%' }"
                        ></div>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Link } from '@inertiajs/vue3'
// Chart.js placeholder - in production you would import Chart.js
window.Chart = window.Chart || class {
  constructor(ctx, config) {
    this.ctx = ctx
    this.config = config
    console.log('Chart created:', config.type)
  }
  destroy() {
    console.log('Chart destroyed')
  }
}

export default {
  name: 'Dashboard',
  components: {
    Link
  },
  props: {
    metrics: {
      type: Object,
      default: () => ({})
    }
  },
  data() {
    return {
      loading: false,
      priorityChart: null,
      trendChart: null,
      selectedPeriod: { value: '30d', label: 'Últimos 30 días' },
      periods: [
        { value: '7d', label: 'Últimos 7 días' },
        { value: '30d', label: 'Últimos 30 días' },
        { value: '90d', label: 'Últimos 3 meses' },
        { value: '1y', label: 'Último año' }
      ]
    }
  },
  mounted() {
    this.initCharts()
    this.startAutoRefresh()
  },
  beforeUnmount() {
    this.stopAutoRefresh()
    if (this.priorityChart) this.priorityChart.destroy()
    if (this.trendChart) this.trendChart.destroy()
  },
  methods: {
    can(permission) {
      // Simple permission check - in production this would check user permissions
      const user = this.$page.props.auth?.user
      if (!user) return false

      const permissions = {
        'view-medical-cases': ['medico', 'administrador'],
        'view-admin-panel': ['administrador'],
        'manage-users': ['administrador'],
        'view-reports': ['medico', 'administrador']
      }

      return permissions[permission]?.includes(user.role) || false
    },

    initCharts() {
      this.$nextTick(() => {
        this.createPriorityChart()
        this.createTrendChart()
      })
    },

    createPriorityChart() {
      const ctx = this.$refs.priorityChart?.getContext('2d')
      if (!ctx) return

      const data = this.metrics.distributions?.by_priority || {}

      this.priorityChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels: Object.keys(data),
          datasets: [{
            data: Object.values(data),
            backgroundColor: [
              '#dc3545', // Alta - Rojo
              '#fd7e14', // Media - Naranja
              '#28a745'  // Baja - Verde
            ],
            borderWidth: 2,
            borderColor: '#fff'
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
      })
    },

    createTrendChart() {
      const ctx = this.$refs.trendChart?.getContext('2d')
      if (!ctx) return

      const trends = this.metrics.trends?.temporal || []

      this.trendChart = new Chart(ctx, {
        type: 'line',
        data: {
          labels: trends.map(t => t.label),
          datasets: [{
            label: 'Casos',
            data: trends.map(t => t.count),
            borderColor: '#2c5aa0',
            backgroundColor: 'rgba(44, 90, 160, 0.1)',
            tension: 0.4,
            fill: true
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              beginAtZero: true
            }
          },
          plugins: {
            legend: {
              display: false
            }
          }
        }
      })
    },

    getCaseRowClass(priority) {
      const classes = {
        'Alta': 'urgent-case',
        'Media': 'medium-case',
        'Baja': 'low-case'
      }
      return classes[priority] || ''
    },

    getPriorityIndicatorClass(priority) {
      const classes = {
        'Alta': 'priority-urgent',
        'Media': 'priority-medium',
        'Baja': 'priority-low'
      }
      return classes[priority] || ''
    },

    getPercentage(value, total) {
      return total > 0 ? Math.round((value / total) * 100) : 0
    },

    getTotalSpecialties() {
      const specialties = this.metrics.distributions?.by_specialty || {}
      return Object.values(specialties).reduce((sum, count) => sum + count, 0)
    },

    getPerformanceClass(responseTime) {
      if (responseTime <= 4) return 'bg-success'
      if (responseTime <= 8) return 'bg-warning'
      return 'bg-danger'
    },

    getPerformancePercentage(responseTime) {
      // Inverse percentage - lower response time = higher performance
      const maxTime = 24 // 24 hours max
      return Math.max(0, Math.min(100, 100 - (responseTime / maxTime) * 100))
    },

    refreshData() {
      this.loading = true
      this.$inertia.reload({
        only: ['metrics'],
        onFinish: () => {
          this.loading = false
          this.updateCharts()
        }
      })
    },

    changePeriod(period) {
      this.selectedPeriod = period
      this.loading = true
      this.$inertia.get(route('dashboard'),
        { period: period.value },
        {
          only: ['metrics'],
          preserveState: true,
          onFinish: () => {
            this.loading = false
            this.updateCharts()
          }
        }
      )
    },

    updateCharts() {
      this.$nextTick(() => {
        if (this.priorityChart) {
          this.priorityChart.destroy()
          this.createPriorityChart()
        }
        if (this.trendChart) {
          this.trendChart.destroy()
          this.createTrendChart()
        }
      })
    },

    startAutoRefresh() {
      this.autoRefreshInterval = setInterval(() => {
        this.refreshData()
      }, 300000) // 5 minutes
    },

    stopAutoRefresh() {
      if (this.autoRefreshInterval) {
        clearInterval(this.autoRefreshInterval)
      }
    },

    getPriorityClass(priority) {
      const classes = {
        'Alta': 'badge-urgent',
        'Media': 'badge-medium',
        'Baja': 'badge-low'
      }
      return classes[priority] || 'bg-secondary'
    },

    getStatusClass(status) {
      const classes = {
        'pendiente_evaluacion': 'bg-warning',
        'en_evaluacion': 'bg-info',
        'aceptada': 'bg-success',
        'rechazada': 'bg-danger',
        'derivada': 'bg-secondary',
        'completada': 'bg-primary'
      }
      return classes[status] || 'bg-secondary'
    },

    getStatusText(status) {
      const texts = {
        'pendiente_evaluacion': 'Pendiente',
        'en_evaluacion': 'En Evaluación',
        'aceptada': 'Aceptada',
        'rechazada': 'Rechazada',
        'derivada': 'Derivada',
        'completada': 'Completada'
      }
      return texts[status] || status
    },

    formatNumber(number) {
      return new Intl.NumberFormat('es-ES').format(number)
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
  }
}
</script>

<style scoped>
.card {
  transition: transform 0.2s ease-in-out;
}

.card:hover {
  transform: translateY(-2px);
}

.progress {
  background-color: #e9ecef;
}

.urgent-case {
  border-left: 4px solid var(--urgent-red);
}

.medium-case {
  border-left: 4px solid var(--medium-orange);
}

.low-case {
  border-left: 4px solid var(--low-green);
}

.table-responsive {
  border-radius: 0.5rem;
}

@media (max-width: 768px) {
  .display-6 {
    font-size: 2rem;
  }

  .card-body {
    padding: 1rem;
  }
}
</style>
