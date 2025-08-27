<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'administrador';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => 'required|in:medico,administrador',
            'department' => 'nullable|string|max:100',
            'phone' => 'nullable|colombian_phone',
            'medical_license' => 'nullable|medical_license',
            'specialties' => 'nullable|array',
            'specialties.*' => 'medical_specialty',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede exceder 255 caracteres.',
            
            'email.required' => 'El email es obligatorio.',
            'email.email' => 'El email debe tener un formato válido.',
            'email.unique' => 'Este email ya está registrado en el sistema.',
            'email.max' => 'El email no puede exceder 255 caracteres.',
            
            'password.required' => 'La contraseña es obligatoria.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
            
            'role.required' => 'El rol es obligatorio.',
            'role.in' => 'El rol debe ser médico o administrador.',
            
            'department.max' => 'El departamento no puede exceder 100 caracteres.',
            
            'phone.colombian_phone' => 'El teléfono debe tener un formato colombiano válido (+57XXXXXXXXXX).',
            
            'medical_license.medical_license' => 'La licencia médica debe tener el formato MP-XXXXX.',
            
            'specialties.array' => 'Las especialidades deben ser un arreglo.',
            'specialties.*.medical_specialty' => 'Una o más especialidades seleccionadas no son válidas.',
            
            'is_active.boolean' => 'El estado activo debe ser verdadero o falso.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'email' => 'correo electrónico',
            'password' => 'contraseña',
            'role' => 'rol',
            'department' => 'departamento',
            'phone' => 'teléfono',
            'medical_license' => 'licencia médica',
            'specialties' => 'especialidades',
            'is_active' => 'estado activo',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Medical license is required for medicos
            if ($this->role === 'medico' && empty($this->medical_license)) {
                $validator->errors()->add(
                    'medical_license',
                    'La licencia médica es obligatoria para médicos.'
                );
            }

            // Specialties are required for medicos
            if ($this->role === 'medico' && (empty($this->specialties) || count($this->specialties) === 0)) {
                $validator->errors()->add(
                    'specialties',
                    'Al menos una especialidad es obligatoria para médicos.'
                );
            }

            // Administrators shouldn't have medical license or specialties
            if ($this->role === 'administrador') {
                if (!empty($this->medical_license)) {
                    $validator->errors()->add(
                        'medical_license',
                        'Los administradores no deben tener licencia médica.'
                    );
                }

                if (!empty($this->specialties) && count($this->specialties) > 0) {
                    $validator->errors()->add(
                        'specialties',
                        'Los administradores no deben tener especialidades médicas.'
                    );
                }
            }

            // Validate email domain for institutional requirements
            if ($this->email) {
                $domain = substr(strrchr($this->email, "@"), 1);
                $personalDomains = ['gmail.com', 'hotmail.com', 'yahoo.com', 'outlook.com'];
                
                if (in_array(strtolower($domain), $personalDomains)) {
                    $validator->errors()->add(
                        'email',
                        'Se requiere un email institucional. Los emails personales no están permitidos para el personal médico.'
                    );
                }
            }

            // Validate department consistency with role
            if ($this->role === 'administrador' && $this->department && $this->department !== 'Administración') {
                $validator->errors()->add(
                    'department',
                    'Los administradores deben pertenecer al departamento de Administración.'
                );
            }

            // Check for duplicate medical license
            if ($this->medical_license) {
                $existingUser = \App\Models\User::where('medical_license', $this->medical_license)->first();
                if ($existingUser) {
                    $validator->errors()->add(
                        'medical_license',
                        'Esta licencia médica ya está registrada en el sistema.'
                    );
                }
            }
        });
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        $this->merge([
            'is_active' => $this->is_active ?? true,
        ]);

        // Clean and format data
        if ($this->has('name')) {
            $this->merge([
                'name' => ucwords(strtolower(trim($this->name)))
            ]);
        }

        if ($this->has('email')) {
            $this->merge([
                'email' => strtolower(trim($this->email))
            ]);
        }

        if ($this->has('phone')) {
            // Clean phone number
            $phone = preg_replace('/[^0-9+]/', '', $this->phone);
            $this->merge(['phone' => $phone]);
        }

        if ($this->has('medical_license')) {
            // Format medical license
            $license = strtoupper(trim($this->medical_license));
            $this->merge(['medical_license' => $license]);
        }

        if ($this->has('department')) {
            $this->merge([
                'department' => ucwords(strtolower(trim($this->department)))
            ]);
        }

        // Set default department based on role
        if ($this->role === 'administrador' && !$this->has('department')) {
            $this->merge(['department' => 'Administración']);
        }

        // Clear medical fields for administrators
        if ($this->role === 'administrador') {
            $this->merge([
                'medical_license' => null,
                'specialties' => null,
            ]);
        }
    }
}
