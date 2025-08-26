<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\NotificationService;
use App\Models\SolicitudMedica;
use App\Models\User;
use App\Models\NotificacionInterna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Event;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $notificationService;
    protected $user;
    protected $solicitud;

    protected function setUp(): void
    {
        parent::setUp();
        $this->notificationService = new NotificationService();
        
        $this->user = User::factory()->create([
            'role' => 'medico',
            'email' => 'medico@test.com'
        ]);
        
        $this->solicitud = SolicitudMedica::factory()->create([
            'prioridad_ia' => 'Alta',
            'paciente_nombre' => 'Juan Pérez',
            'especialidad_solicitada' => 'Cardiología'
        ]);
    }

    public function test_can_send_urgent_case_notification()
    {
        Mail::fake();
        Event::fake();

        $result = $this->notificationService->sendUrgentCaseNotification($this->solicitud);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('notificaciones_internas', [
            'solicitud_medica_id' => $this->solicitud->id,
            'tipo' => 'caso_urgente'
        ]);
    }

    public function test_can_send_evaluation_notification()
    {
        Mail::fake();

        $evaluationData = [
            'decision_medica' => 'aceptar',
            'observaciones_medico' => 'Caso aceptado para traslado'
        ];

        $result = $this->notificationService->sendEvaluationNotification(
            $this->solicitud, 
            $this->user, 
            $evaluationData
        );

        $this->assertTrue($result['success']);
    }

    public function test_can_send_system_alert()
    {
        Mail::fake();

        $alertData = [
            'type' => 'system_error',
            'message' => 'Error en el sistema de monitoreo',
            'severity' => 'high'
        ];

        $result = $this->notificationService->sendSystemAlert($alertData);

        $this->assertTrue($result['success']);
    }

    public function test_creates_internal_notification_record()
    {
        $notificationData = [
            'solicitud_medica_id' => $this->solicitud->id,
            'tipo' => 'caso_urgente',
            'titulo' => 'Caso Urgente Detectado',
            'mensaje' => 'Se ha detectado un caso de alta prioridad',
            'destinatario_email' => 'admin@test.com'
        ];

        $result = $this->notificationService->createInternalNotification($notificationData);

        $this->assertInstanceOf(NotificacionInterna::class, $result);
        $this->assertDatabaseHas('notificaciones_internas', [
            'solicitud_medica_id' => $this->solicitud->id,
            'tipo' => 'caso_urgente'
        ]);
    }

    public function test_handles_failed_email_sending()
    {
        Mail::fake();
        Mail::shouldReceive('send')->andThrow(new \Exception('SMTP Error'));

        $result = $this->notificationService->sendUrgentCaseNotification($this->solicitud);

        // Should handle error gracefully
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    public function test_can_get_pending_notifications()
    {
        // Create some test notifications
        NotificacionInterna::factory()->count(3)->create([
            'estado' => 'pendiente'
        ]);

        NotificacionInterna::factory()->count(2)->create([
            'estado' => 'enviada'
        ]);

        $pendingNotifications = $this->notificationService->getPendingNotifications();

        $this->assertCount(3, $pendingNotifications);
        $this->assertTrue($pendingNotifications->every(function ($notification) {
            return $notification->estado === 'pendiente';
        }));
    }

    public function test_can_mark_notification_as_sent()
    {
        $notification = NotificacionInterna::factory()->create([
            'estado' => 'pendiente'
        ]);

        $result = $this->notificationService->markNotificationAsSent($notification->id);

        $this->assertTrue($result);
        $this->assertDatabaseHas('notificaciones_internas', [
            'id' => $notification->id,
            'estado' => 'enviada'
        ]);
    }

    public function test_can_retry_failed_notifications()
    {
        $failedNotification = NotificacionInterna::factory()->create([
            'estado' => 'fallida',
            'intentos_envio' => 2
        ]);

        Mail::fake();

        $result = $this->notificationService->retryFailedNotification($failedNotification->id);

        $this->assertTrue($result['success']);
        
        $failedNotification->refresh();
        $this->assertEquals(3, $failedNotification->intentos_envio);
    }

    public function test_validates_notification_data()
    {
        $invalidData = [
            'tipo' => '', // Empty type
            'mensaje' => '' // Empty message
        ];

        $result = $this->notificationService->createInternalNotification($invalidData);

        $this->assertNull($result);
    }

    public function test_can_send_batch_notifications()
    {
        Mail::fake();

        $users = User::factory()->count(3)->create(['role' => 'medico']);
        $userIds = $users->pluck('id')->toArray();

        $notificationData = [
            'tipo' => 'sistema',
            'titulo' => 'Mantenimiento Programado',
            'mensaje' => 'El sistema estará en mantenimiento mañana'
        ];

        $result = $this->notificationService->sendBatchNotifications($userIds, $notificationData);

        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['sent_count']);
    }

    public function test_can_get_notification_statistics()
    {
        // Create test notifications with different states
        NotificacionInterna::factory()->count(5)->create(['estado' => 'enviada']);
        NotificacionInterna::factory()->count(2)->create(['estado' => 'pendiente']);
        NotificacionInterna::factory()->count(1)->create(['estado' => 'fallida']);

        $stats = $this->notificationService->getNotificationStatistics();

        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('sent', $stats);
        $this->assertArrayHasKey('pending', $stats);
        $this->assertArrayHasKey('failed', $stats);
        $this->assertEquals(8, $stats['total']);
        $this->assertEquals(5, $stats['sent']);
        $this->assertEquals(2, $stats['pending']);
        $this->assertEquals(1, $stats['failed']);
    }

    public function test_can_clean_old_notifications()
    {
        // Create old notifications
        NotificacionInterna::factory()->count(3)->create([
            'created_at' => now()->subDays(35),
            'estado' => 'enviada'
        ]);

        // Create recent notifications
        NotificacionInterna::factory()->count(2)->create([
            'created_at' => now()->subDays(5),
            'estado' => 'enviada'
        ]);

        $deletedCount = $this->notificationService->cleanOldNotifications(30);

        $this->assertEquals(3, $deletedCount);
        $this->assertEquals(2, NotificacionInterna::count());
    }

    public function test_formats_notification_content_correctly()
    {
        $content = $this->notificationService->formatUrgentCaseContent($this->solicitud);

        $this->assertIsArray($content);
        $this->assertArrayHasKey('subject', $content);
        $this->assertArrayHasKey('message', $content);
        $this->assertStringContainsString($this->solicitud->paciente_nombre, $content['message']);
        $this->assertStringContainsString($this->solicitud->especialidad_solicitada, $content['message']);
    }

    public function test_can_send_real_time_notification()
    {
        Event::fake();

        $eventData = [
            'type' => 'urgent_case',
            'solicitud_id' => $this->solicitud->id,
            'patient_name' => $this->solicitud->paciente_nombre
        ];

        $result = $this->notificationService->sendRealTimeNotification($eventData);

        $this->assertTrue($result['success']);
        Event::assertDispatched(\App\Events\UrgentMedicalCaseDetected::class);
    }
}
