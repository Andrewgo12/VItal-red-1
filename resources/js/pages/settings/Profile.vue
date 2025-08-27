<template>
  <div class="profile-container">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="h3 mb-0 text-primary">
          <i class="fas fa-user-cog me-2"></i>
          Configuración de Perfil
        </h1>
        <p class="text-muted mb-0">
          Gestiona tu información personal y preferencias
        </p>
      </div>
    </div>

    <div class="row">
      <div class="col-lg-8">
        <!-- Profile Information -->
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="card-title mb-0">
              <i class="fas fa-user me-2"></i>
              Información Personal
            </h5>
          </div>
          <div class="card-body">
            <form @submit.prevent="updateProfile">
              <div class="row g-3">
                <div class="col-md-6">
                  <label for="name" class="form-label">Nombre Completo *</label>
                  <input
                    id="name"
                    v-model="profileForm.name"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': profileForm.errors.name }"
                    required
                  >
                  <div v-if="profileForm.errors.name" class="invalid-feedback">
                    {{ profileForm.errors.name }}
                  </div>
                </div>

                <div class="col-md-6">
                  <label for="email" class="form-label">Email *</label>
                  <input
                    id="email"
                    v-model="profileForm.email"
                    type="email"
                    class="form-control"
                    :class="{ 'is-invalid': profileForm.errors.email }"
                    required
                  >
                  <div v-if="profileForm.errors.email" class="invalid-feedback">
                    {{ profileForm.errors.email }}
                  </div>
                  <div v-if="mustVerifyEmail && !$page.props.auth.user.email_verified_at" class="mt-2">
                    <small class="text-warning">
                      <i class="fas fa-exclamation-triangle me-1"></i>
                      Tu email no está verificado.
                      <button
                        type="button"
                        class="btn btn-link btn-sm p-0 ms-1"
                        @click="sendVerification"
                      >
                        Reenviar verificación
                      </button>
                    </small>
                  </div>
                </div>

                <div class="col-md-6" v-if="$page.props.auth.user.role === 'medico'">
                  <label for="especialidad" class="form-label">Especialidad</label>
                  <select
                    id="especialidad"
                    v-model="profileForm.especialidad"
                    class="form-select"
                    :class="{ 'is-invalid': profileForm.errors.especialidad }"
                  >
                    <option value="">Seleccionar especialidad</option>
                    <option v-for="especialidad in especialidades" :key="especialidad" :value="especialidad">
                      {{ especialidad }}
                    </option>
                  </select>
                  <div v-if="profileForm.errors.especialidad" class="invalid-feedback">
                    {{ profileForm.errors.especialidad }}
                  </div>
                </div>

                <div class="col-md-6">
                  <label for="telefono" class="form-label">Teléfono</label>
                  <input
                    id="telefono"
                    v-model="profileForm.telefono"
                    type="tel"
                    class="form-control"
                    :class="{ 'is-invalid': profileForm.errors.telefono }"
                    placeholder="+57 300 123 4567"
                  >
                  <div v-if="profileForm.errors.telefono" class="invalid-feedback">
                    {{ profileForm.errors.telefono }}
                  </div>
                </div>

                <div class="col-12">
                  <label for="bio" class="form-label">Biografía Profesional</label>
                  <textarea
                    id="bio"
                    v-model="profileForm.bio"
                    class="form-control"
                    :class="{ 'is-invalid': profileForm.errors.bio }"
                    rows="4"
                    placeholder="Describe tu experiencia profesional..."
                  ></textarea>
                  <div v-if="profileForm.errors.bio" class="invalid-feedback">
                    {{ profileForm.errors.bio }}
                  </div>
                </div>
              </div>

              <div class="d-flex justify-content-end mt-4">
                <button
                  type="submit"
                  class="btn btn-primary"
                  :disabled="profileForm.processing"
                >
                  <span v-if="profileForm.processing" class="spinner-border spinner-border-sm me-2"></span>
                  <i v-else class="fas fa-save me-1"></i>
                  {{ profileForm.processing ? 'Guardando...' : 'Guardar Cambios' }}
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Notification Preferences -->
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="card-title mb-0">
              <i class="fas fa-bell me-2"></i>
              Preferencias de Notificación
            </h5>
          </div>
          <div class="card-body">
            <form @submit.prevent="updateNotifications">
              <div class="row g-3">
                <div class="col-12">
                  <div class="form-check form-switch">
                    <input
                      id="email_notifications"
                      v-model="notificationForm.email_notifications"
                      type="checkbox"
                      class="form-check-input"
                    >
                    <label for="email_notifications" class="form-check-label">
                      Notificaciones por Email
                    </label>
                  </div>
                  <small class="text-muted">Recibir notificaciones de casos urgentes por email</small>
                </div>

                <div class="col-12">
                  <div class="form-check form-switch">
                    <input
                      id="push_notifications"
                      v-model="notificationForm.push_notifications"
                      type="checkbox"
                      class="form-check-input"
                    >
                    <label for="push_notifications" class="form-check-label">
                      Notificaciones Push
                    </label>
                  </div>
                  <small class="text-muted">Recibir notificaciones en tiempo real en el navegador</small>
                </div>

                <div class="col-12">
                  <div class="form-check form-switch">
                    <input
                      id="sms_notifications"
                      v-model="notificationForm.sms_notifications"
                      type="checkbox"
                      class="form-check-input"
                    >
                    <label for="sms_notifications" class="form-check-label">
                      Notificaciones SMS
                    </label>
                  </div>
                  <small class="text-muted">Recibir SMS para casos críticos (requiere teléfono)</small>
                </div>

                <div class="col-md-6">
                  <label for="notification_frequency" class="form-label">Frecuencia de Resumen</label>
                  <select
                    id="notification_frequency"
                    v-model="notificationForm.notification_frequency"
                    class="form-select"
                  >
                    <option value="immediate">Inmediato</option>
                    <option value="hourly">Cada hora</option>
                    <option value="daily">Diario</option>
                    <option value="weekly">Semanal</option>
                  </select>
                </div>

                <div class="col-md-6">
                  <label for="quiet_hours_start" class="form-label">Horario Silencioso (Inicio)</label>
                  <input
                    id="quiet_hours_start"
                    v-model="notificationForm.quiet_hours_start"
                    type="time"
                    class="form-control"
                  >
                </div>

                <div class="col-md-6">
                  <label for="quiet_hours_end" class="form-label">Horario Silencioso (Fin)</label>
                  <input
                    id="quiet_hours_end"
                    v-model="notificationForm.quiet_hours_end"
                    type="time"
                    class="form-control"
                  >
                </div>
              </div>

              <div class="d-flex justify-content-end mt-4">
                <button
                  type="submit"
                  class="btn btn-primary"
                  :disabled="notificationForm.processing"
                >
                  <span v-if="notificationForm.processing" class="spinner-border spinner-border-sm me-2"></span>
                  <i v-else class="fas fa-save me-1"></i>
                  {{ notificationForm.processing ? 'Guardando...' : 'Guardar Preferencias' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <!-- Profile Picture -->
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="card-title mb-0">
              <i class="fas fa-camera me-2"></i>
              Foto de Perfil
            </h5>
          </div>
          <div class="card-body text-center">
            <div class="profile-picture-container mb-3">
              <img
                :src="profilePicture || '/images/default-avatar.png'"
                alt="Foto de perfil"
                class="profile-picture"
              >
              <div class="profile-picture-overlay">
                <i class="fas fa-camera"></i>
              </div>
            </div>
            <input
              ref="fileInput"
              type="file"
              accept="image/*"
              class="d-none"
              @change="handleFileUpload"
            >
            <button
              class="btn btn-outline-primary btn-sm"
              @click="$refs.fileInput.click()"
            >
              <i class="fas fa-upload me-1"></i>
              Cambiar Foto
            </button>
          </div>
        </div>

        <!-- Account Security -->
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="card-title mb-0">
              <i class="fas fa-shield-alt me-2"></i>
              Seguridad de la Cuenta
            </h5>
          </div>
          <div class="card-body">
            <div class="security-item mb-3">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <div class="fw-semibold">Contraseña</div>
                  <small class="text-muted">Última actualización: hace 30 días</small>
                </div>
                <Link
                  :href="route('password.edit')"
                  class="btn btn-outline-primary btn-sm"
                >
                  Cambiar
                </Link>
              </div>
            </div>

            <div class="security-item mb-3">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <div class="fw-semibold">Autenticación de Dos Factores</div>
                  <small class="text-muted">Protección adicional para tu cuenta</small>
                </div>
                <button class="btn btn-outline-success btn-sm">
                  Activar
                </button>
              </div>
            </div>

            <div class="security-item">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <div class="fw-semibold">Sesiones Activas</div>
                  <small class="text-muted">Gestionar dispositivos conectados</small>
                </div>
                <button class="btn btn-outline-info btn-sm">
                  Ver
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Account Stats -->
        <div class="card">
          <div class="card-header">
            <h5 class="card-title mb-0">
              <i class="fas fa-chart-bar me-2"></i>
              Estadísticas de Cuenta
            </h5>
          </div>
          <div class="card-body">
            <div class="stat-item mb-3">
              <div class="d-flex justify-content-between">
                <span class="text-muted">Casos Evaluados</span>
                <span class="fw-semibold">{{ stats.casos_evaluados || 0 }}</span>
              </div>
            </div>
            <div class="stat-item mb-3">
              <div class="d-flex justify-content-between">
                <span class="text-muted">Tiempo Promedio</span>
                <span class="fw-semibold">{{ stats.tiempo_promedio || '0h' }}</span>
              </div>
            </div>
            <div class="stat-item mb-3">
              <div class="d-flex justify-content-between">
                <span class="text-muted">Último Acceso</span>
                <span class="fw-semibold">{{ formatDate(stats.ultimo_acceso) || 'Nunca' }}</span>
              </div>
            </div>
            <div class="stat-item">
              <div class="d-flex justify-content-between">
                <span class="text-muted">Miembro desde</span>
                <span class="fw-semibold">{{ formatDate($page.props.auth.user.created_at, 'MMM YYYY') }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Link } from '@inertiajs/vue3'

export default {
  name: 'Profile',
  components: {
    Link
  },
  props: {
    mustVerifyEmail: Boolean,
    status: String,
    especialidades: {
      type: Array,
      default: () => [
        'Cardiología', 'Neurología', 'Pediatría', 'Ginecología',
        'Medicina Interna', 'Cirugía General', 'Ortopedia', 'Dermatología'
      ]
    },
    stats: {
      type: Object,
      default: () => ({
        casos_evaluados: 0,
        tiempo_promedio: '0h',
        ultimo_acceso: null
      })
    }
  },
  data() {
    return {
      profileForm: {
        name: this.$page.props.auth.user.name,
        email: this.$page.props.auth.user.email,
        especialidad: this.$page.props.auth.user.especialidad || '',
        telefono: this.$page.props.auth.user.telefono || '',
        bio: this.$page.props.auth.user.bio || '',
        processing: false,
        errors: {}
      },
      notificationForm: {
        email_notifications: true,
        push_notifications: true,
        sms_notifications: false,
        notification_frequency: 'immediate',
        quiet_hours_start: '22:00',
        quiet_hours_end: '07:00',
        processing: false
      },
      profilePicture: this.$page.props.auth.user.profile_picture || null
    }
  },
  methods: {
    updateProfile() {
      this.profileForm.processing = true
      this.profileForm.errors = {}

      this.$inertia.patch(route('profile.update'), this.profileForm, {
        onSuccess: () => {
          this.$showToast('Perfil actualizado exitosamente', 'success')
        },
        onError: (errors) => {
          this.profileForm.errors = errors
          this.$showToast('Error al actualizar perfil', 'error')
        },
        onFinish: () => {
          this.profileForm.processing = false
        }
      })
    },

    updateNotifications() {
      this.notificationForm.processing = true

      this.$inertia.patch(route('profile.notifications'), this.notificationForm, {
        onSuccess: () => {
          this.$showToast('Preferencias de notificación actualizadas', 'success')
        },
        onError: () => {
          this.$showToast('Error al actualizar preferencias', 'error')
        },
        onFinish: () => {
          this.notificationForm.processing = false
        }
      })
    },

    sendVerification() {
      this.$inertia.post(route('verification.send'), {}, {
        onSuccess: () => {
          this.$showToast('Email de verificación enviado', 'success')
        }
      })
    },

    handleFileUpload(event) {
      const file = event.target.files[0]
      if (!file) return

      // Validate file type
      if (!file.type.startsWith('image/')) {
        this.$showToast('Por favor selecciona una imagen válida', 'error')
        return
      }

      // Validate file size (max 2MB)
      if (file.size > 2 * 1024 * 1024) {
        this.$showToast('La imagen debe ser menor a 2MB', 'error')
        return
      }

      const formData = new FormData()
      formData.append('profile_picture', file)

      this.$inertia.post(route('profile.picture'), formData, {
        onSuccess: () => {
          this.$showToast('Foto de perfil actualizada', 'success')
          // Update local preview
          const reader = new FileReader()
          reader.onload = (e) => {
            this.profilePicture = e.target.result
          }
          reader.readAsDataURL(file)
        },
        onError: () => {
          this.$showToast('Error al subir la imagen', 'error')
        }
      })
    },

    formatDate(date, format = 'DD/MM/YYYY') {
      if (!date) return ''
      const d = new Date(date)

      if (format === 'MMM YYYY') {
        return d.toLocaleDateString('es-ES', { month: 'short', year: 'numeric' })
      }

      return d.toLocaleDateString('es-ES')
    }
  }
}
</script>

<style scoped>
.profile-container {
  padding: 1rem;
}

.profile-picture-container {
  position: relative;
  display: inline-block;
}

.profile-picture {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  object-fit: cover;
  border: 4px solid #fff;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.profile-picture-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 0.3s ease;
  cursor: pointer;
  color: white;
  font-size: 1.5rem;
}

.profile-picture-container:hover .profile-picture-overlay {
  opacity: 1;
}

.security-item {
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 0.375rem;
  border-left: 4px solid #007bff;
}

.stat-item {
  padding: 0.75rem 0;
  border-bottom: 1px solid #f8f9fa;
}

.stat-item:last-child {
  border-bottom: none;
}

.form-check-input:checked {
  background-color: #2c5aa0;
  border-color: #2c5aa0;
}

.text-primary {
  color: #2c5aa0 !important;
}

.btn-primary {
  background: linear-gradient(135deg, #2c5aa0 0%, #1e3d6f 100%);
  border: none;
}

@media (max-width: 768px) {
  .profile-container {
    padding: 0.5rem;
  }

  .profile-picture {
    width: 100px;
    height: 100px;
  }
}
</style>
