<template>
  <div class="usuarios-container">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="h3 mb-0 text-primary">
          <i class="fas fa-users me-2"></i>
          Gestión de Usuarios
        </h1>
        <p class="text-muted mb-0">
          Administrar usuarios del sistema médico
        </p>
      </div>
      <div class="d-flex gap-2">
        <button
          class="btn btn-outline-primary btn-sm"
          @click="refreshUsers"
          :disabled="loading"
        >
          <i class="fas fa-sync-alt" :class="{ 'fa-spin': loading }"></i>
          Actualizar
        </button>
        <button
          class="btn btn-primary"
          @click="showCreateModal = true"
        >
          <i class="fas fa-plus me-1"></i>
          Nuevo Usuario
        </button>
      </div>
    </div>

    <!-- Filters and Search -->
    <div class="card mb-4">
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Buscar usuario</label>
            <div class="input-group">
              <span class="input-group-text">
                <i class="fas fa-search"></i>
              </span>
              <input
                v-model="filters.search"
                type="text"
                class="form-control"
                placeholder="Nombre, email o especialidad..."
                @input="debouncedSearch"
              >
            </div>
          </div>
          <div class="col-md-3">
            <label class="form-label">Rol</label>
            <select v-model="filters.role" class="form-select" @change="applyFilters">
              <option value="">Todos los roles</option>
              <option value="administrador">Administrador</option>
              <option value="medico">Médico</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Estado</label>
            <select v-model="filters.status" class="form-select" @change="applyFilters">
              <option value="">Todos los estados</option>
              <option value="activo">Activo</option>
              <option value="inactivo">Inactivo</option>
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

    <!-- Users Table -->
    <div class="card">
      <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0">
            <i class="fas fa-list me-2"></i>
            Lista de Usuarios ({{ filteredUsers.length }})
          </h5>
          <div class="d-flex gap-2">
            <button class="btn btn-outline-success btn-sm" @click="exportUsers">
              <i class="fas fa-download me-1"></i>
              Exportar
            </button>
          </div>
        </div>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>
                  <input
                    type="checkbox"
                    class="form-check-input"
                    @change="toggleSelectAll"
                    :checked="allSelected"
                  >
                </th>
                <th>Usuario</th>
                <th>Rol</th>
                <th>Especialidad</th>
                <th>Estado</th>
                <th>Último Acceso</th>
                <th>Casos Asignados</th>
                <th class="text-center">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="loading">
                <td colspan="8" class="text-center py-4">
                  <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                  </div>
                  <p class="mt-2 mb-0 text-muted">Cargando usuarios...</p>
                </td>
              </tr>
              <tr v-else-if="filteredUsers.length === 0">
                <td colspan="8" class="text-center py-4">
                  <i class="fas fa-users fa-2x text-muted mb-2"></i>
                  <p class="mb-0 text-muted">No se encontraron usuarios</p>
                </td>
              </tr>
              <tr v-else v-for="user in paginatedUsers" :key="user.id">
                <td>
                  <input
                    type="checkbox"
                    class="form-check-input"
                    v-model="selectedUsers"
                    :value="user.id"
                  >
                </td>
                <td>
                  <div class="d-flex align-items-center">
                    <div class="avatar-placeholder me-3">
                      {{ getInitials(user.name) }}
                    </div>
                    <div>
                      <div class="fw-semibold">{{ user.name }}</div>
                      <small class="text-muted">{{ user.email }}</small>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="badge" :class="getRoleBadgeClass(user.role)">
                    <i :class="getRoleIcon(user.role)" class="me-1"></i>
                    {{ getRoleText(user.role) }}
                  </span>
                </td>
                <td>
                  <span v-if="user.especialidad" class="text-primary">
                    {{ user.especialidad }}
                  </span>
                  <span v-else class="text-muted">-</span>
                </td>
                <td>
                  <span class="badge" :class="user.activo ? 'bg-success' : 'bg-danger'">
                    <i :class="user.activo ? 'fas fa-check' : 'fas fa-times'" class="me-1"></i>
                    {{ user.activo ? 'Activo' : 'Inactivo' }}
                  </span>
                </td>
                <td>
                  <span v-if="user.ultimo_acceso">
                    {{ formatDate(user.ultimo_acceso, 'DD/MM/YYYY') }}
                    <br>
                    <small class="text-muted">{{ timeAgo(user.ultimo_acceso) }}</small>
                  </span>
                  <span v-else class="text-muted">Nunca</span>
                </td>
                <td class="text-center">
                  <span class="badge bg-info">
                    {{ user.casos_asignados || 0 }}
                  </span>
                </td>
                <td>
                  <div class="btn-group btn-group-sm">
                    <button
                      class="btn btn-outline-primary"
                      @click="editUser(user)"
                      :title="`Editar ${user.name}`"
                    >
                      <i class="fas fa-edit"></i>
                    </button>
                    <button
                      class="btn btn-outline-warning"
                      @click="toggleUserStatus(user)"
                      :title="user.activo ? 'Desactivar' : 'Activar'"
                    >
                      <i :class="user.activo ? 'fas fa-pause' : 'fas fa-play'"></i>
                    </button>
                    <button
                      class="btn btn-outline-info"
                      @click="viewUserDetails(user)"
                      :title="`Ver detalles de ${user.name}`"
                    >
                      <i class="fas fa-eye"></i>
                    </button>
                    <button
                      class="btn btn-outline-danger"
                      @click="confirmDeleteUser(user)"
                      :title="`Eliminar ${user.name}`"
                      :disabled="user.id === $page.props.auth.user.id"
                    >
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Pagination -->
      <div class="card-footer" v-if="filteredUsers.length > itemsPerPage">
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

    <!-- Bulk Actions -->
    <div v-if="selectedUsers.length > 0" class="fixed-bottom-actions">
      <div class="card shadow-lg">
        <div class="card-body py-2">
          <div class="d-flex justify-content-between align-items-center">
            <span class="text-muted">
              {{ selectedUsers.length }} usuario(s) seleccionado(s)
            </span>
            <div class="btn-group btn-group-sm">
              <button class="btn btn-outline-success" @click="bulkActivate">
                <i class="fas fa-check me-1"></i>
                Activar
              </button>
              <button class="btn btn-outline-warning" @click="bulkDeactivate">
                <i class="fas fa-pause me-1"></i>
                Desactivar
              </button>
              <button class="btn btn-outline-danger" @click="bulkDelete">
                <i class="fas fa-trash me-1"></i>
                Eliminar
              </button>
              <button class="btn btn-outline-secondary" @click="clearSelection">
                <i class="fas fa-times me-1"></i>
                Cancelar
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create/Edit User Modal -->
    <div class="modal fade" :class="{ show: showCreateModal || showEditModal }" :style="{ display: showCreateModal || showEditModal ? 'block' : 'none' }">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i :class="showCreateModal ? 'fas fa-plus' : 'fas fa-edit'" class="me-2"></i>
              {{ showCreateModal ? 'Crear Nuevo Usuario' : 'Editar Usuario' }}
            </h5>
            <button type="button" class="btn-close" @click="closeModal"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveUser">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Nombre completo *</label>
                  <input
                    v-model="userForm.name"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': userForm.errors.name }"
                    required
                  >
                  <div v-if="userForm.errors.name" class="invalid-feedback">
                    {{ userForm.errors.name }}
                  </div>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Email *</label>
                  <input
                    v-model="userForm.email"
                    type="email"
                    class="form-control"
                    :class="{ 'is-invalid': userForm.errors.email }"
                    required
                  >
                  <div v-if="userForm.errors.email" class="invalid-feedback">
                    {{ userForm.errors.email }}
                  </div>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Rol *</label>
                  <select
                    v-model="userForm.role"
                    class="form-select"
                    :class="{ 'is-invalid': userForm.errors.role }"
                    required
                  >
                    <option value="">Seleccionar rol</option>
                    <option value="administrador">Administrador</option>
                    <option value="medico">Médico</option>
                  </select>
                  <div v-if="userForm.errors.role" class="invalid-feedback">
                    {{ userForm.errors.role }}
                  </div>
                </div>
                <div class="col-md-6" v-if="userForm.role === 'medico'">
                  <label class="form-label">Especialidad</label>
                  <select
                    v-model="userForm.especialidad"
                    class="form-select"
                    :class="{ 'is-invalid': userForm.errors.especialidad }"
                  >
                    <option value="">Seleccionar especialidad</option>
                    <option v-for="especialidad in especialidades" :key="especialidad" :value="especialidad">
                      {{ especialidad }}
                    </option>
                  </select>
                  <div v-if="userForm.errors.especialidad" class="invalid-feedback">
                    {{ userForm.errors.especialidad }}
                  </div>
                </div>
                <div class="col-md-6" v-if="showCreateModal">
                  <label class="form-label">Contraseña *</label>
                  <input
                    v-model="userForm.password"
                    type="password"
                    class="form-control"
                    :class="{ 'is-invalid': userForm.errors.password }"
                    required
                  >
                  <div v-if="userForm.errors.password" class="invalid-feedback">
                    {{ userForm.errors.password }}
                  </div>
                </div>
                <div class="col-md-6" v-if="showCreateModal">
                  <label class="form-label">Confirmar contraseña *</label>
                  <input
                    v-model="userForm.password_confirmation"
                    type="password"
                    class="form-control"
                    required
                  >
                </div>
                <div class="col-12">
                  <div class="form-check">
                    <input
                      v-model="userForm.activo"
                      type="checkbox"
                      class="form-check-input"
                      id="userActive"
                    >
                    <label class="form-check-label" for="userActive">
                      Usuario activo
                    </label>
                  </div>
                </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="closeModal">
              Cancelar
            </button>
            <button
              type="button"
              class="btn btn-primary"
              @click="saveUser"
              :disabled="userForm.processing"
            >
              <span v-if="userForm.processing" class="spinner-border spinner-border-sm me-2"></span>
              {{ showCreateModal ? 'Crear Usuario' : 'Guardar Cambios' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Backdrop -->
    <div v-if="showCreateModal || showEditModal" class="modal-backdrop fade show"></div>
  </div>
</template>

<script>
import { Link } from '@inertiajs/vue3'

export default {
  name: 'Usuarios',
  components: {
    Link
  },
  props: {
    users: {
      type: Array,
      default: () => []
    },
    especialidades: {
      type: Array,
      default: () => [
        'Cardiología', 'Neurología', 'Pediatría', 'Ginecología',
        'Medicina Interna', 'Cirugía General', 'Ortopedia', 'Dermatología',
        'Psiquiatría', 'Radiología', 'Anestesiología', 'Medicina de Emergencia'
      ]
    }
  },
  data() {
    return {
      loading: false,
      showCreateModal: false,
      showEditModal: false,
      selectedUsers: [],
      currentPage: 1,
      itemsPerPage: 10,
      filters: {
        search: '',
        role: '',
        status: ''
      },
      userForm: {
        id: null,
        name: '',
        email: '',
        role: '',
        especialidad: '',
        password: '',
        password_confirmation: '',
        activo: true,
        processing: false,
        errors: {}
      },
      searchTimeout: null
    }
  },
  computed: {
    filteredUsers() {
      let filtered = [...this.users]

      // Search filter
      if (this.filters.search) {
        const search = this.filters.search.toLowerCase()
        filtered = filtered.filter(user =>
          user.name.toLowerCase().includes(search) ||
          user.email.toLowerCase().includes(search) ||
          (user.especialidad && user.especialidad.toLowerCase().includes(search))
        )
      }

      // Role filter
      if (this.filters.role) {
        filtered = filtered.filter(user => user.role === this.filters.role)
      }

      // Status filter
      if (this.filters.status) {
        const isActive = this.filters.status === 'activo'
        filtered = filtered.filter(user => user.activo === isActive)
      }

      return filtered
    },

    paginatedUsers() {
      const start = (this.currentPage - 1) * this.itemsPerPage
      const end = start + this.itemsPerPage
      return this.filteredUsers.slice(start, end)
    },

    totalPages() {
      return Math.ceil(this.filteredUsers.length / this.itemsPerPage)
    },

    visiblePages() {
      const pages = []
      const start = Math.max(1, this.currentPage - 2)
      const end = Math.min(this.totalPages, this.currentPage + 2)

      for (let i = start; i <= end; i++) {
        pages.push(i)
      }

      return pages
    },

    allSelected() {
      return this.paginatedUsers.length > 0 &&
             this.paginatedUsers.every(user => this.selectedUsers.includes(user.id))
    }
  },
  methods: {
    refreshUsers() {
      this.loading = true
      this.$inertia.reload({
        only: ['users'],
        onFinish: () => {
          this.loading = false
          this.$showToast('Lista de usuarios actualizada', 'success')
        }
      })
    },

    debouncedSearch() {
      clearTimeout(this.searchTimeout)
      this.searchTimeout = setTimeout(() => {
        this.currentPage = 1
        this.applyFilters()
      }, 300)
    },

    applyFilters() {
      this.currentPage = 1
      // In a real app, you might want to make an API call here
    },

    clearFilters() {
      this.filters = {
        search: '',
        role: '',
        status: ''
      }
      this.currentPage = 1
    },

    toggleSelectAll() {
      if (this.allSelected) {
        this.selectedUsers = this.selectedUsers.filter(id =>
          !this.paginatedUsers.some(user => user.id === id)
        )
      } else {
        this.paginatedUsers.forEach(user => {
          if (!this.selectedUsers.includes(user.id)) {
            this.selectedUsers.push(user.id)
          }
        })
      }
    },

    clearSelection() {
      this.selectedUsers = []
    },

    editUser(user) {
      this.userForm = {
        id: user.id,
        name: user.name,
        email: user.email,
        role: user.role,
        especialidad: user.especialidad || '',
        password: '',
        password_confirmation: '',
        activo: user.activo,
        processing: false,
        errors: {}
      }
      this.showEditModal = true
    },

    closeModal() {
      this.showCreateModal = false
      this.showEditModal = false
      this.userForm = {
        id: null,
        name: '',
        email: '',
        role: '',
        especialidad: '',
        password: '',
        password_confirmation: '',
        activo: true,
        processing: false,
        errors: {}
      }
    },

    saveUser() {
      this.userForm.processing = true
      this.userForm.errors = {}

      const url = this.showCreateModal ? route('admin.usuarios.store') : route('admin.usuarios.update', this.userForm.id)
      const method = this.showCreateModal ? 'post' : 'put'

      this.$inertia[method](url, this.userForm, {
        onSuccess: () => {
          this.closeModal()
          this.$showToast(
            this.showCreateModal ? 'Usuario creado exitosamente' : 'Usuario actualizado exitosamente',
            'success'
          )
        },
        onError: (errors) => {
          this.userForm.errors = errors
          this.$showToast('Error al guardar usuario', 'error')
        },
        onFinish: () => {
          this.userForm.processing = false
        }
      })
    },

    toggleUserStatus(user) {
      this.$showConfirm(
        `${user.activo ? 'Desactivar' : 'Activar'} Usuario`,
        `¿Está seguro de ${user.activo ? 'desactivar' : 'activar'} a ${user.name}?`
      ).then((result) => {
        if (result.isConfirmed) {
          this.$inertia.patch(route('admin.usuarios.toggle-status', user.id), {}, {
            onSuccess: () => {
              this.$showToast(
                `Usuario ${user.activo ? 'desactivado' : 'activado'} exitosamente`,
                'success'
              )
            }
          })
        }
      })
    },

    confirmDeleteUser(user) {
      this.$showConfirm(
        'Eliminar Usuario',
        `¿Está seguro de eliminar a ${user.name}? Esta acción no se puede deshacer.`
      ).then((result) => {
        if (result.isConfirmed) {
          this.$inertia.delete(route('admin.usuarios.destroy', user.id), {
            onSuccess: () => {
              this.$showToast('Usuario eliminado exitosamente', 'success')
            }
          })
        }
      })
    },

    viewUserDetails(user) {
      // Navigate to user details page
      this.$inertia.visit(route('admin.usuarios.show', user.id))
    },

    bulkActivate() {
      this.bulkAction('activate', 'activar')
    },

    bulkDeactivate() {
      this.bulkAction('deactivate', 'desactivar')
    },

    bulkDelete() {
      this.$showConfirm(
        'Eliminar Usuarios',
        `¿Está seguro de eliminar ${this.selectedUsers.length} usuario(s)? Esta acción no se puede deshacer.`
      ).then((result) => {
        if (result.isConfirmed) {
          this.$inertia.post(route('admin.usuarios.bulk-delete'), {
            user_ids: this.selectedUsers
          }, {
            onSuccess: () => {
              this.clearSelection()
              this.$showToast('Usuarios eliminados exitosamente', 'success')
            }
          })
        }
      })
    },

    bulkAction(action, actionText) {
      this.$showConfirm(
        `${actionText.charAt(0).toUpperCase() + actionText.slice(1)} Usuarios`,
        `¿Está seguro de ${actionText} ${this.selectedUsers.length} usuario(s)?`
      ).then((result) => {
        if (result.isConfirmed) {
          this.$inertia.post(route(`admin.usuarios.bulk-${action}`), {
            user_ids: this.selectedUsers
          }, {
            onSuccess: () => {
              this.clearSelection()
              this.$showToast(`Usuarios ${actionText}dos exitosamente`, 'success')
            }
          })
        }
      })
    },

    exportUsers() {
      window.open(route('admin.usuarios.export'), '_blank')
    },

    getInitials(name) {
      return name
        .split(' ')
        .map(word => word.charAt(0))
        .join('')
        .toUpperCase()
        .substring(0, 2)
    },

    getRoleBadgeClass(role) {
      const classes = {
        'administrador': 'bg-danger',
        'medico': 'bg-primary'
      }
      return classes[role] || 'bg-secondary'
    },

    getRoleIcon(role) {
      const icons = {
        'administrador': 'fas fa-crown',
        'medico': 'fas fa-user-md'
      }
      return icons[role] || 'fas fa-user'
    },

    getRoleText(role) {
      const texts = {
        'administrador': 'Administrador',
        'medico': 'Médico'
      }
      return texts[role] || role
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
      const diffInMinutes = Math.floor((now - past) / (1000 * 60))

      if (diffInMinutes < 1) return 'Ahora'
      if (diffInMinutes < 60) return `${diffInMinutes}m`
      if (diffInMinutes < 1440) return `${Math.floor(diffInMinutes / 60)}h`
      return `${Math.floor(diffInMinutes / 1440)}d`
    }
  },

  watch: {
    'filters.search'() {
      this.debouncedSearch()
    }
  }
}
</script>

<style scoped>
.usuarios-container {
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

.fixed-bottom-actions {
  position: fixed;
  bottom: 20px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 1050;
  min-width: 400px;
}

.table th {
  border-top: none;
  font-weight: 600;
  color: #495057;
}

.btn-group-sm .btn {
  padding: 0.25rem 0.5rem;
}

.modal.show {
  background: rgba(0, 0, 0, 0.5);
}

@media (max-width: 768px) {
  .fixed-bottom-actions {
    left: 10px;
    right: 10px;
    transform: none;
    min-width: auto;
  }

  .table-responsive {
    font-size: 0.875rem;
  }

  .btn-group-sm .btn {
    padding: 0.125rem 0.25rem;
  }
}
</style>
