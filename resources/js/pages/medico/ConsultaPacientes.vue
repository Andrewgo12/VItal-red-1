<template>
  <div class="consulta-pacientes-container">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="h3 mb-0 text-primary">
          <i class="fas fa-users me-2"></i>
          Consulta de Pacientes
        </h1>
        <p class="text-muted mb-0">
          Búsqueda y consulta de historiales médicos
        </p>
      </div>
      <button
        class="btn btn-primary"
        @click="showAdvancedSearch = !showAdvancedSearch"
      >
        <i class="fas fa-search-plus me-1"></i>
        Búsqueda Avanzada
      </button>
    </div>

    <!-- Search Form -->
    <div class="card mb-4">
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Buscar paciente</label>
            <div class="input-group">
              <span class="input-group-text">
                <i class="fas fa-search"></i>
              </span>
              <input
                v-model="searchQuery"
                type="text"
                class="form-control"
                placeholder="Nombre, documento, email..."
                @input="debouncedSearch"
              >
            </div>
          </div>
          <div class="col-md-3">
            <label class="form-label">Tipo de documento</label>
            <select v-model="filters.tipoDocumento" class="form-select" @change="applyFilters">
              <option value="">Todos</option>
              <option value="CC">Cédula de Ciudadanía</option>
              <option value="TI">Tarjeta de Identidad</option>
              <option value="CE">Cédula de Extranjería</option>
              <option value="PP">Pasaporte</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Rango de edad</label>
            <select v-model="filters.rangoEdad" class="form-select" @change="applyFilters">
              <option value="">Todas las edades</option>
              <option value="0-17">Menores (0-17)</option>
              <option value="18-64">Adultos (18-64)</option>
              <option value="65+">Adultos mayores (65+)</option>
            </select>
          </div>
        </div>

        <!-- Advanced Search -->
        <div v-if="showAdvancedSearch" class="row g-3 mt-3 pt-3 border-top">
          <div class="col-md-4">
            <label class="form-label">Género</label>
            <select v-model="filters.genero" class="form-select" @change="applyFilters">
              <option value="">Todos</option>
              <option value="M">Masculino</option>
              <option value="F">Femenino</option>
              <option value="O">Otro</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Fecha de nacimiento (desde)</label>
            <input
              v-model="filters.fechaNacimientoDesde"
              type="date"
              class="form-control"
              @change="applyFilters"
            >
          </div>
          <div class="col-md-4">
            <label class="form-label">Fecha de nacimiento (hasta)</label>
            <input
              v-model="filters.fechaNacimientoHasta"
              type="date"
              class="form-control"
              @change="applyFilters"
            >
          </div>
        </div>
      </div>
    </div>

    <!-- Results -->
    <div class="card">
      <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0">
            <i class="fas fa-list me-2"></i>
            Resultados de Búsqueda ({{ filteredPacientes.length }})
          </h5>
          <button
            v-if="filteredPacientes.length > 0"
            class="btn btn-outline-success btn-sm"
            @click="exportResults"
          >
            <i class="fas fa-download me-1"></i>
            Exportar
          </button>
        </div>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>Paciente</th>
                <th>Documento</th>
                <th>Edad</th>
                <th>Género</th>
                <th>Contacto</th>
                <th>Última Consulta</th>
                <th>Casos Médicos</th>
                <th class="text-center">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="loading">
                <td colspan="8" class="text-center py-4">
                  <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Buscando...</span>
                  </div>
                  <p class="mt-2 mb-0 text-muted">Buscando pacientes...</p>
                </td>
              </tr>
              <tr v-else-if="filteredPacientes.length === 0">
                <td colspan="8" class="text-center py-4">
                  <i class="fas fa-search fa-2x text-muted mb-2"></i>
                  <p class="mb-0 text-muted">
                    {{ searchQuery ? 'No se encontraron pacientes con los criterios especificados' : 'Ingresa un término de búsqueda para encontrar pacientes' }}
                  </p>
                </td>
              </tr>
              <tr v-else v-for="paciente in paginatedPacientes" :key="paciente.id">
                <td>
                  <div class="d-flex align-items-center">
                    <div class="avatar-placeholder me-3">
                      {{ getInitials(paciente.nombre) }}
                    </div>
                    <div>
                      <div class="fw-semibold">{{ paciente.nombre }}</div>
                      <small class="text-muted">ID: {{ paciente.id }}</small>
                    </div>
                  </div>
                </td>
                <td>
                  <div>
                    <div class="fw-semibold">{{ paciente.tipo_documento }} {{ paciente.numero_documento }}</div>
                    <small class="text-muted">{{ paciente.lugar_expedicion || 'No especificado' }}</small>
                  </div>
                </td>
                <td>
                  <span class="fw-semibold">{{ calculateAge(paciente.fecha_nacimiento) }} años</span>
                  <br>
                  <small class="text-muted">{{ formatDate(paciente.fecha_nacimiento, 'DD/MM/YYYY') }}</small>
                </td>
                <td>
                  <span class="badge" :class="getGenderBadgeClass(paciente.genero)">
                    {{ getGenderText(paciente.genero) }}
                  </span>
                </td>
                <td>
                  <div v-if="paciente.telefono || paciente.email">
                    <div v-if="paciente.telefono" class="mb-1">
                      <i class="fas fa-phone text-muted me-1"></i>
                      {{ paciente.telefono }}
                    </div>
                    <div v-if="paciente.email">
                      <i class="fas fa-envelope text-muted me-1"></i>
                      {{ paciente.email }}
                    </div>
                  </div>
                  <span v-else class="text-muted">No disponible</span>
                </td>
                <td>
                  <span v-if="paciente.ultima_consulta">
                    {{ formatDate(paciente.ultima_consulta, 'DD/MM/YYYY') }}
                    <br>
                    <small class="text-muted">{{ timeAgo(paciente.ultima_consulta) }}</small>
                  </span>
                  <span v-else class="text-muted">Sin consultas</span>
                </td>
                <td class="text-center">
                  <span class="badge bg-info">
                    {{ paciente.casos_count || 0 }}
                  </span>
                </td>
                <td>
                  <div class="btn-group btn-group-sm">
                    <button
                      class="btn btn-outline-primary"
                      @click="viewPatientHistory(paciente)"
                      :title="`Ver historial de ${paciente.nombre}`"
                    >
                      <i class="fas fa-file-medical"></i>
                    </button>
                    <button
                      class="btn btn-outline-info"
                      @click="viewPatientCases(paciente)"
                      :title="`Ver casos de ${paciente.nombre}`"
                    >
                      <i class="fas fa-list-alt"></i>
                    </button>
                    <button
                      class="btn btn-outline-success"
                      @click="createNewCase(paciente)"
                      :title="`Crear nuevo caso para ${paciente.nombre}`"
                    >
                      <i class="fas fa-plus"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Pagination -->
      <div class="card-footer" v-if="filteredPacientes.length > itemsPerPage">
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
  name: 'ConsultaPacientes',
  components: {
    Link
  },
  props: {
    pacientes: {
      type: Array,
      default: () => []
    }
  },
  data() {
    return {
      loading: false,
      searchQuery: '',
      showAdvancedSearch: false,
      currentPage: 1,
      itemsPerPage: 15,
      filters: {
        tipoDocumento: '',
        rangoEdad: '',
        genero: '',
        fechaNacimientoDesde: '',
        fechaNacimientoHasta: ''
      },
      searchTimeout: null
    }
  },
  computed: {
    filteredPacientes() {
      let filtered = [...this.pacientes]

      // Search filter
      if (this.searchQuery) {
        const search = this.searchQuery.toLowerCase()
        filtered = filtered.filter(paciente =>
          paciente.nombre?.toLowerCase().includes(search) ||
          paciente.numero_documento?.toLowerCase().includes(search) ||
          paciente.email?.toLowerCase().includes(search) ||
          paciente.telefono?.toLowerCase().includes(search)
        )
      }

      // Document type filter
      if (this.filters.tipoDocumento) {
        filtered = filtered.filter(paciente => paciente.tipo_documento === this.filters.tipoDocumento)
      }

      // Age range filter
      if (this.filters.rangoEdad) {
        filtered = filtered.filter(paciente => {
          const age = this.calculateAge(paciente.fecha_nacimiento)
          const [min, max] = this.filters.rangoEdad.split('-').map(x => x === '+' ? 999 : parseInt(x))
          return age >= min && (max ? age <= max : true)
        })
      }

      // Gender filter
      if (this.filters.genero) {
        filtered = filtered.filter(paciente => paciente.genero === this.filters.genero)
      }

      // Birth date filters
      if (this.filters.fechaNacimientoDesde) {
        filtered = filtered.filter(paciente =>
          new Date(paciente.fecha_nacimiento) >= new Date(this.filters.fechaNacimientoDesde)
        )
      }

      if (this.filters.fechaNacimientoHasta) {
        filtered = filtered.filter(paciente =>
          new Date(paciente.fecha_nacimiento) <= new Date(this.filters.fechaNacimientoHasta)
        )
      }

      return filtered
    },

    paginatedPacientes() {
      const start = (this.currentPage - 1) * this.itemsPerPage
      const end = start + this.itemsPerPage
      return this.filteredPacientes.slice(start, end)
    },

    totalPages() {
      return Math.ceil(this.filteredPacientes.length / this.itemsPerPage)
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
    debouncedSearch() {
      clearTimeout(this.searchTimeout)
      this.searchTimeout = setTimeout(() => {
        this.currentPage = 1
        this.performSearch()
      }, 300)
    },

    performSearch() {
      if (this.searchQuery.length >= 3 || this.searchQuery === '') {
        this.loading = true
        // In a real app, you would make an API call here
        setTimeout(() => {
          this.loading = false
        }, 500)
      }
    },

    applyFilters() {
      this.currentPage = 1
      this.performSearch()
    },

    viewPatientHistory(paciente) {
      this.$inertia.visit(route('medico.paciente.historial', paciente.id))
    },

    viewPatientCases(paciente) {
      this.$inertia.visit(route('medico.paciente.casos', paciente.id))
    },

    createNewCase(paciente) {
      this.$inertia.visit(route('medico.crear-caso', { paciente_id: paciente.id }))
    },

    exportResults() {
      const params = new URLSearchParams({
        search: this.searchQuery,
        ...this.filters
      })
      window.open(route('medico.pacientes.export') + '?' + params.toString(), '_blank')
    },

    calculateAge(birthDate) {
      if (!birthDate) return 0
      const today = new Date()
      const birth = new Date(birthDate)
      let age = today.getFullYear() - birth.getFullYear()
      const monthDiff = today.getMonth() - birth.getMonth()

      if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
        age--
      }

      return age
    },

    getInitials(name) {
      return name
        .split(' ')
        .map(word => word.charAt(0))
        .join('')
        .toUpperCase()
        .substring(0, 2)
    },

    getGenderBadgeClass(gender) {
      const classes = {
        'M': 'bg-primary',
        'F': 'bg-pink',
        'O': 'bg-secondary'
      }
      return classes[gender] || 'bg-secondary'
    },

    getGenderText(gender) {
      const texts = {
        'M': 'Masculino',
        'F': 'Femenino',
        'O': 'Otro'
      }
      return texts[gender] || 'No especificado'
    },

    formatDate(date, format = 'DD/MM/YYYY') {
      if (!date) return ''
      const d = new Date(date)
      return d.toLocaleDateString('es-ES')
    },

    timeAgo(date) {
      if (!date) return ''
      const now = new Date()
      const past = new Date(date)
      const diffInDays = Math.floor((now - past) / (1000 * 60 * 60 * 24))

      if (diffInDays === 0) return 'Hoy'
      if (diffInDays === 1) return 'Ayer'
      if (diffInDays < 30) return `${diffInDays} días`
      if (diffInDays < 365) return `${Math.floor(diffInDays / 30)} meses`
      return `${Math.floor(diffInDays / 365)} años`
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
.consulta-pacientes-container {
  padding: 1rem;
}

.avatar-placeholder {
  width: 40px;
  height: 40px;
  background: linear-gradient(135deg, #2c5aa0, #4dabf7);
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.875rem;
  font-weight: 600;
}

.bg-pink {
  background-color: #e91e63 !important;
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
  .consulta-pacientes-container {
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
