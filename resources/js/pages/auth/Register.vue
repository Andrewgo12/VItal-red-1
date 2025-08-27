<template>
  <div class="min-vh-100 d-flex align-items-center bg-light">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
          <div class="card shadow-lg border-0">
            <div class="card-body p-5">
              <!-- Logo and Title -->
              <div class="text-center mb-4">
                <div class="mb-3">
                  <i class="fas fa-heartbeat fa-3x text-primary"></i>
                </div>
                <h1 class="h3 mb-1 fw-bold text-primary">Registro Médico</h1>
                <p class="text-muted">Crear cuenta en Vital Red</p>
              </div>

              <!-- Registration Form -->
              <form @submit.prevent="submit">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label for="name" class="form-label">Nombre Completo *</label>
                    <input
                      id="name"
                      v-model="form.name"
                      type="text"
                      class="form-control"
                      :class="{ 'is-invalid': form.errors.name }"
                      required
                      autofocus
                      placeholder="Dr. Juan Pérez"
                    >
                    <div v-if="form.errors.name" class="invalid-feedback">
                      {{ form.errors.name }}
                    </div>
                  </div>

                  <div class="col-md-6">
                    <label for="email" class="form-label">Email *</label>
                    <input
                      id="email"
                      v-model="form.email"
                      type="email"
                      class="form-control"
                      :class="{ 'is-invalid': form.errors.email }"
                      required
                      placeholder="doctor@hospital.com"
                    >
                    <div v-if="form.errors.email" class="invalid-feedback">
                      {{ form.errors.email }}
                    </div>
                  </div>

                  <div class="col-md-6">
                    <label for="role" class="form-label">Rol *</label>
                    <select
                      id="role"
                      v-model="form.role"
                      class="form-select"
                      :class="{ 'is-invalid': form.errors.role }"
                      required
                    >
                      <option value="">Seleccionar rol</option>
                      <option value="medico">Médico</option>
                      <option value="administrador">Administrador</option>
                    </select>
                    <div v-if="form.errors.role" class="invalid-feedback">
                      {{ form.errors.role }}
                    </div>
                  </div>

                  <div class="col-md-6" v-if="form.role === 'medico'">
                    <label for="especialidad" class="form-label">Especialidad</label>
                    <select
                      id="especialidad"
                      v-model="form.especialidad"
                      class="form-select"
                      :class="{ 'is-invalid': form.errors.especialidad }"
                    >
                      <option value="">Seleccionar especialidad</option>
                      <option value="Cardiología">Cardiología</option>
                      <option value="Neurología">Neurología</option>
                      <option value="Pediatría">Pediatría</option>
                      <option value="Ginecología">Ginecología</option>
                      <option value="Medicina Interna">Medicina Interna</option>
                      <option value="Cirugía General">Cirugía General</option>
                      <option value="Ortopedia">Ortopedia</option>
                      <option value="Dermatología">Dermatología</option>
                    </select>
                    <div v-if="form.errors.especialidad" class="invalid-feedback">
                      {{ form.errors.especialidad }}
                    </div>
                  </div>

                  <div class="col-md-6">
                    <label for="password" class="form-label">Contraseña *</label>
                    <div class="input-group">
                      <input
                        id="password"
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        class="form-control"
                        :class="{ 'is-invalid': form.errors.password }"
                        required
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

                  <div class="col-md-6">
                    <label for="password_confirmation" class="form-label">Confirmar Contraseña *</label>
                    <input
                      id="password_confirmation"
                      v-model="form.password_confirmation"
                      type="password"
                      class="form-control"
                      required
                      placeholder="••••••••"
                    >
                  </div>

                  <div class="col-12">
                    <div class="form-check">
                      <input
                        id="terms"
                        v-model="form.terms"
                        type="checkbox"
                        class="form-check-input"
                        :class="{ 'is-invalid': form.errors.terms }"
                        required
                      >
                      <label for="terms" class="form-check-label">
                        Acepto los <a href="#" class="text-primary">términos y condiciones</a> 
                        y la <a href="#" class="text-primary">política de privacidad</a>
                      </label>
                      <div v-if="form.errors.terms" class="invalid-feedback d-block">
                        {{ form.errors.terms }}
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Submit Button -->
                <div class="d-grid mt-4">
                  <button
                    type="submit"
                    class="btn btn-primary btn-lg"
                    :disabled="form.processing"
                  >
                    <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                    <i v-else class="fas fa-user-plus me-1"></i>
                    {{ form.processing ? 'Creando cuenta...' : 'Crear Cuenta' }}
                  </button>
                </div>

                <!-- Login Link -->
                <div class="text-center mt-3">
                  <span class="text-muted">¿Ya tienes cuenta?</span>
                  <Link :href="route('login')" class="text-primary text-decoration-none ms-1">
                    Iniciar Sesión
                  </Link>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Link, useForm } from '@inertiajs/vue3'

export default {
  name: 'Register',
  components: {
    Link
  },
  setup() {
    const form = useForm({
      name: '',
      email: '',
      role: '',
      especialidad: '',
      password: '',
      password_confirmation: '',
      terms: false
    })

    return { form }
  },
  data() {
    return {
      showPassword: false
    }
  },
  methods: {
    submit() {
      this.form.post(route('register'), {
        onFinish: () => {
          this.form.reset('password', 'password_confirmation')
        },
        onSuccess: () => {
          this.$showToast('Cuenta creada exitosamente', 'success')
        },
        onError: () => {
          this.$showToast('Error al crear la cuenta', 'error')
        }
      })
    }
  }
}
</script>

<style scoped>
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

.form-control, .form-select {
  border-radius: 0.5rem;
  border: 1px solid #dee2e6;
  padding: 0.75rem 1rem;
  transition: all 0.3s ease;
}

.form-control:focus, .form-select:focus {
  border-color: #2c5aa0;
  box-shadow: 0 0 0 0.2rem rgba(44, 90, 160, 0.25);
}

.text-primary {
  color: #2c5aa0 !important;
}

.card {
  border-radius: 1rem;
}

.bg-light {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%) !important;
}
</style>
