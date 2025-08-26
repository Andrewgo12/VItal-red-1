<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\SolicitudMedica;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class SolicitudMedicaTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_solicitud_medica()
    {
        $solicitud = SolicitudMedica::factory()->create([
            'paciente_nombre' => 'Juan Pérez',
            'especialidad_solicitada' => 'Cardiología',
            'prioridad_ia' => 'Alta'
        ]);

        $this->assertDatabaseHas('solicitudes_medicas', [
            'paciente_nombre' => 'Juan Pérez',
            'especialidad_solicitada' => 'Cardiología',
            'prioridad_ia' => 'Alta'
        ]);
    }

    public function test_has_vital_signs_method_works()
    {
        $solicitudWithVitals = SolicitudMedica::factory()->create([
            'frecuencia_cardiaca' => 80,
            'tension_sistolica' => 120,
            'tension_diastolica' => 80
        ]);

        $solicitudWithoutVitals = SolicitudMedica::factory()->create([
            'frecuencia_cardiaca' => null,
            'tension_sistolica' => null,
            'tension_diastolica' => null
        ]);

        $this->assertTrue($solicitudWithVitals->hasVitalSigns());
        $this->assertFalse($solicitudWithoutVitals->hasVitalSigns());
    }

    public function test_belongs_to_medico_evaluador()
    {
        $medico = User::factory()->create(['role' => 'medico']);
        $solicitud = SolicitudMedica::factory()->create([
            'medico_evaluador_id' => $medico->id
        ]);

        $this->assertInstanceOf(User::class, $solicitud->medicoEvaluador);
        $this->assertEquals($medico->id, $solicitud->medicoEvaluador->id);
    }

    public function test_can_calculate_response_time()
    {
        $solicitud = SolicitudMedica::factory()->create([
            'fecha_recepcion_email' => Carbon::now()->subHours(5),
            'fecha_evaluacion' => Carbon::now()
        ]);

        $responseTime = $solicitud->getResponseTimeInHours();
        $this->assertEquals(5, $responseTime);
    }

    public function test_scope_urgent_cases()
    {
        SolicitudMedica::factory()->count(3)->create(['prioridad_ia' => 'Alta']);
        SolicitudMedica::factory()->count(2)->create(['prioridad_ia' => 'Media']);
        SolicitudMedica::factory()->count(1)->create(['prioridad_ia' => 'Baja']);

        $urgentCases = SolicitudMedica::urgentCases()->get();
        $this->assertCount(3, $urgentCases);
        $this->assertTrue($urgentCases->every(function ($case) {
            return $case->prioridad_ia === 'Alta';
        }));
    }

    public function test_scope_pending_evaluation()
    {
        SolicitudMedica::factory()->count(2)->create(['estado' => 'pendiente_evaluacion']);
        SolicitudMedica::factory()->count(3)->create(['estado' => 'evaluada']);

        $pendingCases = SolicitudMedica::pendingEvaluation()->get();
        $this->assertCount(2, $pendingCases);
        $this->assertTrue($pendingCases->every(function ($case) {
            return $case->estado === 'pendiente_evaluacion';
        }));
    }

    public function test_scope_by_specialty()
    {
        SolicitudMedica::factory()->count(3)->create(['especialidad_solicitada' => 'Cardiología']);
        SolicitudMedica::factory()->count(2)->create(['especialidad_solicitada' => 'Neurología']);

        $cardiologyCases = SolicitudMedica::bySpecialty('Cardiología')->get();
        $this->assertCount(3, $cardiologyCases);
        $this->assertTrue($cardiologyCases->every(function ($case) {
            return $case->especialidad_solicitada === 'Cardiología';
        }));
    }

    public function test_scope_recent_cases()
    {
        SolicitudMedica::factory()->count(2)->create([
            'fecha_recepcion_email' => Carbon::now()->subHours(2)
        ]);
        SolicitudMedica::factory()->count(3)->create([
            'fecha_recepcion_email' => Carbon::now()->subDays(2)
        ]);

        $recentCases = SolicitudMedica::recentCases(1)->get(); // Last 1 day
        $this->assertCount(2, $recentCases);
    }

    public function test_can_check_if_evaluated()
    {
        $evaluatedCase = SolicitudMedica::factory()->create([
            'estado' => 'evaluada',
            'fecha_evaluacion' => Carbon::now()
        ]);

        $pendingCase = SolicitudMedica::factory()->create([
            'estado' => 'pendiente_evaluacion',
            'fecha_evaluacion' => null
        ]);

        $this->assertTrue($evaluatedCase->isEvaluated());
        $this->assertFalse($pendingCase->isEvaluated());
    }

    public function test_can_check_if_urgent()
    {
        $urgentCase = SolicitudMedica::factory()->create(['prioridad_ia' => 'Alta']);
        $normalCase = SolicitudMedica::factory()->create(['prioridad_ia' => 'Media']);

        $this->assertTrue($urgentCase->isUrgent());
        $this->assertFalse($normalCase->isUrgent());
    }

    public function test_can_get_priority_color()
    {
        $urgentCase = SolicitudMedica::factory()->create(['prioridad_ia' => 'Alta']);
        $mediumCase = SolicitudMedica::factory()->create(['prioridad_ia' => 'Media']);
        $lowCase = SolicitudMedica::factory()->create(['prioridad_ia' => 'Baja']);

        $this->assertEquals('danger', $urgentCase->getPriorityColor());
        $this->assertEquals('warning', $mediumCase->getPriorityColor());
        $this->assertEquals('info', $lowCase->getPriorityColor());
    }

    public function test_can_get_status_badge_class()
    {
        $pendingCase = SolicitudMedica::factory()->create(['estado' => 'pendiente_evaluacion']);
        $evaluatedCase = SolicitudMedica::factory()->create(['estado' => 'evaluada']);
        $acceptedCase = SolicitudMedica::factory()->create(['estado' => 'aceptada']);
        $rejectedCase = SolicitudMedica::factory()->create(['estado' => 'rechazada']);

        $this->assertEquals('warning', $pendingCase->getStatusBadgeClass());
        $this->assertEquals('success', $evaluatedCase->getStatusBadgeClass());
        $this->assertEquals('primary', $acceptedCase->getStatusBadgeClass());
        $this->assertEquals('danger', $rejectedCase->getStatusBadgeClass());
    }

    public function test_can_get_waiting_time_in_hours()
    {
        $case = SolicitudMedica::factory()->create([
            'fecha_recepcion_email' => Carbon::now()->subHours(8)
        ]);

        $waitingTime = $case->getWaitingTimeInHours();
        $this->assertEquals(8, $waitingTime);
    }

    public function test_can_get_waiting_time_color()
    {
        $recentCase = SolicitudMedica::factory()->create([
            'fecha_recepcion_email' => Carbon::now()->subHours(2)
        ]);

        $moderateCase = SolicitudMedica::factory()->create([
            'fecha_recepcion_email' => Carbon::now()->subHours(12)
        ]);

        $oldCase = SolicitudMedica::factory()->create([
            'fecha_recepcion_email' => Carbon::now()->subHours(30)
        ]);

        $this->assertEquals('success', $recentCase->getWaitingTimeColor());
        $this->assertEquals('warning', $moderateCase->getWaitingTimeColor());
        $this->assertEquals('danger', $oldCase->getWaitingTimeColor());
    }

    public function test_can_format_patient_full_name()
    {
        $case = SolicitudMedica::factory()->create([
            'paciente_nombre' => 'Juan',
            'paciente_apellidos' => 'Pérez García'
        ]);

        $this->assertEquals('Juan Pérez García', $case->getPatientFullName());
    }

    public function test_can_check_sla_compliance()
    {
        // High priority case within SLA (2 hours)
        $urgentCaseOnTime = SolicitudMedica::factory()->create([
            'prioridad_ia' => 'Alta',
            'fecha_recepcion_email' => Carbon::now()->subHour(),
            'fecha_evaluacion' => Carbon::now()
        ]);

        // High priority case outside SLA
        $urgentCaseLate = SolicitudMedica::factory()->create([
            'prioridad_ia' => 'Alta',
            'fecha_recepcion_email' => Carbon::now()->subHours(5),
            'fecha_evaluacion' => Carbon::now()
        ]);

        $this->assertTrue($urgentCaseOnTime->isWithinSLA());
        $this->assertFalse($urgentCaseLate->isWithinSLA());
    }

    public function test_can_get_formatted_vital_signs()
    {
        $case = SolicitudMedica::factory()->create([
            'frecuencia_cardiaca' => 80,
            'frecuencia_respiratoria' => 18,
            'tension_sistolica' => 120,
            'tension_diastolica' => 80,
            'temperatura' => 36.5,
            'saturacion_oxigeno' => 98
        ]);

        $vitalSigns = $case->getFormattedVitalSigns();

        $this->assertIsArray($vitalSigns);
        $this->assertArrayHasKey('FC', $vitalSigns);
        $this->assertArrayHasKey('TA', $vitalSigns);
        $this->assertEquals('80 lpm', $vitalSigns['FC']);
        $this->assertEquals('120/80 mmHg', $vitalSigns['TA']);
    }

    public function test_validates_required_fields()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        SolicitudMedica::create([
            // Missing required fields
            'paciente_nombre' => null,
            'especialidad_solicitada' => null
        ]);
    }

    public function test_can_search_by_patient_name()
    {
        SolicitudMedica::factory()->create(['paciente_nombre' => 'Juan Pérez']);
        SolicitudMedica::factory()->create(['paciente_nombre' => 'María García']);
        SolicitudMedica::factory()->create(['paciente_nombre' => 'Pedro López']);

        $results = SolicitudMedica::searchByPatient('Juan')->get();
        $this->assertCount(1, $results);
        $this->assertEquals('Juan Pérez', $results->first()->paciente_nombre);
    }

    public function test_can_filter_by_date_range()
    {
        SolicitudMedica::factory()->create([
            'fecha_recepcion_email' => Carbon::now()->subDays(5)
        ]);
        SolicitudMedica::factory()->create([
            'fecha_recepcion_email' => Carbon::now()->subDays(2)
        ]);
        SolicitudMedica::factory()->create([
            'fecha_recepcion_email' => Carbon::now()->subDays(10)
        ]);

        $startDate = Carbon::now()->subDays(7);
        $endDate = Carbon::now();

        $results = SolicitudMedica::dateRange($startDate, $endDate)->get();
        $this->assertCount(2, $results);
    }
}
