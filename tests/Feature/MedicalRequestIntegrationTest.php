<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\SolicitudMedica;
use App\Services\GeminiAIService;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Event;

class MedicalRequestIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $medico;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create([
            'role' => 'administrador',
            'email' => 'admin@test.com'
        ]);
        
        $this->medico = User::factory()->create([
            'role' => 'medico',
            'email' => 'medico@test.com'
        ]);
    }

    public function test_complete_medical_request_workflow()
    {
        Mail::fake();
        Event::fake();

        // Step 1: Create a medical request (simulating email processing)
        $solicitud = SolicitudMedica::factory()->create([
            'paciente_nombre' => 'Juan Pérez',
            'especialidad_solicitada' => 'Cardiología',
            'prioridad_ia' => 'Alta',
            'estado' => 'pendiente_evaluacion',
            'diagnostico_principal' => 'Dolor torácico agudo'
        ]);

        $this->assertDatabaseHas('solicitudes_medicas', [
            'id' => $solicitud->id,
            'estado' => 'pendiente_evaluacion'
        ]);

        // Step 2: Medical evaluation
        $this->actingAs($this->medico);
        
        $evaluationData = [
            'decision_medica' => 'aceptar',
            'observaciones_medico' => 'Caso aceptado para traslado urgente',
            'prioridad_medica' => 'Alta',
            'fecha_programada' => now()->addHours(2)->format('Y-m-d\TH:i'),
            'servicio_destino' => 'urgencias'
        ];

        $response = $this->post(route('medico.guardar-evaluacion', $solicitud->id), $evaluationData);

        $response->assertRedirect();
        
        $solicitud->refresh();
        $this->assertEquals('evaluada', $solicitud->estado);
        $this->assertEquals('aceptar', $solicitud->decision_medica);
        $this->assertEquals($this->medico->id, $solicitud->medico_evaluador_id);

        // Step 3: Verify notification was sent
        $this->assertDatabaseHas('notificaciones_internas', [
            'solicitud_medica_id' => $solicitud->id,
            'tipo' => 'evaluacion_completada'
        ]);
    }

    public function test_urgent_case_detection_and_notification_flow()
    {
        Mail::fake();
        Event::fake();

        // Create urgent case
        $urgentCase = SolicitudMedica::factory()->create([
            'prioridad_ia' => 'Alta',
            'score_urgencia' => 95,
            'diagnostico_principal' => 'Infarto agudo del miocardio',
            'estado' => 'pendiente_evaluacion'
        ]);

        // Trigger urgent notification
        $notificationService = new NotificationService();
        $result = $notificationService->sendUrgentCaseNotification($urgentCase);

        $this->assertTrue($result['success']);

        // Verify internal notification was created
        $this->assertDatabaseHas('notificaciones_internas', [
            'solicitud_medica_id' => $urgentCase->id,
            'tipo' => 'caso_urgente'
        ]);

        // Verify real-time event was dispatched
        Event::assertDispatched(\App\Events\UrgentMedicalCaseDetected::class);
    }

    public function test_medical_request_api_endpoints()
    {
        $this->actingAs($this->admin);

        // Test getting medical requests list
        $solicitudes = SolicitudMedica::factory()->count(5)->create();

        $response = $this->getJson('/api/solicitudes-medicas');
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         '*' => [
                             'id',
                             'paciente_nombre',
                             'especialidad_solicitada',
                             'prioridad_ia',
                             'estado'
                         ]
                     ]
                 ]);

        // Test getting specific medical request
        $solicitud = $solicitudes->first();
        $response = $this->getJson("/api/solicitudes-medicas/{$solicitud->id}");
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'id' => $solicitud->id,
                         'paciente_nombre' => $solicitud->paciente_nombre
                     ]
                 ]);
    }

    public function test_medical_evaluation_with_different_decisions()
    {
        $this->actingAs($this->medico);

        // Test acceptance
        $solicitudAccept = SolicitudMedica::factory()->create(['estado' => 'pendiente_evaluacion']);
        
        $acceptData = [
            'decision_medica' => 'aceptar',
            'observaciones_medico' => 'Caso aceptado',
            'fecha_programada' => now()->addHours(4)->format('Y-m-d\TH:i'),
            'servicio_destino' => 'hospitalizacion'
        ];

        $response = $this->post(route('medico.guardar-evaluacion', $solicitudAccept->id), $acceptData);
        $response->assertRedirect();

        $solicitudAccept->refresh();
        $this->assertEquals('aceptar', $solicitudAccept->decision_medica);

        // Test rejection
        $solicitudReject = SolicitudMedica::factory()->create(['estado' => 'pendiente_evaluacion']);
        
        $rejectData = [
            'decision_medica' => 'rechazar',
            'observaciones_medico' => 'No cumple criterios de ingreso',
            'motivo_rechazo' => 'no_cumple_criterios'
        ];

        $response = $this->post(route('medico.guardar-evaluacion', $solicitudReject->id), $rejectData);
        $response->assertRedirect();

        $solicitudReject->refresh();
        $this->assertEquals('rechazar', $solicitudReject->decision_medica);

        // Test information request
        $solicitudInfo = SolicitudMedica::factory()->create(['estado' => 'pendiente_evaluacion']);
        
        $infoData = [
            'decision_medica' => 'solicitar_info',
            'observaciones_medico' => 'Necesito más información',
            'informacion_requerida' => 'Exámenes de laboratorio recientes'
        ];

        $response = $this->post(route('medico.guardar-evaluacion', $solicitudInfo->id), $infoData);
        $response->assertRedirect();

        $solicitudInfo->refresh();
        $this->assertEquals('solicitar_info', $solicitudInfo->decision_medica);
    }

    public function test_dashboard_metrics_integration()
    {
        $this->actingAs($this->admin);

        // Create test data
        SolicitudMedica::factory()->count(10)->create(['prioridad_ia' => 'Alta']);
        SolicitudMedica::factory()->count(15)->create(['prioridad_ia' => 'Media']);
        SolicitudMedica::factory()->count(5)->create(['prioridad_ia' => 'Baja']);

        // Test metrics API
        $response = $this->getJson('/api/metrics/dashboard');
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'overview',
                         'solicitudes',
                         'performance'
                     ]
                 ]);

        $data = $response->json('data');
        $this->assertArrayHasKey('overview', $data);
        $this->assertArrayHasKey('solicitudes', $data);
    }

    public function test_user_authentication_and_authorization()
    {
        // Test unauthenticated access
        $response = $this->get('/medico/bandeja-casos');
        $response->assertRedirect('/login');

        // Test medical user access
        $this->actingAs($this->medico);
        $response = $this->get('/medico/bandeja-casos');
        $response->assertStatus(200);

        // Test admin access to admin routes
        $this->actingAs($this->admin);
        $response = $this->get('/admin/dashboard');
        $response->assertStatus(200);

        // Test medical user cannot access admin routes
        $this->actingAs($this->medico);
        $response = $this->get('/admin/dashboard');
        $response->assertStatus(403);
    }

    public function test_ai_service_integration()
    {
        $geminiService = new GeminiAIService();

        $medicalText = "Paciente de 65 años con dolor torácico agudo, disnea y sudoración profusa. FC: 120 lpm, TA: 160/100 mmHg.";

        $result = $geminiService->analyzeMedicalText($medicalText);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('patient_info', $result);
        $this->assertArrayHasKey('vital_signs', $result);

        // Test priority classification
        $medicalData = [
            'diagnostico_principal' => 'Infarto agudo del miocardio',
            'motivo_consulta' => 'Dolor torácico severo',
            'paciente_edad' => 65
        ];

        $priorityResult = $geminiService->classifyPriority($medicalData);

        $this->assertIsArray($priorityResult);
        $this->assertArrayHasKey('priority_level', $priorityResult);
        $this->assertArrayHasKey('urgency_score', $priorityResult);
    }

    public function test_notification_system_integration()
    {
        Mail::fake();

        $notificationService = new NotificationService();
        $solicitud = SolicitudMedica::factory()->create([
            'prioridad_ia' => 'Alta',
            'paciente_nombre' => 'Test Patient'
        ]);

        // Test urgent case notification
        $result = $notificationService->sendUrgentCaseNotification($solicitud);
        $this->assertTrue($result['success']);

        // Test evaluation notification
        $evaluationData = [
            'decision_medica' => 'aceptar',
            'observaciones_medico' => 'Test evaluation'
        ];

        $result = $notificationService->sendEvaluationNotification($solicitud, $this->medico, $evaluationData);
        $this->assertTrue($result['success']);

        // Verify notifications were created
        $this->assertDatabaseHas('notificaciones_internas', [
            'solicitud_medica_id' => $solicitud->id,
            'tipo' => 'caso_urgente'
        ]);
    }

    public function test_audit_logging_integration()
    {
        $this->actingAs($this->medico);

        $solicitud = SolicitudMedica::factory()->create(['estado' => 'pendiente_evaluacion']);

        $evaluationData = [
            'decision_medica' => 'aceptar',
            'observaciones_medico' => 'Test evaluation for audit'
        ];

        $response = $this->post(route('medico.guardar-evaluacion', $solicitud->id), $evaluationData);

        // Verify audit log was created
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->medico->id,
            'action' => 'evaluate_medical_request'
        ]);
    }

    public function test_real_time_notifications_integration()
    {
        Event::fake();

        $solicitud = SolicitudMedica::factory()->create([
            'prioridad_ia' => 'Alta',
            'paciente_nombre' => 'Emergency Patient'
        ]);

        $notificationService = new NotificationService();
        $result = $notificationService->sendRealTimeNotification([
            'type' => 'urgent_case',
            'solicitud_id' => $solicitud->id,
            'patient_name' => $solicitud->paciente_nombre
        ]);

        $this->assertTrue($result['success']);
        Event::assertDispatched(\App\Events\UrgentMedicalCaseDetected::class);
    }

    public function test_system_configuration_integration()
    {
        $this->actingAs($this->admin);

        // Test Gmail configuration
        $gmailConfig = [
            'email' => 'test@gmail.com',
            'app_password' => 'test_password_1234567890',
            'imap_server' => 'imap.gmail.com',
            'imap_port' => 993,
            'check_interval' => 5,
            'max_emails_per_check' => 50,
            'enabled' => true
        ];

        $response = $this->postJson('/api/config/gmail', $gmailConfig);
        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        // Test AI configuration
        $aiConfig = [
            'gemini_api_keys' => ['test_key_1234567890123456789012345678901234567890'],
            'confidence_threshold' => 0.7,
            'enhanced_analysis' => true,
            'priority_classification' => true,
            'semantic_analysis' => true
        ];

        $response = $this->postJson('/api/config/ai', $aiConfig);
        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
    }

    public function test_backup_system_integration()
    {
        $this->actingAs($this->admin);

        // Test backup creation
        $response = $this->postJson('/api/system/backup', [
            'type' => 'database',
            'compress' => true
        ]);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
    }
}
