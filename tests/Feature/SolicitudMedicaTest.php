<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\SolicitudMedica;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SolicitudMedicaTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $admin;
    protected $medico;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        $this->admin = User::factory()->create([
            'role' => 'administrador',
            'email' => 'admin@test.com'
        ]);

        $this->medico = User::factory()->create([
            'role' => 'medico',
            'email' => 'medico@test.com',
            'specialties' => ['Cardiología', 'Medicina Interna']
        ]);
    }

    public function test_admin_can_view_all_solicitudes()
    {
        // Create test solicitudes
        SolicitudMedica::factory()->count(5)->create();

        $response = $this->actingAs($this->admin)
            ->get('/api/solicitudes-medicas');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'paciente_nombre',
                            'diagnostico_principal',
                            'especialidad_solicitada',
                            'prioridad_ia',
                            'estado'
                        ]
                    ]
                ]
            ]);
    }

    public function test_medico_can_view_assigned_solicitudes()
    {
        // Create solicitudes for the medico's specialty
        SolicitudMedica::factory()->create([
            'especialidad_solicitada' => 'Cardiología',
            'estado' => 'pendiente_evaluacion'
        ]);

        SolicitudMedica::factory()->create([
            'especialidad_solicitada' => 'Neurología', // Different specialty
            'estado' => 'pendiente_evaluacion'
        ]);

        $response = $this->actingAs($this->medico)
            ->get('/api/solicitudes-medicas');

        $response->assertStatus(200);
        
        $data = $response->json('data.data');
        $this->assertCount(1, $data); // Should only see Cardiología case
    }

    public function test_can_create_solicitud_medica()
    {
        $solicitudData = [
            'paciente_nombre' => 'Juan',
            'paciente_apellidos' => 'Pérez',
            'paciente_edad' => 45,
            'paciente_sexo' => 'Masculino',
            'diagnostico_principal' => 'Dolor torácico',
            'especialidad_solicitada' => 'Cardiología',
            'motivo_consulta' => 'Dolor torácico de 2 horas de evolución',
            'institucion_remitente' => 'Hospital Central',
            'medico_remitente' => 'Dr. Ana Martínez'
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/solicitudes-medicas', $solicitudData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'paciente_nombre',
                    'diagnostico_principal'
                ]
            ]);

        $this->assertDatabaseHas('solicitudes_medicas', [
            'paciente_nombre' => 'Juan',
            'paciente_apellidos' => 'Pérez',
            'diagnostico_principal' => 'Dolor torácico'
        ]);
    }

    public function test_can_evaluate_solicitud()
    {
        $solicitud = SolicitudMedica::factory()->create([
            'especialidad_solicitada' => 'Cardiología',
            'estado' => 'pendiente_evaluacion'
        ]);

        $evaluationData = [
            'decision' => 'aceptada',
            'observaciones' => 'Caso aceptado para evaluación cardiológica',
            'prioridad_medico' => 'Alta',
            'fecha_cita_propuesta' => now()->addDays(3)->toISOString()
        ];

        $response = $this->actingAs($this->medico)
            ->putJson("/api/solicitudes-medicas/{$solicitud->id}/evaluar", $evaluationData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('solicitudes_medicas', [
            'id' => $solicitud->id,
            'estado' => 'aceptada',
            'medico_evaluador_id' => $this->medico->id,
            'observaciones_medico' => 'Caso aceptado para evaluación cardiológica'
        ]);
    }

    public function test_cannot_evaluate_solicitud_from_different_specialty()
    {
        $solicitud = SolicitudMedica::factory()->create([
            'especialidad_solicitada' => 'Neurología', // Different from medico's specialty
            'estado' => 'pendiente_evaluacion'
        ]);

        $evaluationData = [
            'decision' => 'aceptada',
            'observaciones' => 'Test evaluation'
        ];

        $response = $this->actingAs($this->medico)
            ->putJson("/api/solicitudes-medicas/{$solicitud->id}/evaluar", $evaluationData);

        $response->assertStatus(403);
    }

    public function test_validation_errors_on_create()
    {
        $invalidData = [
            'paciente_nombre' => '', // Required field empty
            'paciente_edad' => 150, // Invalid age
            'especialidad_solicitada' => 'Invalid Specialty' // Invalid specialty
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/solicitudes-medicas', $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'paciente_nombre',
                'paciente_edad',
                'especialidad_solicitada'
            ]);
    }

    public function test_can_filter_solicitudes_by_priority()
    {
        SolicitudMedica::factory()->create(['prioridad_ia' => 'Alta']);
        SolicitudMedica::factory()->create(['prioridad_ia' => 'Media']);
        SolicitudMedica::factory()->create(['prioridad_ia' => 'Baja']);

        $response = $this->actingAs($this->admin)
            ->get('/api/solicitudes-medicas?prioridad=Alta');

        $response->assertStatus(200);
        
        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertEquals('Alta', $data[0]['prioridad_ia']);
    }

    public function test_can_search_solicitudes()
    {
        SolicitudMedica::factory()->create([
            'paciente_nombre' => 'Juan',
            'diagnostico_principal' => 'Hipertensión'
        ]);

        SolicitudMedica::factory()->create([
            'paciente_nombre' => 'María',
            'diagnostico_principal' => 'Diabetes'
        ]);

        $response = $this->actingAs($this->admin)
            ->get('/api/solicitudes-medicas?search=Juan');

        $response->assertStatus(200);
        
        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertEquals('Juan', $data[0]['paciente_nombre']);
    }

    public function test_unauthorized_access_returns_401()
    {
        $response = $this->get('/api/solicitudes-medicas');
        $response->assertStatus(401);
    }

    public function test_can_get_solicitud_details()
    {
        $solicitud = SolicitudMedica::factory()->create([
            'especialidad_solicitada' => 'Cardiología'
        ]);

        $response = $this->actingAs($this->medico)
            ->get("/api/solicitudes-medicas/{$solicitud->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'paciente_nombre',
                    'paciente_apellidos',
                    'diagnostico_principal',
                    'especialidad_solicitada',
                    'motivo_consulta',
                    'antecedentes_medicos',
                    'medicamentos_actuales',
                    'prioridad_ia',
                    'score_urgencia',
                    'analisis_ia',
                    'estado',
                    'fecha_recepcion_email'
                ]
            ]);
    }

    public function test_can_update_solicitud_status()
    {
        $solicitud = SolicitudMedica::factory()->create([
            'estado' => 'pendiente_evaluacion',
            'medico_evaluador_id' => $this->medico->id
        ]);

        $response = $this->actingAs($this->medico)
            ->putJson("/api/solicitudes-medicas/{$solicitud->id}", [
                'estado' => 'en_evaluacion'
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('solicitudes_medicas', [
            'id' => $solicitud->id,
            'estado' => 'en_evaluacion'
        ]);
    }
}
