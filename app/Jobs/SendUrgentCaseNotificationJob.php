<?php

namespace App\Jobs;

use App\Models\SolicitudMedica;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class SendUrgentCaseNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $solicitud;
    protected $maxTries = 3;
    protected $timeout = 120; // 2 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(SolicitudMedica $solicitud)
    {
        $this->solicitud = $solicitud;
        $this->onQueue('notifications');
    }

    /**
     * Execute the job.
     */
    public function handle(NotificationService $notificationService): void
    {
        try {
            Log::info('Sending urgent case notification', [
                'solicitud_id' => $this->solicitud->id,
                'prioridad' => $this->solicitud->prioridad_ia
            ]);

            // Get users to notify
            $usersToNotify = $this->getUsersToNotify();

            if ($usersToNotify->isEmpty()) {
                Log::warning('No users found to notify for urgent case', [
                    'solicitud_id' => $this->solicitud->id
                ]);
                return;
            }

            // Send notifications through different channels
            $this->sendEmailNotifications($usersToNotify, $notificationService);
            $this->sendInternalNotifications($usersToNotify, $notificationService);
            $this->sendPushNotifications($usersToNotify, $notificationService);

            Log::info('Urgent case notifications sent successfully', [
                'solicitud_id' => $this->solicitud->id,
                'users_notified' => $usersToNotify->count()
            ]);

        } catch (Exception $e) {
            Log::error('Failed to send urgent case notification', [
                'solicitud_id' => $this->solicitud->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Get users that should be notified
     */
    private function getUsersToNotify()
    {
        // Get active medical users
        $medicos = User::where('role', 'medico')
            ->where('is_active', true)
            ->get();

        // Filter by specialty if available
        if ($this->solicitud->especialidad_solicitada) {
            $especialidadMedicos = $medicos->filter(function ($medico) {
                return $medico->specialties && 
                       in_array($this->solicitud->especialidad_solicitada, $medico->specialties);
            });

            // If we have specialists, notify them; otherwise notify all medicos
            if ($especialidadMedicos->isNotEmpty()) {
                $medicos = $especialidadMedicos;
            }
        }

        // Always include administrators
        $admins = User::where('role', 'administrador')
            ->where('is_active', true)
            ->get();

        return $medicos->merge($admins);
    }

    /**
     * Send email notifications
     */
    private function sendEmailNotifications($users, NotificationService $notificationService): void
    {
        if (!config('notifications.email_enabled', true)) {
            return;
        }

        foreach ($users as $user) {
            try {
                $notificationService->sendUrgentCaseEmail($user, $this->solicitud);
            } catch (Exception $e) {
                Log::error('Failed to send email notification', [
                    'user_id' => $user->id,
                    'solicitud_id' => $this->solicitud->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Send internal notifications
     */
    private function sendInternalNotifications($users, NotificationService $notificationService): void
    {
        foreach ($users as $user) {
            try {
                $notificationService->createInternalNotification(
                    $user->id,
                    'urgent_case',
                    'Nuevo Caso Urgente',
                    "Caso urgente recibido: {$this->solicitud->paciente_nombre} - {$this->solicitud->especialidad_solicitada}",
                    [
                        'solicitud_id' => $this->solicitud->id,
                        'prioridad' => $this->solicitud->prioridad_ia,
                        'score_urgencia' => $this->solicitud->score_urgencia,
                        'url' => route('medico.evaluar-solicitud', $this->solicitud->id)
                    ]
                );
            } catch (Exception $e) {
                Log::error('Failed to send internal notification', [
                    'user_id' => $user->id,
                    'solicitud_id' => $this->solicitud->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Send push notifications (browser notifications)
     */
    private function sendPushNotifications($users, NotificationService $notificationService): void
    {
        $notificationData = [
            'title' => 'Caso Médico Urgente',
            'body' => "{$this->solicitud->paciente_nombre} - {$this->solicitud->especialidad_solicitada}",
            'icon' => '/favicon.ico',
            'badge' => '/favicon.ico',
            'tag' => 'urgent-case-' . $this->solicitud->id,
            'data' => [
                'solicitud_id' => $this->solicitud->id,
                'url' => route('medico.evaluar-solicitud', $this->solicitud->id)
            ],
            'actions' => [
                [
                    'action' => 'view',
                    'title' => 'Ver Caso',
                    'icon' => '/icons/view.png'
                ],
                [
                    'action' => 'dismiss',
                    'title' => 'Descartar',
                    'icon' => '/icons/dismiss.png'
                ]
            ]
        ];

        foreach ($users as $user) {
            try {
                $notificationService->sendPushNotification($user, $notificationData);
            } catch (Exception $e) {
                Log::error('Failed to send push notification', [
                    'user_id' => $user->id,
                    'solicitud_id' => $this->solicitud->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Handle job failure
     */
    public function failed(Exception $exception): void
    {
        Log::error('SendUrgentCaseNotificationJob failed permanently', [
            'solicitud_id' => $this->solicitud->id,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        // Send notification to administrators about failed notification
        try {
            $admins = User::where('role', 'administrador')
                ->where('is_active', true)
                ->get();

            $notificationService = app(NotificationService::class);

            foreach ($admins as $admin) {
                $notificationService->createInternalNotification(
                    $admin->id,
                    'system_error',
                    'Error en Notificación Urgente',
                    "Falló el envío de notificación para caso urgente ID: {$this->solicitud->id}",
                    [
                        'solicitud_id' => $this->solicitud->id,
                        'error' => $exception->getMessage()
                    ]
                );
            }
        } catch (Exception $e) {
            Log::error('Failed to notify admins about notification failure', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
