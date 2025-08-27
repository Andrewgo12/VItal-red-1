<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\SolicitudMedica;

class UpdateSolicitudMedicaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $solicitud = $this->route('solicitud_medica') ?? $this->route('solicitud');
        
        if (!$solicitud || !$this->user()) {
            return false;
        }

        // Administrators can update any solicitud
        if ($this->user()->role === 'administrador') {
            return true;
        }

        // Doctors can only update solicitudes in their specialty or assigned to them
        if ($this->user()->role === 'medico') {
            // Check if assigned to this doctor
            if ($solicitud->medico_evaluador_id === $this->user()->id) {
                return true;
            }

            // Check if specialty matches
            if ($this->user()->specialties && 
                in_array($solicitud->especialidad_solicitada, $this->user()->specialties)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $solicitud = $this->route('solicitud_medica') ?? $this->route('solicitud');
        
        return [
            // Patient information (limited updates)
            'paciente_nombre' => 'sometimes|string|max:100',
            'paciente_apellidos' => 'sometimes|string|max:100',
            'paciente_edad' => 'sometimes|integer|min:0|max:120',
            'paciente_sexo' => 'sometimes|in:Masculino,Femenino',
            'paciente_identificacion' => 'sometimes|nullable|string|max:50',
            
            // Medical information updates
            'diagnostico_principal' => 'sometimes|string|max:500',
            'motivo_consulta' => 'sometimes|string|max:1000',
            'antecedentes_medicos' => 'sometimes|nullable|string|max:1000',
            'medicamentos_actuales' => 'sometimes|nullable|string|max:1000',
            'especialidad_solicitada' => 'sometimes|medical_specialty',
            
            // Medical evaluation
            'estado' => 'sometimes|case_status',
            'observaciones_medico' => 'sometimes|nullable|string|max:2000',
            'prioridad_medico' => 'sometimes|nullable|priority_level',
            'fecha_cita_propuesta' => 'sometimes|nullable|date|after:now',
            'medico_evaluador_id' => 'sometimes|nullable|exists:users,id',
            
            // AI updates (admin only)
            'prioridad_ia' => 'sometimes|priority_level',
            'score_urgencia' => 'sometimes|integer|min:0|max:100',
            'analisis_ia' => 'sometimes|nullable|array',
            
            // Institution information
            'institucion_remitente' => 'sometimes|string|max:200',
            'medico_remitente' => 'sometimes|nullable|string|max:200',
            'telefono_remitente' => 'sometimes|nullable|colombian_phone',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'paciente_edad.integer' => 'La edad debe ser un número entero.',
            'paciente_edad.min' => 'La edad no puede ser negativa.',
            'paciente_edad.max' => 'La edad no puede ser mayor a 120 años.',
            'paciente_sexo.in' => 'El sexo debe ser Masculino o Femenino.',
            
            'diagnostico_principal.max' => 'El diagnóstico no puede exceder 500 caracteres.',
            'motivo_consulta.max' => 'El motivo de consulta no puede exceder 1000 caracteres.',
            'antecedentes_medicos.max' => 'Los antecedentes médicos no pueden exceder 1000 caracteres.',
            'medicamentos_actuales.max' => 'Los medicamentos actuales no pueden exceder 1000 caracteres.',
            'especialidad_solicitada.medical_specialty' => 'La especialidad seleccionada no es válida.',
            
            'estado.case_status' => 'El estado del caso no es válido.',
            'observaciones_medico.max' => 'Las observaciones no pueden exceder 2000 caracteres.',
            'prioridad_medico.priority_level' => 'La prioridad debe ser Alta, Media o Baja.',
            'fecha_cita_propuesta.date' => 'La fecha de cita debe ser una fecha válida.',
            'fecha_cita_propuesta.after' => 'La fecha de cita debe ser posterior a la fecha actual.',
            'medico_evaluador_id.exists' => 'El médico evaluador seleccionado no existe.',
            
            'prioridad_ia.priority_level' => 'La prioridad IA debe ser Alta, Media o Baja.',
            'score_urgencia.integer' => 'El score de urgencia debe ser un número entero.',
            'score_urgencia.min' => 'El score de urgencia no puede ser menor a 0.',
            'score_urgencia.max' => 'El score de urgencia no puede ser mayor a 100.',
            
            'institucion_remitente.max' => 'El nombre de la institución no puede exceder 200 caracteres.',
            'medico_remitente.max' => 'El nombre del médico remitente no puede exceder 200 caracteres.',
            'telefono_remitente.colombian_phone' => 'El teléfono debe tener un formato colombiano válido.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $solicitud = $this->route('solicitud_medica') ?? $this->route('solicitud');
            
            // Prevent updates to completed cases unless admin
            if ($solicitud && $solicitud->estado === 'completada' && $this->user()->role !== 'administrador') {
                $validator->errors()->add(
                    'estado',
                    'No se pueden modificar casos completados.'
                );
            }

            // Validate state transitions
            if ($this->has('estado') && $solicitud) {
                $this->validateStateTransition($validator, $solicitud->estado, $this->estado);
            }

            // Validate medical evaluator assignment
            if ($this->has('medico_evaluador_id') && $this->medico_evaluador_id) {
                $this->validateMedicoAssignment($validator);
            }

            // Validate required fields for certain states
            if ($this->has('estado')) {
                $this->validateRequiredFieldsForState($validator);
            }

            // Check if patient age is appropriate for specialty change
            if ($this->has('especialidad_solicitada') && $solicitud) {
                if ($this->especialidad_solicitada === 'Pediatría' && $solicitud->paciente_edad > 17) {
                    $validator->errors()->add(
                        'especialidad_solicitada',
                        'Los pacientes mayores de 17 años no pueden ser derivados a Pediatría.'
                    );
                }
            }
        });
    }

    /**
     * Validate state transitions
     */
    private function validateStateTransition($validator, $currentState, $newState): void
    {
        $validTransitions = [
            'pendiente_evaluacion' => ['en_evaluacion', 'aceptada', 'rechazada', 'derivada'],
            'en_evaluacion' => ['aceptada', 'rechazada', 'derivada', 'pendiente_evaluacion'],
            'aceptada' => ['completada', 'en_evaluacion'],
            'rechazada' => ['en_evaluacion', 'pendiente_evaluacion'],
            'derivada' => ['pendiente_evaluacion'],
            'completada' => [], // No transitions allowed from completed
        ];

        if (!isset($validTransitions[$currentState]) || 
            !in_array($newState, $validTransitions[$currentState])) {
            
            $validator->errors()->add(
                'estado',
                "No se puede cambiar el estado de '{$currentState}' a '{$newState}'."
            );
        }
    }

    /**
     * Validate medical evaluator assignment
     */
    private function validateMedicoAssignment($validator): void
    {
        $medico = \App\Models\User::find($this->medico_evaluador_id);
        
        if (!$medico || $medico->role !== 'medico' || !$medico->is_active) {
            $validator->errors()->add(
                'medico_evaluador_id',
                'El médico seleccionado no es válido o no está activo.'
            );
            return;
        }

        // Check if medico has the required specialty
        if ($this->has('especialidad_solicitada') || 
            ($solicitud = $this->route('solicitud_medica') ?? $this->route('solicitud'))) {
            
            $especialidad = $this->especialidad_solicitada ?? $solicitud->especialidad_solicitada;
            
            if ($medico->specialties && !in_array($especialidad, $medico->specialties)) {
                $validator->errors()->add(
                    'medico_evaluador_id',
                    "El médico seleccionado no tiene la especialidad requerida: {$especialidad}."
                );
            }
        }
    }

    /**
     * Validate required fields for specific states
     */
    private function validateRequiredFieldsForState($validator): void
    {
        switch ($this->estado) {
            case 'aceptada':
                if (!$this->has('observaciones_medico') || empty($this->observaciones_medico)) {
                    $validator->errors()->add(
                        'observaciones_medico',
                        'Las observaciones médicas son obligatorias para casos aceptados.'
                    );
                }
                if (!$this->has('prioridad_medico') || empty($this->prioridad_medico)) {
                    $validator->errors()->add(
                        'prioridad_medico',
                        'La prioridad médica es obligatoria para casos aceptados.'
                    );
                }
                break;

            case 'rechazada':
                if (!$this->has('observaciones_medico') || empty($this->observaciones_medico)) {
                    $validator->errors()->add(
                        'observaciones_medico',
                        'Las observaciones médicas son obligatorias para casos rechazados.'
                    );
                }
                break;

            case 'derivada':
                if (!$this->has('observaciones_medico') || empty($this->observaciones_medico)) {
                    $validator->errors()->add(
                        'observaciones_medico',
                        'Las observaciones médicas son obligatorias para casos derivados.'
                    );
                }
                break;
        }
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set evaluation timestamp when changing to evaluated states
        if ($this->has('estado') && 
            in_array($this->estado, ['aceptada', 'rechazada', 'derivada']) &&
            !$this->has('fecha_evaluacion')) {
            
            $this->merge(['fecha_evaluacion' => now()]);
        }

        // Set evaluator if not set and user is a medico
        if ($this->has('estado') && 
            in_array($this->estado, ['en_evaluacion', 'aceptada', 'rechazada', 'derivada']) &&
            !$this->has('medico_evaluador_id') &&
            $this->user()->role === 'medico') {
            
            $this->merge(['medico_evaluador_id' => $this->user()->id]);
        }

        // Clean phone number if provided
        if ($this->has('telefono_remitente') && $this->telefono_remitente) {
            $phone = preg_replace('/[^0-9+]/', '', $this->telefono_remitente);
            $this->merge(['telefono_remitente' => $phone]);
        }
    }
}
