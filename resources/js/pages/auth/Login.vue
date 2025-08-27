<template>
  <div class="min-vh-100 d-flex align-items-center bg-light">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
          <div class="card shadow-lg border-0">
            <div class="card-body p-5">
              <!-- Logo and Title -->
              <div class="text-center mb-4">
                <div class="mb-3">
                  <i class="fas fa-heartbeat fa-3x text-primary"></i>
                </div>
                <h1 class="h3 mb-1 fw-bold text-primary">Vital Red</h1>
                <p class="text-muted">Sistema de Gestión Médica</p>
              </div>

              <!-- Login Form -->
              <form @submit.prevent="submit">
                <!-- Email Field -->
                <div class="mb-3">
                  <label for="email" class="form-label">
                    <i class="fas fa-envelope me-1"></i>
                    Correo Electrónico
                  </label>
                  <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors.email }"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="doctor@hospital.com"
                  >
                  <div v-if="form.errors.email" class="invalid-feedback">
                    {{ form.errors.email }}
                  </div>
                </div>

                <!-- Password Field -->
                <div class="mb-3">
                  <label for="password" class="form-label">
                    <i class="fas fa-lock me-1"></i>
                    Contraseña
                  </label>
                  <div class="input-group">
                    <input
                      id="password"
                      v-model="form.password"
                      :type="showPassword ? 'text' : 'password'"
                      class="form-control"
                      :class="{ 'is-invalid': form.errors.password }"
                      required
                      autocomplete="current-password"
                      placeholder="••••••••"
                    >
                    <button
                      type="button"
                      class="btn btn-outline-secondary"
                      @click="showPassword = !showPassword"
                    >
                      <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                    </button>
                  </div>
                  <div v-if="form.errors.password" class="invalid-feedback d-block">
                    {{ form.errors.password }}
                  </div>
                </div>

                <!-- Remember Me -->
                <div class="mb-3 form-check">
                  <input
                    id="remember"
                    v-model="form.remember"
                    type="checkbox"
                    class="form-check-input"
                  >
                  <label for="remember" class="form-check-label">
                    Recordar sesión
                  </label>
                </div>

                <!-- Error Messages -->
                <div v-if="form.errors.general" class="alert alert-danger">
                  <i class="fas fa-exclamation-triangle me-1"></i>
                  {{ form.errors.general }}
                </div>

                <!-- Submit Button -->
                <div class="d-grid mb-3">
                  <button
                    type="submit"
                    class="btn btn-primary btn-lg"
                    :disabled="form.processing"
                  >
                    <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                    <i v-else class="fas fa-sign-in-alt me-1"></i>
                    {{ form.processing ? 'Iniciando sesión...' : 'Iniciar Sesión' }}
                  </button>
                </div>

                <!-- Forgot Password Link -->
                <div class="text-center">
                  <Link
                    :href="route('password.request')"
                    class="text-decoration-none"
                  >
                    <i class="fas fa-key me-1"></i>
                    ¿Olvidaste tu contraseña?
                  </Link>
                </div>
              </form>
            </div>

            <!-- Footer -->
            <div class="card-footer bg-light text-center py-3">
              <small class="text-muted">
                <i class="fas fa-shield-alt me-1"></i>
                Acceso seguro para profesionales médicos
              </small>
            </div>
          </div>

          <!-- System Status -->
          <div class="text-center mt-3">
            <small class="text-muted">
              <i class="fas fa-circle text-success me-1"></i>
              Sistema operativo | Versión 1.0.0
            </small>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Link, useForm } from '@inertiajs/vue3'

export default {
  name: 'Login',
  components: {
    Link
  },
  props: {
    canResetPassword: Boolean,
    status: String
  },
  setup() {
    const form = useForm({
      email: '',
      password: '',
      remember: false
    })

    return { form }
  },
  data() {
    return {
      showPassword: false
    }
  },
  mounted() {
    // Focus on email field
    this.$nextTick(() => {
      document.getElementById('email')?.focus()
    })
  },
  methods: {
    submit() {
      this.form.post(route('login'), {
        onFinish: () => {
          this.form.reset('password')
        },
        onError: (errors) => {
          if (errors.email || errors.password) {
            this.$showToast('Credenciales incorrectas', 'error')
          }
        },
        onSuccess: () => {
          this.$showToast('Sesión iniciada correctamente', 'success')
        }
      })
    }
  }
}
</script>

<style scoped>
.card {
  border-radius: 1rem;
}

.btn-primary {
  background: linear-gradient(135deg, #2c5aa0 0%, #1e3d6f 100%);
  border: none;
  border-radius: 0.5rem;
  font-weight: 600;
  transition: all 0.3s ease;
}

.btn-primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(44, 90, 160, 0.3);
}

.form-control {
  border-radius: 0.5rem;
  border: 1px solid #dee2e6;
  padding: 0.75rem 1rem;
  transition: all 0.3s ease;
}

.form-control:focus {
  border-color: #2c5aa0;
  box-shadow: 0 0 0 0.2rem rgba(44, 90, 160, 0.25);
}

.text-primary {
  color: #2c5aa0 !important;
}

.bg-light {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%) !important;
}

@media (max-width: 768px) {
  .card-body {
    padding: 2rem !important;
  }
}
</style>
