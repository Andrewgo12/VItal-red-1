<template>
  <div class="min-vh-100 d-flex align-items-center bg-light">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
          <div class="card shadow-lg border-0">
            <div class="card-body p-5">
              <div class="text-center mb-4">
                <div class="mb-3">
                  <i class="fas fa-key fa-3x text-primary"></i>
                </div>
                <h1 class="h3 mb-1 fw-bold text-primary">Recuperar Contraseña</h1>
                <p class="text-muted">Ingresa tu email para recibir un enlace de recuperación</p>
              </div>

              <div v-if="status" class="alert alert-success" role="alert">
                {{ status }}
              </div>

              <form @submit.prevent="submit">
                <div class="mb-3">
                  <label for="email" class="form-label">Email</label>
                  <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors.email }"
                    required
                    autofocus
                    placeholder="doctor@hospital.com"
                  >
                  <div v-if="form.errors.email" class="invalid-feedback">
                    {{ form.errors.email }}
                  </div>
                </div>

                <div class="d-grid mb-3">
                  <button
                    type="submit"
                    class="btn btn-primary btn-lg"
                    :disabled="form.processing"
                  >
                    <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                    <i v-else class="fas fa-paper-plane me-1"></i>
                    {{ form.processing ? 'Enviando...' : 'Enviar Enlace' }}
                  </button>
                </div>

                <div class="text-center">
                  <Link :href="route('login')" class="text-decoration-none">
                    <i class="fas fa-arrow-left me-1"></i>
                    Volver al Login
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
  name: 'ForgotPassword',
  components: { Link },
  props: { status: String },
  setup() {
    return {
      form: useForm({ email: '' })
    }
  },
  methods: {
    submit() {
      this.form.post(route('password.email'))
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
}

.text-primary {
  color: #2c5aa0 !important;
}

.card {
  border-radius: 1rem;
}
</style>
