<?php

namespace App\Providers;

use App\Events\MedicalRequestEvaluated;
use App\Events\UrgentMedicalCaseDetected;
use App\Listeners\LogMedicalRequestEvaluation;
use App\Listeners\SendUrgentCaseNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        // Medical System Events
        UrgentMedicalCaseDetected::class => [
            SendUrgentCaseNotification::class,
        ],

        MedicalRequestEvaluated::class => [
            LogMedicalRequestEvaluation::class,
        ],

        // Authentication Events
        'Illuminate\Auth\Events\Login' => [
            'App\Listeners\LogUserLogin',
        ],

        'Illuminate\Auth\Events\Logout' => [
            'App\Listeners\LogUserLogout',
        ],

        'Illuminate\Auth\Events\Failed' => [
            'App\Listeners\LogFailedLogin',
        ],

        // Model Events
        'App\Models\SolicitudMedica: created' => [
            'App\Listeners\ProcessNewMedicalRequest',
        ],

        'App\Models\User: created' => [
            'App\Listeners\SendWelcomeNotification',
        ],

        // System Events
        'App\Events\SystemBackupCompleted' => [
            'App\Listeners\NotifyBackupCompletion',
        ],

        'App\Events\SystemBackupFailed' => [
            'App\Listeners\NotifyBackupFailure',
        ],

        'App\Events\SystemMaintenanceScheduled' => [
            'App\Listeners\NotifySystemMaintenance',
        ],

        // Email Events
        'App\Events\EmailProcessed' => [
            'App\Listeners\LogEmailProcessing',
        ],

        'App\Events\EmailProcessingFailed' => [
            'App\Listeners\LogEmailProcessingFailure',
        ],

        // AI Events
        'App\Events\AIAnalysisCompleted' => [
            'App\Listeners\LogAIAnalysis',
        ],

        'App\Events\AIAnalysisFailed' => [
            'App\Listeners\LogAIAnalysisFailure',
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        // Register model observers
        \App\Models\SolicitudMedica::observe(\App\Observers\SolicitudMedicaObserver::class);
        \App\Models\User::observe(\App\Observers\UserObserver::class);
        \App\Models\AuditLog::observe(\App\Observers\AuditLogObserver::class);

        // Register custom event listeners
        Event::listen('eloquent.created: App\Models\SolicitudMedica', function ($event, $models) {
            foreach ($models as $model) {
                // Trigger AI analysis for new medical requests
                \App\Jobs\ProcessGmailEmailJob::dispatch($model);
            }
        });

        Event::listen('eloquent.updated: App\Models\SolicitudMedica', function ($event, $models) {
            foreach ($models as $model) {
                // Check if status changed to urgent
                if ($model->wasChanged('puntuacion_urgencia') && $model->puntuacion_urgencia >= 80) {
                    event(new UrgentMedicalCaseDetected($model));
                }

                // Check if evaluation was completed
                if ($model->wasChanged('estado') && in_array($model->estado, ['aceptada', 'rechazada', 'derivada'])) {
                    $evaluador = \App\Models\User::find($model->medico_evaluador_id);
                    if ($evaluador) {
                        event(new MedicalRequestEvaluated($model, $evaluador, $model->estado));
                    }
                }
            }
        });

        // Register queue event listeners
        Event::listen('Illuminate\Queue\Events\JobProcessed', function ($event) {
            \Illuminate\Support\Facades\Log::info('Job processed successfully', [
                'job' => $event->job->resolveName(),
                'queue' => $event->job->getQueue(),
                'connection' => $event->connectionName,
            ]);
        });

        Event::listen('Illuminate\Queue\Events\JobFailed', function ($event) {
            \Illuminate\Support\Facades\Log::error('Job failed', [
                'job' => $event->job->resolveName(),
                'queue' => $event->job->getQueue(),
                'connection' => $event->connectionName,
                'exception' => $event->exception->getMessage(),
            ]);

            // Notify administrators about job failures
            $this->notifyJobFailure($event);
        });

        // Register cache event listeners
        Event::listen('Illuminate\Cache\Events\CacheHit', function ($event) {
            \Illuminate\Support\Facades\Log::debug('Cache hit', [
                'key' => $event->key,
                'tags' => $event->tags,
            ]);
        });

        Event::listen('Illuminate\Cache\Events\CacheMissed', function ($event) {
            \Illuminate\Support\Facades\Log::debug('Cache missed', [
                'key' => $event->key,
                'tags' => $event->tags,
            ]);
        });

        // Register database event listeners
        Event::listen('Illuminate\Database\Events\QueryExecuted', function ($event) {
            if ($event->time > 1000) { // Log slow queries (> 1 second)
                \Illuminate\Support\Facades\Log::warning('Slow query detected', [
                    'sql' => $event->sql,
                    'bindings' => $event->bindings,
                    'time' => $event->time,
                ]);
            }
        });

        // Register HTTP event listeners
        Event::listen('Illuminate\Foundation\Http\Events\RequestHandled', function ($event) {
            $response = $event->response;
            $request = $event->request;

            // Log API requests
            if ($request->is('api/*')) {
                \Illuminate\Support\Facades\Log::info('API request', [
                    'method' => $request->method(),
                    'url' => $request->fullUrl(),
                    'status' => $response->getStatusCode(),
                    'user_id' => $request->user()?->id,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            }

            // Log errors
            if ($response->getStatusCode() >= 400) {
                \Illuminate\Support\Facades\Log::warning('HTTP error response', [
                    'method' => $request->method(),
                    'url' => $request->fullUrl(),
                    'status' => $response->getStatusCode(),
                    'user_id' => $request->user()?->id,
                    'ip' => $request->ip(),
                ]);
            }
        });
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }

    /**
     * Notify administrators about job failures.
     */
    private function notifyJobFailure($event): void
    {
        try {
            $admins = \App\Models\User::where('role', 'administrador')
                                     ->where('activo', true)
                                     ->get();

            foreach ($admins as $admin) {
                \App\Models\Notification::create([
                    'user_id' => $admin->id,
                    'type' => 'system_error',
                    'title' => 'Fallo en Job del Sistema',
                    'message' => "El job {$event->job->resolveName()} falló en la cola {$event->job->getQueue()}",
                    'data' => [
                        'job_name' => $event->job->resolveName(),
                        'queue' => $event->job->getQueue(),
                        'connection' => $event->connectionName,
                        'error' => $event->exception->getMessage(),
                        'failed_at' => now()->toISOString(),
                    ],
                    'priority' => 'high',
                    'read_at' => null,
                ]);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to notify job failure', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
