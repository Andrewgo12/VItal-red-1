<template>
  <div id="app">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
      <div class="container-fluid">
        <!-- Brand -->
        <Link class="navbar-brand d-flex align-items-center" :href="route('dashboard')">
          <i class="fas fa-heartbeat me-2 text-primary"></i>
          <span class="fw-bold">Vital Red</span>
        </Link>

        <!-- Mobile toggle -->
        <button
          class="navbar-toggler"
          type="button"
          data-bs-toggle="collapse"
          data-bs-target="#navbarNav"
          aria-controls="navbarNav"
          aria-expanded="false"
          aria-label="Toggle navigation"
        >
          <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navigation items -->
        <div class="collapse navbar-collapse" id="navbarNav">
          <ul class="navbar-nav me-auto">
            <!-- Dashboard -->
            <li class="nav-item">
              <Link
                class="nav-link"
                :class="{ active: $page.component === 'Dashboard' }"
                :href="route('dashboard')"
              >
                <i class="fas fa-tachometer-alt me-1"></i>
                Dashboard
              </Link>
            </li>

            <!-- Medical Cases (for doctors and admins) -->
            <li v-if="can('view-medical-cases')" class="nav-item dropdown">
              <a
                class="nav-link dropdown-toggle"
                href="#"
                role="button"
                data-bs-toggle="dropdown"
                :class="{ active: $page.component.startsWith('Medical') }"
              >
                <i class="fas fa-file-medical me-1"></i>
                Casos Médicos
              </a>
              <ul class="dropdown-menu">
                <li>
                  <Link class="dropdown-item" :href="route('medico.bandeja-casos')">
                    <i class="fas fa-inbox me-1"></i>
                    Bandeja de Casos
                  </Link>
                </li>
                <li>
                  <Link class="dropdown-item" :href="route('medico.casos-urgentes')">
                    <i class="fas fa-exclamation-triangle me-1 text-danger"></i>
                    Casos Urgentes
                  </Link>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                  <Link class="dropdown-item" :href="route('solicitudes-medicas.create')">
                    <i class="fas fa-plus me-1"></i>
                    Nuevo Caso
                  </Link>
                </li>
              </ul>
            </li>

            <!-- Admin section -->
            <li v-if="can('view-admin-panel')" class="nav-item dropdown">
              <a
                class="nav-link dropdown-toggle"
                href="#"
                role="button"
                data-bs-toggle="dropdown"
                :class="{ active: $page.component.startsWith('Admin') }"
              >
                <i class="fas fa-cog me-1"></i>
                Administración
              </a>
              <ul class="dropdown-menu">
                <li>
                  <Link class="dropdown-item" :href="route('admin.users.index')">
                    <i class="fas fa-users me-1"></i>
                    Gestión de Usuarios
                  </Link>
                </li>
                <li>
                  <Link class="dropdown-item" :href="route('admin.reports.index')">
                    <i class="fas fa-chart-bar me-1"></i>
                    Reportes
                  </Link>
                </li>
                <li>
                  <Link class="dropdown-item" :href="route('admin.config')">
                    <i class="fas fa-sliders-h me-1"></i>
                    Configuración
                  </Link>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                  <Link class="dropdown-item" :href="route('admin.system-status')">
                    <i class="fas fa-server me-1"></i>
                    Estado del Sistema
                  </Link>
                </li>
              </ul>
            </li>
          </ul>

          <!-- Right side navigation -->
          <ul class="navbar-nav">
            <!-- Notifications -->
            <li class="nav-item dropdown">
              <a
                class="nav-link position-relative"
                href="#"
                role="button"
                data-bs-toggle="dropdown"
                @click="markNotificationsAsRead"
              >
                <i class="fas fa-bell"></i>
                <span
                  v-if="unreadNotificationsCount > 0"
                  class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                >
                  {{ unreadNotificationsCount }}
                </span>
              </a>
              <div class="dropdown-menu dropdown-menu-end notification-dropdown">
                <h6 class="dropdown-header">
                  <i class="fas fa-bell me-1"></i>
                  Notificaciones
                </h6>
                <div v-if="notifications.length === 0" class="dropdown-item-text text-muted">
                  No hay notificaciones
                </div>
                <div v-else>
                  <div
                    v-for="notification in notifications.slice(0, 5)"
                    :key="notification.id"
                    class="dropdown-item notification-item"
                    @click="handleNotificationClick(notification)"
                  >
                    <div class="d-flex">
                      <div class="flex-shrink-0">
                        <i :class="getNotificationIcon(notification.type)" class="text-primary"></i>
                      </div>
                      <div class="flex-grow-1 ms-2">
                        <div class="fw-semibold">{{ notification.title }}</div>
                        <div class="small text-muted">{{ notification.message }}</div>
                        <div class="small text-muted">{{ timeAgo(notification.created_at) }}</div>
                      </div>
                    </div>
                  </div>
                  <div class="dropdown-divider"></div>
                  <Link class="dropdown-item text-center" :href="route('notifications.index')">
                    Ver todas las notificaciones
                  </Link>
                </div>
              </div>
            </li>

            <!-- User menu -->
            <li class="nav-item dropdown">
              <a
                class="nav-link dropdown-toggle d-flex align-items-center"
                href="#"
                role="button"
                data-bs-toggle="dropdown"
              >
                <div class="avatar me-2">
                  <img
                    v-if="$page.props.auth.user.avatar_url"
                    :src="$page.props.auth.user.avatar_url"
                    :alt="$page.props.auth.user.name"
                    class="rounded-circle"
                    width="32"
                    height="32"
                  >
                  <div v-else class="avatar-placeholder">
                    {{ getInitials($page.props.auth.user.name) }}
                  </div>
                </div>
                <span class="d-none d-md-inline">{{ $page.props.auth.user.name }}</span>
              </a>
              <ul class="dropdown-menu dropdown-menu-end">
                <li>
                  <div class="dropdown-item-text">
                    <div class="fw-semibold">{{ $page.props.auth.user.name }}</div>
                    <div class="small text-muted">{{ $page.props.auth.user.email }}</div>
                    <div class="small text-muted">{{ $page.props.auth.user.role }}</div>
                  </div>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                  <Link class="dropdown-item" :href="route('profile.edit')">
                    <i class="fas fa-user me-1"></i>
                    Mi Perfil
                  </Link>
                </li>
                <li>
                  <Link class="dropdown-item" :href="route('settings.notifications')">
                    <i class="fas fa-bell me-1"></i>
                    Notificaciones
                  </Link>
                </li>
                <li>
                  <Link class="dropdown-item" :href="route('settings.appearance')">
                    <i class="fas fa-palette me-1"></i>
                    Apariencia
                  </Link>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                  <Link
                    class="dropdown-item text-danger"
                    :href="route('logout')"
                    method="post"
                    as="button"
                  >
                    <i class="fas fa-sign-out-alt me-1"></i>
                    Cerrar Sesión
                  </Link>
                </li>
              </ul>
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <!-- Main content -->
    <main class="main-content">
      <div class="container-fluid">
        <!-- Flash messages -->
        <div v-if="$page.props.flash.success" class="alert alert-success alert-dismissible fade show mt-3" role="alert">
          <i class="fas fa-check-circle me-1"></i>
          {{ $page.props.flash.success }}
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>

        <div v-if="$page.props.flash.error" class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
          <i class="fas fa-exclamation-circle me-1"></i>
          {{ $page.props.flash.error }}
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>

        <div v-if="$page.props.flash.warning" class="alert alert-warning alert-dismissible fade show mt-3" role="alert">
          <i class="fas fa-exclamation-triangle me-1"></i>
          {{ $page.props.flash.warning }}
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>

        <div v-if="$page.props.flash.info" class="alert alert-info alert-dismissible fade show mt-3" role="alert">
          <i class="fas fa-info-circle me-1"></i>
          {{ $page.props.flash.info }}
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>

        <!-- Page content -->
        <slot />
      </div>
    </main>

    <!-- Footer -->
    <footer class="footer bg-light border-top mt-auto py-3">
      <div class="container-fluid">
        <div class="row align-items-center">
          <div class="col-md-6">
            <span class="text-muted">
              © {{ currentYear }} Vital Red. Todos los derechos reservados.
            </span>
          </div>
          <div class="col-md-6 text-md-end">
            <span class="text-muted">
              Versión {{ $page.props.config.version || '1.0.0' }}
            </span>
          </div>
        </div>
      </div>
    </footer>

    <!-- Loading overlay -->
    <div v-if="loading" class="loading-overlay">
      <div class="text-center">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Cargando...</span>
        </div>
        <div class="mt-2">{{ loadingMessage }}</div>
      </div>
    </div>
  </div>
</template>

<script>
import { Link } from '@inertiajs/vue3'

export default {
  name: 'AppLayout',
  components: {
    Link
  },
  data() {
    return {
      loading: false,
      loadingMessage: 'Cargando...',
      currentYear: new Date().getFullYear()
    }
  },
  computed: {
    notifications() {
      return this.$page.props.notifications?.recent || []
    },
    unreadNotificationsCount() {
      return this.$page.props.notifications?.unread_count || 0
    }
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

    getInitials(name) {
      return name
        .split(' ')
        .map(word => word.charAt(0))
        .join('')
        .toUpperCase()
        .substring(0, 2)
    },

    getNotificationIcon(type) {
      const icons = {
        'urgent_case': 'fas fa-exclamation-triangle text-danger',
        'new_case': 'fas fa-file-medical text-info',
        'case_assigned': 'fas fa-user-md text-primary',
        'case_updated': 'fas fa-edit text-warning',
        'system_alert': 'fas fa-bell text-danger',
        'reminder': 'fas fa-clock text-secondary'
      }
      return icons[type] || 'fas fa-info-circle text-info'
    },

    handleNotificationClick(notification) {
      if (notification.url) {
        this.$inertia.visit(notification.url)
      }
    },

    markNotificationsAsRead() {
      if (this.unreadNotificationsCount > 0) {
        this.$inertia.post(route('notifications.mark-all-read'), {}, {
          preserveScroll: true,
          preserveState: true
        })
      }
    }
  },
  mounted() {
    // Initialize Bootstrap tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl)
    })
  }
}
</script>

<style scoped>
.main-content {
  min-height: calc(100vh - 120px);
  padding-bottom: 2rem;
}

.avatar-placeholder {
  width: 32px;
  height: 32px;
  background-color: var(--medical-blue);
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 600;
}

.notification-dropdown {
  width: 350px;
  max-height: 400px;
  overflow-y: auto;
}

.notification-item {
  cursor: pointer;
  border-bottom: 1px solid #f8f9fa;
}

.notification-item:hover {
  background-color: #f8f9fa;
}

.footer {
  margin-top: auto;
}

#app {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

@media (max-width: 768px) {
  .notification-dropdown {
    width: 300px;
  }
}
</style>
