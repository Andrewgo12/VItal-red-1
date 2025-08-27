<template>
  <div class="bandeja-casos-container">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="h3 mb-0 text-primary">
          <i class="fas fa-inbox me-2"></i>
          Bandeja de Casos Médicos
        </h1>
        <p class="text-muted mb-0">
          Gestión y evaluación de solicitudes médicas
        </p>
      </div>
      <div class="d-flex gap-2">
        <button
          class="btn btn-outline-primary btn-sm"
          @click="refreshCases"
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
            <i class="fas fa-filter me-1"></i>
            Filtros
          </button>
          <ul class="dropdown-menu">
            <li><h6 class="dropdown-header">Filtrar por estado</h6></li>
            <li><a class="dropdown-item" href="#" @click="setFilter('estado', '')">Todos</a></li>
            <li><a class="dropdown-item" href="#" @click="setFilter('estado', 'pendiente_evaluacion')">Pendientes</a></li>
            <li><a class="dropdown-item" href="#" @click="setFilter('estado', 'en_evaluacion')">En Evaluación</a></li>
            <li><a class="dropdown-item" href="#" @click="setFilter('estado', 'aceptada')">Aceptados</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><h6 class="dropdown-header">Filtrar por prioridad</h6></li>
            <li><a class="dropdown-item" href="#" @click="setFilter('prioridad', '')">Todas</a></li>
            <li><a class="dropdown-item" href="#" @click="setFilter('prioridad', 'Alta')">Alta</a></li>
            <li><a class="dropdown-item" href="#" @click="setFilter('prioridad', 'Media')">Media</a></li>
            <li><a class="dropdown-item" href="#" @click="setFilter('prioridad', 'Baja')">Baja</a></li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Quick Stats -->
    <div class="row mb-4">
      <div class="col-md-3 mb-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body text-center">
            <div class="stat-icon mb-2 text-warning">
              <i class="fas fa-clock fa-2x"></i>
            </div>
            <h5 class="card-title">Pendientes</h5>
            <h3 class="text-warning mb-0">{{ stats.pendientes }}</h3>
          </div>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body text-center">
            <div class="stat-icon mb-2 text-info">
              <i class="fas fa-user-md fa-2x"></i>
            </div>
            <h5 class="card-title">En Evaluación</h5>
            <h3 class="text-info mb-0">{{ stats.en_evaluacion }}</h3>
          </div>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body text-center">
            <div class="stat-icon mb-2 text-danger">
              <i class="fas fa-exclamation-triangle fa-2x"></i>
            </div>
            <h5 class="card-title">Urgentes</h5>
            <h3 class="text-danger mb-0">{{ stats.urgentes }}</h3>
          </div>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body text-center">
            <div class="stat-icon mb-2 text-success">
              <i class="fas fa-check-circle fa-2x"></i>
            </div>
            <h5 class="card-title">Completados Hoy</h5>
            <h3 class="text-success mb-0">{{ stats.completados_hoy }}</h3>
          </div>
        </div>
      </div>
    </div>

    <!-- Search and Filters -->
    <div class="card mb-4">
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Buscar casos</label>
            <div class="input-group">
              <span class="input-group-text">
                <i class="fas fa-search"></i>
              </span>
              <input
                v-model="searchQuery"
                type="text"
                class="form-control"
                placeholder="Buscar por paciente, institución, diagnóstico..."
                @input="debouncedSearch"
              >
            </div>
          </div>
          <div class="col-md-2">
            <label class="form-label">Especialidad</label>
            <select v-model="filters.especialidad" class="form-select" @change="applyFilters">
              <option value="">Todas</option>
              <option v-for="especialidad in especialidades" :key="especialidad" :value="especialidad">
                {{ especialidad }}
              </option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Fecha</label>
            <select v-model="filters.fecha" class="form-select" @change="applyFilters">
              <option value="">Todas</option>
              <option value="hoy">Hoy</option>
              <option value="ayer">Ayer</option>
              <option value="semana">Esta semana</option>
              <option value="mes">Este mes</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">&nbsp;</label>
            <button
              class="btn btn-outline-secondary w-100"
              @click="clearFilters"
            >
              <i class="fas fa-times me-1"></i>
              Limpiar
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Cases Table -->
    <div class="card">
      <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0">
            <i class="fas fa-list me-2"></i>
            Casos Médicos ({{ filteredCases.length }})
          </h5>
          <div class="d-flex gap-2">
            <select v-model="sortBy" class="form-select form-select-sm" style="width: auto;">
              <option value="fecha_recepcion_email">Fecha de recepción</option>
              <option value="prioridad_ia">Prioridad IA</option>
              <option value="especialidad_sugerida">Especialidad</option>
              <option value="estado">Estado</option>
            </select>
            <button
              class="btn btn-outline-secondary btn-sm"
              @click="toggleSortOrder"
            >
              <i :class="sortOrder === 'asc' ? 'fas fa-sort-amount-up' : 'fas fa-sort-amount-down'"></i>
            </button>
          </div>
        </div>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>Paciente</th>
                <th>Institución</th>
                <th>Especialidad</th>
                <th>Prioridad IA</th>
                <th>Estado</th>
                <th>Fecha</th>
                <th>Tiempo Transcurrido</th>
                <th class="text-center">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="loading">
                <td colspan="8" class="text-center py-4">
                  <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                  </div>
                  <p class="mt-2 mb-0 text-muted">Cargando casos médicos...</p>
                </td>
              </tr>
              <tr v-else-if="paginatedCases.length === 0">
                <td colspan="8" class="text-center py-4">
                  <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                  <p class="mb-0 text-muted">No se encontraron casos médicos</p>
                </td>
              </tr>
              <tr
                v-else
                v-for="caso in paginatedCases"
                :key="caso.id"
                class="case-row"
                :class="{ 'table-danger': caso.prioridad_ia === 'Alta', 'table-warning': caso.prioridad_ia === 'Media' }"
              >
                <td>
                  <div>
                    <div class="fw-semibold">{{ caso.nombre_paciente }}</div>
                    <small class="text-muted">{{ caso.edad_paciente }} años - {{ caso.genero_paciente }}</small>
                  </div>
                </td>
                <td>
                  <div>
                    <div class="fw-semibold">{{ caso.institucion_origen }}</div>
                    <small class="text-muted">{{ caso.medico_solicitante }}</small>
                  </div>
                </td>
                <td>
                  <span class="badge bg-primary">
                    {{ caso.especialidad_sugerida }}
                  </span>
                </td>
                <td>
                  <div class="d-flex align-items-center">
                    <span class="badge" :class="getPriorityBadgeClass(caso.prioridad_ia)">
                      <span class="priority-indicator" :class="getPriorityIndicatorClass(caso.prioridad_ia)"></span>
                      {{ caso.prioridad_ia }}
                    </span>
                    <small class="text-muted ms-2">{{ caso.puntuacion_urgencia }}/100</small>
                  </div>
                </td>
                <td>
                  <span class="badge" :class="getStatusBadgeClass(caso.estado)">
                    {{ getStatusText(caso.estado) }}
                  </span>
                </td>
                <td>
                  <div>
                    <div>{{ formatDate(caso.fecha_recepcion_email, 'DD/MM/YYYY') }}</div>
                    <small class="text-muted">{{ formatDate(caso.fecha_recepcion_email, 'HH:mm') }}</small>
                  </div>
                </td>
                <td>
                  <span class="badge" :class="getTimeElapsedClass(caso.fecha_recepcion_email)">
                    {{ timeAgo(caso.fecha_recepcion_email) }}
                  </span>
                </td>
                <td>
                  <div class="btn-group btn-group-sm">
                    <button
                      class="btn btn-outline-primary"
                      @click="viewCase(caso)"
                      :title="`Ver detalles del caso`"
                    >
                      <i class="fas fa-eye"></i>
                    </button>
                    <button
                      v-if="caso.estado === 'pendiente_evaluacion'"
                      class="btn btn-outline-success"
                      @click="evaluateCase(caso)"
                      :title="`Evaluar caso`"
                    >
                      <i class="fas fa-user-md"></i>
                    </button>
                    <button
                      v-if="caso.estado === 'en_evaluacion' && caso.medico_evaluador_id === $page.props.auth.user.id"
                      class="btn btn-outline-warning"
                      @click="continueEvaluation(caso)"
                      :title="`Continuar evaluación`"
                    >
                      <i class="fas fa-edit"></i>
                    </button>
                    <button
                      class="btn btn-outline-info"
                      @click="downloadCase(caso)"
                      :title="`Descargar caso`"
                    >
                      <i class="fas fa-download"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Pagination -->
      <div class="card-footer" v-if="filteredCases.length > itemsPerPage">
        <nav>
          <ul class="pagination pagination-sm justify-content-center mb-0">
            <li class="page-item" :class="{ disabled: currentPage === 1 }">
              <button class="page-link" @click="currentPage = 1" :disabled="currentPage === 1">
                <i class="fas fa-angle-double-left"></i>
              </button>
            </li>
            <li class="page-item" :class="{ disabled: currentPage === 1 }">
              <button class="page-link" @click="currentPage--" :disabled="currentPage === 1">
                <i class="fas fa-angle-left"></i>
              </button>
            </li>
            <li
              v-for="page in visiblePages"
              :key="page"
              class="page-item"
              :class="{ active: page === currentPage }"
            >
              <button class="page-link" @click="currentPage = page">
                {{ page }}
              </button>
            </li>
            <li class="page-item" :class="{ disabled: currentPage === totalPages }">
              <button class="page-link" @click="currentPage++" :disabled="currentPage === totalPages">
                <i class="fas fa-angle-right"></i>
              </button>
            </li>
            <li class="page-item" :class="{ disabled: currentPage === totalPages }">
              <button class="page-link" @click="currentPage = totalPages" :disabled="currentPage === totalPages">
                <i class="fas fa-angle-double-right"></i>
              </button>
            </li>
          </ul>
        </nav>
      </div>
    </div>
  </div>
</template>

<script>
import { Link } from '@inertiajs/vue3'

export default {
  name: 'BandejaCasos',
  components: {
    Link
  },
  props: {
    casos: {
      type: Array,
      default: () => []
    },
    stats: {
      type: Object,
      default: () => ({
        pendientes: 8,
        en_evaluacion: 3,
        urgentes: 2,
        completados_hoy: 5
      })
    },
    especialidades: {
      type: Array,
      default: () => [
        'Cardiología', 'Neurología', 'Pediatría', 'Ginecología',
        'Medicina Interna', 'Cirugía General', 'Ortopedia', 'Dermatología'
      ]
    }
  },
  data() {
    return {
      loading: false,
      searchQuery: '',
      currentPage: 1,
      itemsPerPage: 15,
      sortBy: 'fecha_recepcion_email',
      sortOrder: 'desc',
      filters: {
        estado: '',
        prioridad: '',
        especialidad: '',
        fecha: ''
      },
      searchTimeout: null
    }
  },
  computed: {
    filteredCases() {
      let filtered = [...this.casos]

      // Search filter
      if (this.searchQuery) {
        const search = this.searchQuery.toLowerCase()
        filtered = filtered.filter(caso =>
          caso.nombre_paciente?.toLowerCase().includes(search) ||
          caso.institucion_origen?.toLowerCase().includes(search) ||
          caso.medico_solicitante?.toLowerCase().includes(search) ||
          caso.diagnostico_presuntivo?.toLowerCase().includes(search) ||
          caso.especialidad_sugerida?.toLowerCase().includes(search)
        )
      }

      // Estado filter
      if (this.filters.estado) {
        filtered = filtered.filter(caso => caso.estado === this.filters.estado)
      }

      // Prioridad filter
      if (this.filters.prioridad) {
        filtered = filtered.filter(caso => caso.prioridad_ia === this.filters.prioridad)
      }

      // Especialidad filter
      if (this.filters.especialidad) {
        filtered = filtered.filter(caso => caso.especialidad_sugerida === this.filters.especialidad)
      }

      // Fecha filter
      if (this.filters.fecha) {
        const now = new Date()
        const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())

        filtered = filtered.filter(caso => {
          const caseDate = new Date(caso.fecha_recepcion_email)

          switch (this.filters.fecha) {
            case 'hoy':
              return caseDate >= today
            case 'ayer':
              const yesterday = new Date(today)
              yesterday.setDate(yesterday.getDate() - 1)
              return caseDate >= yesterday && caseDate < today
            case 'semana':
              const weekAgo = new Date(today)
              weekAgo.setDate(weekAgo.getDate() - 7)
              return caseDate >= weekAgo
            case 'mes':
              const monthAgo = new Date(today)
              monthAgo.setMonth(monthAgo.getMonth() - 1)
              return caseDate >= monthAgo
            default:
              return true
          }
        })
      }

      // Sort
      filtered.sort((a, b) => {
        let aValue = a[this.sortBy]
        let bValue = b[this.sortBy]

        if (this.sortBy === 'fecha_recepcion_email') {
          aValue = new Date(aValue)
          bValue = new Date(bValue)
        } else if (this.sortBy === 'prioridad_ia') {
          const priorityOrder = { 'Alta': 3, 'Media': 2, 'Baja': 1 }
          aValue = priorityOrder[aValue] || 0
          bValue = priorityOrder[bValue] || 0
        }

        if (this.sortOrder === 'asc') {
          return aValue > bValue ? 1 : -1
        } else {
          return aValue < bValue ? 1 : -1
        }
      })

      return filtered
    },

    paginatedCases() {
      const start = (this.currentPage - 1) * this.itemsPerPage
      const end = start + this.itemsPerPage
      return this.filteredCases.slice(start, end)
    },

    totalPages() {
      return Math.ceil(this.filteredCases.length / this.itemsPerPage)
    },

    visiblePages() {
      const pages = []
      const start = Math.max(1, this.currentPage - 2)
      const end = Math.min(this.totalPages, this.currentPage + 2)

      for (let i = start; i <= end; i++) {
        pages.push(i)
      }

      return pages
    }
  },
  methods: {
    refreshCases() {
      this.loading = true
      this.$inertia.reload({
        only: ['casos', 'stats'],
        onFinish: () => {
          this.loading = false
          this.$showToast('Casos actualizados', 'success')
        }
      })
    },

    debouncedSearch() {
      clearTimeout(this.searchTimeout)
      this.searchTimeout = setTimeout(() => {
        this.currentPage = 1
      }, 300)
    },

    setFilter(type, value) {
      this.filters[type] = value
      this.currentPage = 1
      this.applyFilters()
    },

    applyFilters() {
      this.currentPage = 1
    },

    clearFilters() {
      this.searchQuery = ''
      this.filters = {
        estado: '',
        prioridad: '',
        especialidad: '',
        fecha: ''
      }
      this.currentPage = 1
    },

    toggleSortOrder() {
      this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc'
    },

    viewCase(caso) {
      this.$inertia.visit(route('medico.evaluar-solicitud', caso.id))
    },

    evaluateCase(caso) {
      this.$showConfirm(
        'Iniciar Evaluación',
        `¿Desea iniciar la evaluación del caso de ${caso.nombre_paciente}?`
      ).then((result) => {
        if (result.isConfirmed) {
          this.$inertia.post(route('medico.evaluar-solicitud', caso.id), {
            action: 'start_evaluation'
          }, {
            onSuccess: () => {
              this.$showToast('Evaluación iniciada', 'success')
            }
          })
        }
      })
    },

    continueEvaluation(caso) {
      this.$inertia.visit(route('medico.evaluar-solicitud', caso.id))
    },

    downloadCase(caso) {
      window.open(route('medico.descargar-historia', caso.id), '_blank')
    },

    getPriorityBadgeClass(priority) {
      const classes = {
        'Alta': 'badge-urgent',
        'Media': 'badge-medium',
        'Baja': 'badge-low'
      }
      return classes[priority] || 'bg-secondary'
    },

    getPriorityIndicatorClass(priority) {
      const classes = {
        'Alta': 'priority-high',
        'Media': 'priority-medium',
        'Baja': 'priority-low'
      }
      return classes[priority] || ''
    },

    getStatusBadgeClass(status) {
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

    getTimeElapsedClass(date) {
      const now = new Date()
      const caseDate = new Date(date)
      const diffInHours = Math.floor((now - caseDate) / (1000 * 60 * 60))

      if (diffInHours < 2) return 'bg-success'
      if (diffInHours < 8) return 'bg-warning'
      return 'bg-danger'
    },

    formatDate(date, format = 'DD/MM/YYYY') {
      if (!date) return ''
      const d = new Date(date)

      if (format === 'DD/MM/YYYY') {
        return d.toLocaleDateString('es-ES')
      } else if (format === 'HH:mm') {
        return d.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' })
      }

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
    searchQuery() {
      this.debouncedSearch()
    }
  }
}
</script>

<style scoped>
.bandeja-casos-container {
  padding: 1rem;
}

.stat-icon {
  opacity: 0.8;
}

.case-row {
  transition: background-color 0.2s ease;
}

.case-row:hover {
  background-color: rgba(0, 123, 255, 0.05) !important;
}

.priority-indicator {
  display: inline-block;
  width: 8px;
  height: 8px;
  border-radius: 50%;
  margin-right: 0.25rem;
}

.priority-high {
  background-color: #dc3545;
  animation: pulse 2s infinite;
}

.priority-medium {
  background-color: #ffc107;
}

.priority-low {
  background-color: #28a745;
}

.badge-urgent {
  background: linear-gradient(135deg, #dc3545, #c82333);
  color: white;
}

.badge-medium {
  background: linear-gradient(135deg, #ffc107, #e0a800);
  color: #212529;
}

.badge-low {
  background: linear-gradient(135deg, #28a745, #1e7e34);
  color: white;
}

@keyframes pulse {
  0% {
    box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7);
  }
  70% {
    box-shadow: 0 0 0 10px rgba(220, 53, 69, 0);
  }
  100% {
    box-shadow: 0 0 0 0 rgba(220, 53, 69, 0);
  }
}

.table th {
  border-top: none;
  font-weight: 600;
  color: #495057;
}

.btn-group-sm .btn {
  padding: 0.25rem 0.5rem;
}

@media (max-width: 768px) {
  .bandeja-casos-container {
    padding: 0.5rem;
  }

  .table-responsive {
    font-size: 0.875rem;
  }

  .btn-group-sm .btn {
    padding: 0.125rem 0.25rem;
  }
}
</style>
