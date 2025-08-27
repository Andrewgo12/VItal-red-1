<?php

namespace Database\Factories;

use App\Models\SolicitudMedica;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SolicitudMedica>
 */
class SolicitudMedicaFactory extends Factory
{
    protected $model = SolicitudMedica::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $especialidades = [
            'Medicina General',
            'Cardiología',
            'Neurología',
            'Ortopedia',
            'Pediatría',
            'Ginecología',
            'Urología',
            'Oftalmología',
            'Dermatología',
            'Psiquiatría',
            'Medicina Interna'
        ];

        $prioridades = ['Alta', 'Media', 'Baja'];
        $estados = [
            'pendiente_evaluacion',
            'en_evaluacion',
            'aceptada',
            'rechazada',
            'derivada',
            'completada'
        ];

        $sexos = ['Masculino', 'Femenino'];

        $diagnosticos = [
            'Hipertensión arterial',
            'Diabetes mellitus tipo 2',
            'Dolor torácico',
            'Cefalea',
            'Dolor abdominal',
            'Disnea',
            'Fiebre',
            'Dolor lumbar',
            'Artritis',
            'Gastritis',
            'Bronquitis',
            'Dermatitis',
            'Ansiedad',
            'Depresión',
            'Migraña'
        ];

        $instituciones = [
            'Hospital General',
            'Clínica San José',
            'Hospital Universitario',
            'Centro Médico Nacional',
            'Clínica del Country',
            'Hospital Santa Fe',
            'IPS Salud Total',
            'Clínica Reina Sofía',
            'Hospital San Vicente',
            'Centro de Salud Municipal'
        ];

        $nombres = [
            'Juan', 'María', 'Carlos', 'Ana', 'Luis', 'Carmen', 'José', 'Laura',
            'Miguel', 'Isabel', 'Antonio', 'Patricia', 'Francisco', 'Rosa',
            'Manuel', 'Dolores', 'David', 'Pilar', 'Jesús', 'Teresa'
        ];

        $apellidos = [
            'García', 'Rodríguez', 'González', 'Fernández', 'López', 'Martínez',
            'Sánchez', 'Pérez', 'Gómez', 'Martín', 'Jiménez', 'Ruiz',
            'Hernández', 'Díaz', 'Moreno', 'Muñoz', 'Álvarez', 'Romero'
        ];

        $fechaRecepcion = $this->faker->dateTimeBetween('-30 days', 'now');
        $scoreUrgencia = $this->faker->numberBetween(10, 100);
        
        // Determine priority based on score
        $prioridad = $scoreUrgencia >= 80 ? 'Alta' : ($scoreUrgencia >= 50 ? 'Media' : 'Baja');

        return [
            'email_id' => $this->faker->unique()->uuid(),
            'fecha_recepcion_email' => $fechaRecepcion,
            'email_remitente' => $this->faker->email(),
            'asunto_email' => 'Solicitud médica - ' . $this->faker->words(3, true),
            'contenido_email' => $this->faker->paragraph(5),
            
            // Patient information
            'paciente_nombre' => $this->faker->randomElement($nombres),
            'paciente_apellidos' => $this->faker->randomElement($apellidos) . ' ' . $this->faker->randomElement($apellidos),
            'paciente_edad' => $this->faker->numberBetween(1, 95),
            'paciente_sexo' => $this->faker->randomElement($sexos),
            'paciente_identificacion' => $this->faker->numerify('########'),
            
            // Medical information
            'diagnostico_principal' => $this->faker->randomElement($diagnosticos),
            'motivo_consulta' => $this->faker->sentence(8),
            'antecedentes_medicos' => $this->faker->optional(0.7)->sentence(6),
            'medicamentos_actuales' => $this->faker->optional(0.6)->sentence(4),
            'especialidad_solicitada' => $this->faker->randomElement($especialidades),
            
            // Institution information
            'institucion_remitente' => $this->faker->randomElement($instituciones),
            'medico_remitente' => 'Dr. ' . $this->faker->name(),
            'telefono_remitente' => $this->faker->optional(0.8)->phoneNumber(),
            
            // AI analysis
            'prioridad_ia' => $prioridad,
            'score_urgencia' => $scoreUrgencia,
            'analisis_ia' => [
                'confidence' => $this->faker->randomFloat(2, 0.5, 1.0),
                'keywords' => $this->faker->words(5),
                'risk_factors' => $this->faker->words(3),
                'recommendations' => $this->faker->sentence(),
                'processing_time' => $this->faker->randomFloat(2, 0.1, 2.0)
            ],
            
            // Status
            'estado' => $this->faker->randomElement($estados),
            'medico_evaluador_id' => $this->faker->optional(0.4)->randomElement(
                User::where('role', 'medico')->pluck('id')->toArray() ?: [null]
            ),
            'fecha_evaluacion' => function (array $attributes) {
                return $attributes['medico_evaluador_id'] 
                    ? $this->faker->dateTimeBetween($attributes['fecha_recepcion_email'], 'now')
                    : null;
            },
            'observaciones_medico' => function (array $attributes) {
                return $attributes['medico_evaluador_id'] 
                    ? $this->faker->optional(0.8)->paragraph(2)
                    : null;
            },
            'prioridad_medico' => function (array $attributes) {
                return $attributes['medico_evaluador_id'] 
                    ? $this->faker->randomElement($prioridades)
                    : null;
            },
            'fecha_cita_propuesta' => function (array $attributes) {
                return $attributes['estado'] === 'aceptada'
                    ? $this->faker->dateTimeBetween('now', '+30 days')
                    : null;
            },
        ];
    }

    /**
     * Indicate that the solicitud is urgent.
     */
    public function urgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'prioridad_ia' => 'Alta',
            'score_urgencia' => $this->faker->numberBetween(80, 100),
        ]);
    }

    /**
     * Indicate that the solicitud is pending evaluation.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'pendiente_evaluacion',
            'medico_evaluador_id' => null,
            'fecha_evaluacion' => null,
            'observaciones_medico' => null,
            'prioridad_medico' => null,
        ]);
    }

    /**
     * Indicate that the solicitud has been evaluated.
     */
    public function evaluated(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => $this->faker->randomElement(['aceptada', 'rechazada', 'derivada']),
            'medico_evaluador_id' => User::factory()->medico(),
            'fecha_evaluacion' => $this->faker->dateTimeBetween($attributes['fecha_recepcion_email'], 'now'),
            'observaciones_medico' => $this->faker->paragraph(2),
            'prioridad_medico' => $this->faker->randomElement(['Alta', 'Media', 'Baja']),
        ]);
    }

    /**
     * Indicate that the solicitud is for cardiology.
     */
    public function cardiology(): static
    {
        return $this->state(fn (array $attributes) => [
            'especialidad_solicitada' => 'Cardiología',
            'diagnostico_principal' => $this->faker->randomElement([
                'Dolor torácico',
                'Hipertensión arterial',
                'Arritmia cardíaca',
                'Insuficiencia cardíaca',
                'Infarto agudo de miocardio'
            ]),
        ]);
    }

    /**
     * Indicate that the solicitud is for neurology.
     */
    public function neurology(): static
    {
        return $this->state(fn (array $attributes) => [
            'especialidad_solicitada' => 'Neurología',
            'diagnostico_principal' => $this->faker->randomElement([
                'Cefalea',
                'Migraña',
                'Epilepsia',
                'Accidente cerebrovascular',
                'Parkinson'
            ]),
        ]);
    }

    /**
     * Indicate that the solicitud is for pediatrics.
     */
    public function pediatrics(): static
    {
        return $this->state(fn (array $attributes) => [
            'especialidad_solicitada' => 'Pediatría',
            'paciente_edad' => $this->faker->numberBetween(0, 17),
            'diagnostico_principal' => $this->faker->randomElement([
                'Fiebre',
                'Bronquitis',
                'Gastroenteritis',
                'Otitis media',
                'Dermatitis atópica'
            ]),
        ]);
    }

    /**
     * Create a solicitud from today.
     */
    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'fecha_recepcion_email' => $this->faker->dateTimeBetween('today', 'now'),
        ]);
    }

    /**
     * Create a solicitud from this week.
     */
    public function thisWeek(): static
    {
        return $this->state(fn (array $attributes) => [
            'fecha_recepcion_email' => $this->faker->dateTimeBetween('-7 days', 'now'),
        ]);
    }
}
