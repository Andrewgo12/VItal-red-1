<template>
  <div class="welcome-page">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
      <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="#">
          <i class="fas fa-heartbeat me-2 text-primary fs-3"></i>
          <span class="fw-bold fs-4">Vital Red</span>
        </a>
        
        <div class="navbar-nav ms-auto">
          <Link
            v-if="canLogin"
            :href="route('login')"
            class="btn btn-outline-primary me-2"
          >
            <i class="fas fa-sign-in-alt me-1"></i>
            Iniciar Sesión
          </Link>
          <Link
            v-if="canRegister"
            :href="route('register')"
            class="btn btn-primary"
          >
            <i class="fas fa-user-plus me-1"></i>
            Registrarse
          </Link>
        </div>
      </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
      <div class="container">
        <div class="row align-items-center min-vh-100 py-5">
          <div class="col-lg-6">
            <div class="hero-content">
              <h1 class="display-4 fw-bold text-primary mb-4">
                Sistema de Gestión Médica
                <span class="text-secondary">con Inteligencia Artificial</span>
              </h1>
              <p class="lead text-muted mb-4">
                Optimiza la gestión de solicitudes médicas con análisis automático, 
                clasificación inteligente por urgencia y procesamiento de emails en tiempo real.
              </p>
              
              <div class="d-flex flex-wrap gap-3 mb-4">
                <div class="feature-badge">
                  <i class="fas fa-robot text-primary me-2"></i>
                  IA Avanzada
                </div>
                <div class="feature-badge">
                  <i class="fas fa-envelope text-primary me-2"></i>
                  Gmail Integrado
                </div>
                <div class="feature-badge">
                  <i class="fas fa-chart-line text-primary me-2"></i>
                  Analytics
                </div>
                <div class="feature-badge">
                  <i class="fas fa-shield-alt text-primary me-2"></i>
                  Seguro
                </div>
              </div>

              <div class="cta-buttons">
                <Link
                  v-if="canLogin"
                  :href="route('login')"
                  class="btn btn-primary btn-lg me-3"
                >
                  <i class="fas fa-play me-2"></i>
                  Comenzar Ahora
                </Link>
                <a href="#features" class="btn btn-outline-secondary btn-lg">
                  <i class="fas fa-info-circle me-2"></i>
                  Conocer Más
                </a>
              </div>
            </div>
          </div>
          
          <div class="col-lg-6">
            <div class="hero-image text-center">
              <div class="dashboard-preview">
                <div class="preview-header">
                  <div class="preview-dots">
                    <span></span>
                    <span></span>
                    <span></span>
                  </div>
                </div>
                <div class="preview-content">
                  <div class="preview-stats">
                    <div class="stat-card urgent">
                      <i class="fas fa-exclamation-triangle"></i>
                      <span>5 Casos Urgentes</span>
                    </div>
                    <div class="stat-card">
                      <i class="fas fa-file-medical"></i>
                      <span>23 Casos Totales</span>
                    </div>
                    <div class="stat-card">
                      <i class="fas fa-clock"></i>
                      <span>2.5h Promedio</span>
                    </div>
                  </div>
                  <div class="preview-chart">
                    <div class="chart-bars">
                      <div class="bar" style="height: 60%"></div>
                      <div class="bar" style="height: 80%"></div>
                      <div class="bar" style="height: 45%"></div>
                      <div class="bar" style="height: 90%"></div>
                      <div class="bar" style="height: 70%"></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features-section py-5 bg-light">
      <div class="container">
        <div class="text-center mb-5">
          <h2 class="display-5 fw-bold text-primary">Características Principales</h2>
          <p class="lead text-muted">Tecnología avanzada para la gestión médica moderna</p>
        </div>

        <div class="row g-4">
          <div class="col-md-6 col-lg-4">
            <div class="feature-card h-100">
              <div class="feature-icon">
                <i class="fas fa-brain"></i>
              </div>
              <h4>Inteligencia Artificial</h4>
              <p>Análisis automático de casos médicos con Google Gemini AI para clasificación por urgencia y especialidad.</p>
            </div>
          </div>

          <div class="col-md-6 col-lg-4">
            <div class="feature-card h-100">
              <div class="feature-icon">
                <i class="fas fa-envelope-open-text"></i>
              </div>
              <h4>Procesamiento de Emails</h4>
              <p>Integración con Gmail para procesamiento automático de solicitudes médicas recibidas por correo.</p>
            </div>
          </div>

          <div class="col-md-6 col-lg-4">
            <div class="feature-card h-100">
              <div class="feature-icon">
                <i class="fas fa-tachometer-alt"></i>
              </div>
              <h4>Dashboard en Tiempo Real</h4>
              <p>Métricas y estadísticas actualizadas en tiempo real con gráficos interactivos y alertas.</p>
            </div>
          </div>

          <div class="col-md-6 col-lg-4">
            <div class="feature-card h-100">
              <div class="feature-icon">
                <i class="fas fa-bell"></i>
              </div>
              <h4>Notificaciones Inteligentes</h4>
              <p>Sistema de alertas automáticas para casos urgentes con múltiples canales de notificación.</p>
            </div>
          </div>

          <div class="col-md-6 col-lg-4">
            <div class="feature-card h-100">
              <div class="feature-icon">
                <i class="fas fa-chart-bar"></i>
              </div>
              <h4>Reportes Avanzados</h4>
              <p>Generación automática de reportes con análisis de tendencias y métricas de rendimiento.</p>
            </div>
          </div>

          <div class="col-md-6 col-lg-4">
            <div class="feature-card h-100">
              <div class="feature-icon">
                <i class="fas fa-lock"></i>
              </div>
              <h4>Seguridad Médica</h4>
              <p>Cumplimiento con estándares HIPAA y encriptación de datos sensibles para máxima seguridad.</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section py-5">
      <div class="container">
        <div class="row text-center">
          <div class="col-md-3 col-6 mb-4">
            <div class="stat-item">
              <div class="stat-number">95%</div>
              <div class="stat-label">Precisión IA</div>
            </div>
          </div>
          <div class="col-md-3 col-6 mb-4">
            <div class="stat-item">
              <div class="stat-number">2.5h</div>
              <div class="stat-label">Tiempo Promedio</div>
            </div>
          </div>
          <div class="col-md-3 col-6 mb-4">
            <div class="stat-item">
              <div class="stat-number">24/7</div>
              <div class="stat-label">Monitoreo</div>
            </div>
          </div>
          <div class="col-md-3 col-6 mb-4">
            <div class="stat-item">
              <div class="stat-number">100%</div>
              <div class="stat-label">Seguro</div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="footer bg-dark text-white py-4">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-md-6">
            <div class="d-flex align-items-center">
              <i class="fas fa-heartbeat me-2 text-primary"></i>
              <span class="fw-bold">Vital Red</span>
              <span class="ms-2 text-muted">© 2024</span>
            </div>
          </div>
          <div class="col-md-6 text-md-end">
            <small class="text-muted">
              Sistema de Gestión Médica con Inteligencia Artificial
            </small>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>

<script>
import { Link } from '@inertiajs/vue3'

export default {
  name: 'Welcome',
  components: {
    Link
  },
  props: {
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: String,
    phpVersion: String
  }
}
</script>

<style scoped>
.welcome-page {
  font-family: 'Inter', sans-serif;
}

.hero-section {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  min-height: 100vh;
  display: flex;
  align-items: center;
}

.feature-badge {
  background: rgba(44, 90, 160, 0.1);
  color: #2c5aa0;
  padding: 0.5rem 1rem;
  border-radius: 2rem;
  font-size: 0.875rem;
  font-weight: 500;
}

.dashboard-preview {
  background: white;
  border-radius: 1rem;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  max-width: 400px;
  margin: 0 auto;
}

.preview-header {
  background: #f8f9fa;
  padding: 1rem;
  border-bottom: 1px solid #dee2e6;
}

.preview-dots {
  display: flex;
  gap: 0.5rem;
}

.preview-dots span {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: #dee2e6;
}

.preview-dots span:first-child {
  background: #dc3545;
}

.preview-dots span:nth-child(2) {
  background: #ffc107;
}

.preview-dots span:last-child {
  background: #28a745;
}

.preview-content {
  padding: 1.5rem;
}

.preview-stats {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
}

.stat-card {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  background: #f8f9fa;
  border-radius: 0.5rem;
  font-size: 0.875rem;
}

.stat-card.urgent {
  background: rgba(220, 53, 69, 0.1);
  color: #dc3545;
}

.stat-card i {
  font-size: 1.25rem;
}

.preview-chart {
  height: 100px;
  background: #f8f9fa;
  border-radius: 0.5rem;
  padding: 1rem;
  display: flex;
  align-items: end;
}

.chart-bars {
  display: flex;
  gap: 0.5rem;
  align-items: end;
  width: 100%;
  height: 100%;
}

.bar {
  background: linear-gradient(to top, #2c5aa0, #4dabf7);
  border-radius: 0.25rem;
  flex: 1;
  min-height: 20%;
  animation: growUp 1s ease-out;
}

@keyframes growUp {
  from {
    height: 0;
  }
  to {
    height: var(--height);
  }
}

.feature-card {
  background: white;
  padding: 2rem;
  border-radius: 1rem;
  text-align: center;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.feature-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.feature-icon {
  width: 80px;
  height: 80px;
  background: linear-gradient(135deg, #2c5aa0, #4dabf7);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.5rem;
  color: white;
  font-size: 2rem;
}

.feature-card h4 {
  color: #2c5aa0;
  margin-bottom: 1rem;
  font-weight: 600;
}

.stat-item {
  padding: 1rem;
}

.stat-number {
  font-size: 3rem;
  font-weight: 700;
  color: #2c5aa0;
  line-height: 1;
}

.stat-label {
  color: #6c757d;
  font-weight: 500;
  margin-top: 0.5rem;
}

.text-primary {
  color: #2c5aa0 !important;
}

.btn-primary {
  background: linear-gradient(135deg, #2c5aa0 0%, #1e3d6f 100%);
  border: none;
  font-weight: 600;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(44, 90, 160, 0.3);
}

@media (max-width: 768px) {
  .hero-content {
    text-align: center;
    margin-bottom: 3rem;
  }
  
  .cta-buttons {
    flex-direction: column;
    gap: 1rem;
  }
  
  .cta-buttons .btn {
    width: 100%;
  }
}
</style>
