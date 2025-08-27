<template>
  <div class="evaluar-solicitud-container">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="h3 mb-0 text-primary">
          <i class="fas fa-user-md me-2"></i>
          Evaluación de Caso Médico
        </h1>
        <p class="text-muted mb-0">
          Caso ID: #{{ solicitud.id }} - {{ solicitud.nombre_paciente }}
        </p>
      </div>
      <div class="d-flex gap-2">
        <Link
          :href="route('medico.bandeja-casos')"
          class="btn btn-outline-secondary"
        >
          <i class="fas fa-arrow-left me-1"></i>
          Volver a Bandeja
        </Link>
        <button
          class="btn btn-outline-info"
          @click="downloadCase"
        >
          <i class="fas fa-download me-1"></i>
          Descargar
        </button>
      </div>
    </div>

    <!-- Case Status Alert -->
    <div class="alert" :class="getStatusAlertClass(solicitud.estado)" role="alert">
      <div class="d-flex align-items-center">
        <i :class="getStatusIcon(solicitud.estado)" class="me-2"></i>
        <div class="flex-grow-1">
          <strong>Estado: {{ getStatusText(solicitud.estado) }}</strong>
          <div v-if="solicitud.medico_evaluador_id && solicitud.medico_evaluador_id !== $page.props.auth.user.id">
            <small>Evaluado por: {{ solicitud.medico_evaluador_nombre }}</small>
          </div>
        </div>
        <span class="badge" :class="getPriorityBadgeClass(solicitud.prioridad_ia)">
          Prioridad {{ solicitud.prioridad_ia }}
        </span>
      </div>
    </div>

    <div class="row">
      <!-- Case Details -->
      <div class="col-lg-8 mb-4">
        <!-- Patient Information -->
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="card-title mb-0">
              <i class="fas fa-user me-2"></i>
              Información del Paciente
            </h5>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-6">
                <div class="info-item mb-3">
                  <label class="form-label text-muted">Nombre Completo</label>
                  <div class="fw-semibold">{{ solicitud.nombre_paciente }}</div>
                </div>
                <div class="info-item mb-3">
                  <label class="form-label text-muted">Edad</label>
                  <div class="fw-semibold">{{ solicitud.edad_paciente }} años</div>
                </div>
                <div class="info-item mb-3">
                  <label class="form-label text-muted">Género</label>
                  <div class="fw-semibold">{{ solicitud.genero_paciente }}</div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="info-item mb-3">
                  <label class="form-label text-muted">Documento</label>
                  <div class="fw-semibold">{{ solicitud.documento_paciente }}</div>
                </div>
                <div class="info-item mb-3">
                  <label class="form-label text-muted">Teléfono</label>
                  <div class="fw-semibold">{{ solicitud.telefono_paciente || 'No especificado' }}</div>
                </div>
                <div class="info-item mb-3">
                  <label class="form-label text-muted">Dirección</label>
                  <div class="fw-semibold">{{ solicitud.direccion_paciente || 'No especificada' }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Medical Information -->
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="card-title mb-0">
              <i class="fas fa-stethoscope me-2"></i>
              Información Médica
            </h5>
          </div>
          <div class="card-body">
            <div class="info-item mb-3">
              <label class="form-label text-muted">Diagnóstico Presuntivo</label>
              <div class="fw-semibold">{{ solicitud.diagnostico_presuntivo }}</div>
            </div>
            <div class="info-item mb-3">
              <label class="form-label text-muted">Motivo de Consulta</label>
              <div class="fw-semibold">{{ solicitud.motivo_consulta }}</div>
            </div>
            <div class="info-item mb-3">
              <label class="form-label text-muted">Historia Clínica</label>
              <div class="medical-text">{{ solicitud.historia_clinica }}</div>
            </div>
            <div class="info-item mb-3">
              <label class="form-label text-muted">Exámenes Realizados</label>
              <div class="medical-text">{{ solicitud.examenes_realizados || 'No especificados' }}</div>
            </div>
          </div>
        </div>

        <!-- Request Information -->
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="card-title mb-0">
              <i class="fas fa-hospital me-2"></i>
              Información de la Solicitud
            </h5>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-6">
                <div class="info-item mb-3">
                  <label class="form-label text-muted">Institución de Origen</label>
                  <div class="fw-semibold">{{ solicitud.institucion_origen }}</div>
                </div>
                <div class="info-item mb-3">
                  <label class="form-label text-muted">Médico Solicitante</label>
                  <div class="fw-semibold">{{ solicitud.medico_solicitante }}</div>
                </div>
                <div class="info-item mb-3">
                  <label class="form-label text-muted">Especialidad Solicitada</label>
                  <div class="fw-semibold">{{ solicitud.especialidad_solicitada }}</div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="info-item mb-3">
                  <label class="form-label text-muted">Fecha de Solicitud</label>
                  <div class="fw-semibold">{{ formatDate(solicitud.fecha_recepcion_email, 'DD/MM/YYYY HH:mm') }}</div>
                </div>
                <div class="info-item mb-3">
                  <label class="form-label text-muted">Urgencia Solicitada</label>
                  <div class="fw-semibold">{{ solicitud.urgencia_solicitada || 'No especificada' }}</div>
                </div>
                <div class="info-item mb-3">
                  <label class="form-label text-muted">Observaciones</label>
                  <div class="fw-semibold">{{ solicitud.observaciones || 'Ninguna' }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Attachments -->
        <div class="card mb-4" v-if="solicitud.adjuntos && solicitud.adjuntos.length > 0">
          <div class="card-header">
            <h5 class="card-title mb-0">
              <i class="fas fa-paperclip me-2"></i>
              Archivos Adjuntos ({{ solicitud.adjuntos.length }})
            </h5>
          </div>
          <div class="card-body">
            <div class="row">
              <div
                v-for="adjunto in solicitud.adjuntos"
                :key="adjunto.id"
                class="col-md-6 mb-3"
              >
                <div class="attachment-item p-3 border rounded">
                  <div class="d-flex align-items-center">
                    <i :class="getFileIcon(adjunto.tipo)" class="me-3 fa-2x"></i>
                    <div class="flex-grow-1">
                      <div class="fw-semibold">{{ adjunto.nombre_original }}</div>
                      <small class="text-muted">{{ formatFileSize(adjunto.tamaño) }}</small>
                    </div>
                    <button
                      class="btn btn-outline-primary btn-sm"
                      @click="downloadAttachment(adjunto)"
                    >
                      <i class="fas fa-download"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Evaluation Panel -->
      <div class="col-lg-4">
        <!-- AI Analysis -->
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="card-title mb-0">
              <i class="fas fa-robot me-2"></i>
              Análisis de IA
            </h5>
          </div>
          <div class="card-body">
            <div class="ai-score mb-3">
              <label class="form-label text-muted">Puntuación de Urgencia</label>
              <div class="d-flex align-items-center">
                <div class="progress flex-grow-1 me-2" style="height: 20px;">
                  <div
                    class="progress-bar"
                    :class="getUrgencyProgressClass(solicitud.puntuacion_urgencia)"
                    :style="{ width: solicitud.puntuacion_urgencia + '%' }"
                  >
                    {{ solicitud.puntuacion_urgencia }}/100
                  </div>
                </div>
              </div>
            </div>
            <div class="info-item mb-3">
              <label class="form-label text-muted">Especialidad Sugerida</label>
              <div class="fw-semibold">{{ solicitud.especialidad_sugerida }}</div>
            </div>
            <div class="info-item mb-3">
              <label class="form-label text-muted">Resumen IA</label>
              <div class="ai-summary">{{ solicitud.resumen_ia || 'No disponible' }}</div>
            </div>
          </div>
        </div>

        <!-- Evaluation Form -->
        <div class="card" v-if="canEvaluate">
          <div class="card-header">
            <h5 class="card-title mb-0">
              <i class="fas fa-clipboard-check me-2"></i>
              Evaluación Médica
            </h5>
          </div>
          <div class="card-body">
            <form @submit.prevent="submitEvaluation">
              <div class="mb-3">
                <label class="form-label">Decisión *</label>
                <select
                  v-model="evaluationForm.decision"
                  class="form-select"
                  :class="{ 'is-invalid': evaluationForm.errors.decision }"
                  required
                >
                  <option value="">Seleccionar decisión</option>
                  <option value="aceptada">Aceptar Caso</option>
                  <option value="rechazada">Rechazar Caso</option>
                  <option value="derivada">Derivar a Otra Especialidad</option>
                </select>
                <div v-if="evaluationForm.errors.decision" class="invalid-feedback">
                  {{ evaluationForm.errors.decision }}
                </div>
              </div>

              <div class="mb-3" v-if="evaluationForm.decision === 'derivada'">
                <label class="form-label">Especialidad de Derivación *</label>
                <select
                  v-model="evaluationForm.especialidad_derivacion"
                  class="form-select"
                  required
                >
                  <option value="">Seleccionar especialidad</option>
                  <option v-for="especialidad in especialidades" :key="especialidad" :value="especialidad">
                    {{ especialidad }}
                  </option>
                </select>
              </div>

              <div class="mb-3">
                <label class="form-label">Observaciones de Evaluación *</label>
                <textarea
                  v-model="evaluationForm.observaciones"
                  class="form-control"
                  :class="{ 'is-invalid': evaluationForm.errors.observaciones }"
                  rows="4"
                  placeholder="Escriba sus observaciones sobre el caso..."
                  required
                ></textarea>
                <div v-if="evaluationForm.errors.observaciones" class="invalid-feedback">
                  {{ evaluationForm.errors.observaciones }}
                </div>
              </div>

              <div class="mb-3" v-if="evaluationForm.decision === 'aceptada'">
                <label class="form-label">Fecha de Cita Propuesta</label>
                <input
                  v-model="evaluationForm.fecha_cita"
                  type="datetime-local"
                  class="form-control"
                  :min="minDateTime"
                >
              </div>

              <div class="mb-3">
                <label class="form-label">Prioridad Médica</label>
                <select v-model="evaluationForm.prioridad_medica" class="form-select">
                  <option value="Alta">Alta</option>
                  <option value="Media">Media</option>
                  <option value="Baja">Baja</option>
                </select>
              </div>

              <div class="d-grid gap-2">
                <button
                  type="submit"
                  class="btn btn-primary"
                  :disabled="evaluationForm.processing"
                >
                  <span v-if="evaluationForm.processing" class="spinner-border spinner-border-sm me-2"></span>
                  <i v-else class="fas fa-save me-1"></i>
                  {{ evaluationForm.processing ? 'Guardando...' : 'Guardar Evaluación' }}
                </button>
                <button
                  type="button"
                  class="btn btn-outline-secondary"
                  @click="saveDraft"
                  :disabled="evaluationForm.processing"
                >
                  <i class="fas fa-file-alt me-1"></i>
                  Guardar Borrador
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Evaluation History -->
        <div class="card mt-4" v-if="solicitud.evaluaciones && solicitud.evaluaciones.length > 0">
          <div class="card-header">
            <h5 class="card-title mb-0">
              <i class="fas fa-history me-2"></i>
              Historial de Evaluaciones
            </h5>
          </div>
          <div class="card-body">
            <div
              v-for="evaluacion in solicitud.evaluaciones"
              :key="evaluacion.id"
              class="evaluation-item mb-3 p-3 border rounded"
            >
              <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="fw-semibold">{{ evaluacion.medico_nombre }}</div>
                <small class="text-muted">{{ formatDate(evaluacion.fecha_evaluacion, 'DD/MM/YYYY HH:mm') }}</small>
              </div>
              <div class="mb-2">
                <span class="badge" :class="getStatusBadgeClass(evaluacion.decision)">
                  {{ getStatusText(evaluacion.decision) }}
                </span>
              </div>
              <div class="text-muted">{{ evaluacion.observaciones }}</div>
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
  name: 'EvaluarSolicitud',
  components: {
    Link
  },
  props: {
    solicitud: {
      type: Object,
      required: true
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
      evaluationForm: {
        decision: '',
        especialidad_derivacion: '',
        observaciones: '',
        fecha_cita: '',
        prioridad_medica: 'Media',
        processing: false,
        errors: {}
      }
    }
  },
  computed: {
    canEvaluate() {
      return this.solicitud.estado === 'pendiente_evaluacion' ||
             (this.solicitud.estado === 'en_evaluacion' &&
              this.solicitud.medico_evaluador_id === this.$page.props.auth.user.id)
    },

    minDateTime() {
      const now = new Date()
      now.setHours(now.getHours() + 1) // Minimum 1 hour from now
      return now.toISOString().slice(0, 16)
    }
  },
  methods: {
    submitEvaluation() {
      this.evaluationForm.processing = true
      this.evaluationForm.errors = {}

      this.$inertia.post(route('medico.guardar-evaluacion', this.solicitud.id), this.evaluationForm, {
        onSuccess: () => {
          this.$showToast('Evaluación guardada exitosamente', 'success')
          this.$inertia.visit(route('medico.bandeja-casos'))
        },
        onError: (errors) => {
          this.evaluationForm.errors = errors
          this.$showToast('Error al guardar evaluación', 'error')
        },
        onFinish: () => {
          this.evaluationForm.processing = false
        }
      })
    },

    saveDraft() {
      this.$inertia.post(route('medico.guardar-evaluacion', this.solicitud.id), {
        ...this.evaluationForm,
        is_draft: true
      }, {
        onSuccess: () => {
          this.$showToast('Borrador guardado', 'info')
        }
      })
    },

    downloadCase() {
      window.open(route('medico.descargar-historia', this.solicitud.id), '_blank')
    },

    downloadAttachment(adjunto) {
      window.open(route('attachments.download', adjunto.id), '_blank')
    },

    getStatusAlertClass(status) {
      const classes = {
        'pendiente_evaluacion': 'alert-warning',
        'en_evaluacion': 'alert-info',
        'aceptada': 'alert-success',
        'rechazada': 'alert-danger',
        'derivada': 'alert-secondary',
        'completada': 'alert-primary'
      }
      return classes[status] || 'alert-secondary'
    },

    getStatusIcon(status) {
      const icons = {
        'pendiente_evaluacion': 'fas fa-clock',
        'en_evaluacion': 'fas fa-user-md',
        'aceptada': 'fas fa-check-circle',
        'rechazada': 'fas fa-times-circle',
        'derivada': 'fas fa-share',
        'completada': 'fas fa-flag-checkered'
      }
      return icons[status] || 'fas fa-info-circle'
    },

    getStatusText(status) {
      const texts = {
        'pendiente_evaluacion': 'Pendiente de Evaluación',
        'en_evaluacion': 'En Evaluación',
        'aceptada': 'Aceptada',
        'rechazada': 'Rechazada',
        'derivada': 'Derivada',
        'completada': 'Completada'
      }
      return texts[status] || status
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

    getPriorityBadgeClass(priority) {
      const classes = {
        'Alta': 'badge-urgent',
        'Media': 'badge-medium',
        'Baja': 'badge-low'
      }
      return classes[priority] || 'bg-secondary'
    },

    getUrgencyProgressClass(score) {
      if (score >= 80) return 'bg-danger'
      if (score >= 60) return 'bg-warning'
      if (score >= 40) return 'bg-info'
      return 'bg-success'
    },

    getFileIcon(tipo) {
      const icons = {
        'pdf': 'fas fa-file-pdf text-danger',
        'doc': 'fas fa-file-word text-primary',
        'docx': 'fas fa-file-word text-primary',
        'jpg': 'fas fa-file-image text-success',
        'jpeg': 'fas fa-file-image text-success',
        'png': 'fas fa-file-image text-success',
        'txt': 'fas fa-file-alt text-secondary'
      }
      return icons[tipo?.toLowerCase()] || 'fas fa-file text-muted'
    },

    formatDate(date, format = 'DD/MM/YYYY HH:mm') {
      if (!date) return ''
      const d = new Date(date)
      return d.toLocaleString('es-ES')
    },

    formatFileSize(bytes) {
      if (!bytes) return '0 B'
      const k = 1024
      const sizes = ['B', 'KB', 'MB', 'GB']
      const i = Math.floor(Math.log(bytes) / Math.log(k))
      return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i]
    }
  }
}
</script>

<style scoped>
.evaluar-solicitud-container {
  padding: 1rem;
}

.info-item {
  border-bottom: 1px solid #f8f9fa;
  padding-bottom: 0.5rem;
}

.medical-text {
  background: #f8f9fa;
  padding: 1rem;
  border-radius: 0.375rem;
  border-left: 4px solid #007bff;
  white-space: pre-wrap;
  font-family: 'Courier New', monospace;
  font-size: 0.9rem;
}

.ai-summary {
  background: linear-gradient(135deg, #e3f2fd, #f3e5f5);
  padding: 1rem;
  border-radius: 0.375rem;
  border-left: 4px solid #9c27b0;
  font-style: italic;
}

.ai-score .progress {
  border-radius: 10px;
}

.attachment-item {
  transition: all 0.2s ease;
}

.attachment-item:hover {
  background-color: #f8f9fa;
  transform: translateY(-1px);
}

.evaluation-item {
  background: #f8f9fa;
  transition: all 0.2s ease;
}

.evaluation-item:hover {
  background: #e9ecef;
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

@media (max-width: 768px) {
  .evaluar-solicitud-container {
    padding: 0.5rem;
  }

  .medical-text {
    font-size: 0.8rem;
    padding: 0.75rem;
  }
}
</style>
