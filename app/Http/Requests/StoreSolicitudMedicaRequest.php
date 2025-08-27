<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSolicitudMedicaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && in_array($this->user()->role, ['medico', 'administrador']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Email information
            'email_id' => 'nullable|string|max:255',
            'email_remitente' => 'required|email|max:255',
            'asunto_email' => 'required|string|max:500',
            'contenido_email' => 'required|string',
            
            // Patient information
            'paciente_nombre' => 'required|string|max:100',
            'paciente_apellidos' => 'required|string|max:100',
            'paciente_edad' => 'required|integer|min:0|max:120',
            'paciente_sexo' => 'required|in:Masculino,Femenino',
            'paciente_identificacion' => 'nullable|string|max:50',
            
            // Medical information
            'diagnostico_principal' => 'required|string|max:500',
            'motivo_consulta' => 'required|string|max:1000',
            'antecedentes_medicos' => 'nullable|string|max:1000',
            'medicamentos_actuales' => 'nullable|string|max:1000',
            'especialidad_solicitada' => 'required|medical_specialty',
            
            // Institution information
            'institucion_remitente' => 'required|string|max:200',
            'medico_remitente' => 'nullable|string|max:200',
            'telefono_remitente' => 'nullable|colombian_phone',
            
            // AI analysis (optional, usually set by system)
            'prioridad_ia' => 'nullable|priority_level',
            'score_urgencia' => 'nullable|integer|min:0|max:100',
            'analisis_ia' => 'nullable|array',
            
            // Status
            'estado' => 'nullable|case_status',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'paciente_nombre.required' => 'El nombre del paciente es obligatorio.',
            'paciente_apellidos.required' => 'Los apellidos del paciente son obligatorios.',
            'paciente_edad.required' => 'La edad del paciente es obligatoria.',
            'paciente_edad.integer' => 'La edad debe ser un número entero.',
            'paciente_edad.min' => 'La edad no puede ser negativa.',
            'paciente_edad.max' => 'La edad no puede ser mayor a 120 años.',
            'paciente_sexo.required' => 'El sexo del paciente es obligatorio.',
            'paciente_sexo.in' => 'El sexo debe ser Masculino o Femenino.',
            
            'diagnostico_principal.required' => 'El diagnóstico principal es obligatorio.',
            'diagnostico_principal.max' => 'El diagnóstico no puede exceder 500 caracteres.',
            'motivo_consulta.required' => 'El motivo de consulta es obligatorio.',
            'motivo_consulta.max' => 'El motivo de consulta no puede exceder 1000 caracteres.',
            'especialidad_solicitada.required' => 'La especialidad solicitada es obligatoria.',
            'especialidad_solicitada.medical_specialty' => 'La especialidad seleccionada no es válida.',
            
            'institucion_remitente.required' => 'La institución remitente es obligatoria.',
            'institucion_remitente.max' => 'El nombre de la institución no puede exceder 200 caracteres.',
            
            'email_remitente.required' => 'El email del remitente es obligatorio.',
            'email_remitente.email' => 'El email del remitente debe tener un formato válido.',
            'asunto_email.required' => 'El asunto del email es obligatorio.',
            'contenido_email.required' => 'El contenido del email es obligatorio.',
            
            'telefono_remitente.colombian_phone' => 'El teléfono debe tener un formato colombiano válido.',
            'prioridad_ia.priority_level' => 'La prioridad debe ser Alta, Media o Baja.',
            'score_urgencia.integer' => 'El score de urgencia debe ser un número entero.',
            'score_urgencia.min' => 'El score de urgencia no puede ser menor a 0.',
            'score_urgencia.max' => 'El score de urgencia no puede ser mayor a 100.',
            'estado.case_status' => 'El estado del caso no es válido.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'paciente_nombre' => 'nombre del paciente',
            'paciente_apellidos' => 'apellidos del paciente',
            'paciente_edad' => 'edad del paciente',
            'paciente_sexo' => 'sexo del paciente',
            'paciente_identificacion' => 'identificación del paciente',
            'diagnostico_principal' => 'diagnóstico principal',
            'motivo_consulta' => 'motivo de consulta',
            'antecedentes_medicos' => 'antecedentes médicos',
            'medicamentos_actuales' => 'medicamentos actuales',
            'especialidad_solicitada' => 'especialidad solicitada',
            'institucion_remitente' => 'institución remitente',
            'medico_remitente' => 'médico remitente',
            'telefono_remitente' => 'teléfono del remitente',
            'email_remitente' => 'email del remitente',
            'asunto_email' => 'asunto del email',
            'contenido_email' => 'contenido del email',
            'prioridad_ia' => 'prioridad IA',
            'score_urgencia' => 'score de urgencia',
            'analisis_ia' => 'análisis IA',
            'estado' => 'estado',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        $this->merge([
            'fecha_recepcion_email' => $this->fecha_recepcion_email ?? now(),
            'estado' => $this->estado ?? 'pendiente_evaluacion',
            'score_urgencia' => $this->score_urgencia ?? 50,
            'prioridad_ia' => $this->prioridad_ia ?? 'Media',
        ]);

        // Clean and format data
        if ($this->has('paciente_nombre')) {
            $this->merge([
                'paciente_nombre' => ucwords(strtolower(trim($this->paciente_nombre)))
            ]);
        }

        if ($this->has('paciente_apellidos')) {
            $this->merge([
                'paciente_apellidos' => ucwords(strtolower(trim($this->paciente_apellidos)))
            ]);
        }

        if ($this->has('telefono_remitente')) {
            // Clean phone number
            $phone = preg_replace('/[^0-9+]/', '', $this->telefono_remitente);
            $this->merge(['telefono_remitente' => $phone]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Custom validation logic
            
            // Check if patient age is appropriate for pediatrics
            if ($this->especialidad_solicitada === 'Pediatría' && $this->paciente_edad > 17) {
                $validator->errors()->add(
                    'especialidad_solicitada',
                    'Los pacientes mayores de 17 años no pueden ser derivados a Pediatría.'
                );
            }

            // Check if urgent cases have proper justification
            if ($this->prioridad_ia === 'Alta' && strlen($this->motivo_consulta) < 50) {
                $validator->errors()->add(
                    'motivo_consulta',
                    'Los casos urgentes requieren una descripción detallada del motivo de consulta (mínimo 50 caracteres).'
                );
            }

            // Validate email domain for institutional emails
            if ($this->email_remitente) {
                $domain = substr(strrchr($this->email_remitente, "@"), 1);
                $personalDomains = ['gmail.com', 'hotmail.com', 'yahoo.com', 'outlook.com'];
                
                if (in_array(strtolower($domain), $personalDomains)) {
                    $validator->errors()->add(
                        'email_remitente',
                        'Se requiere un email institucional. Los emails personales no están permitidos.'
                    );
                }
            }
        });
    }
}
