<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\CreateSystemBackupJob;
use App\Jobs\SendUrgentCaseNotificationJob;
use App\Models\SolicitudMedica;
use App\Models\MetricaSistema;
use App\Models\NotificacionInterna;
use App\Models\User;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Configure scheduled tasks
Schedule::macro('vitalRedTasks', function () {
    // Gmail monitoring (every 5 minutes if enabled)
    if (config('services.gmail.enabled', false)) {
        Schedule::command('gmail:monitor --once')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/gmail-monitor.log'));
    }

    // Daily system backup
    Schedule::job(new CreateSystemBackupJob('database'))
        ->daily()
        ->at('02:00')
        ->withoutOverlapping()
        ->runInBackground();

    // Weekly full backup
    Schedule::job(new CreateSystemBackupJob('full'))
        ->weekly()
        ->sundays()
        ->at('03:00')
        ->withoutOverlapping()
        ->runInBackground();

    // Daily system cleanup
    Schedule::command('system:clean --logs --temp --sessions --failed-jobs')
        ->daily()
        ->at('01:00')
        ->withoutOverlapping()
        ->appendOutputTo(storage_path('logs/system-clean.log'));

    // Clear expired notifications (every hour)
    Schedule::call(function () {
        NotificacionInterna::where('created_at', '<', now()->subDays(30))->delete();
    })->hourly();

    // Update system metrics (every 15 minutes)
    Schedule::call(function () {
        updateSystemMetrics();
    })->everyFifteenMinutes();

    // Check for overdue urgent cases (every 30 minutes)
    Schedule::call(function () {
        checkOverdueUrgentCases();
    })->everyThirtyMinutes();

    // Generate daily reports (weekdays at 8 AM)
    Schedule::call(function () {
        generateDailyReports();
    })->weekdays()->at('08:00');
});

// Helper functions for scheduled tasks
function updateSystemMetrics(): void
{
    try {
        $metrics = [
            'total_cases' => SolicitudMedica::count(),
            'pending_cases' => SolicitudMedica::where('estado', 'pendiente_evaluacion')->count(),
            'urgent_cases' => SolicitudMedica::where('prioridad_ia', 'Alta')
                ->where('estado', 'pendiente_evaluacion')->count(),
            'active_users' => User::where('is_active', true)->count(),
            'cases_today' => SolicitudMedica::whereDate('fecha_recepcion_email', today())->count(),
            'evaluations_today' => SolicitudMedica::whereDate('fecha_evaluacion', today())->count(),
        ];

        foreach ($metrics as $name => $value) {
            MetricaSistema::create([
                'nombre_metrica' => $name,
                'valor' => $value,
                'tipo_metrica' => 'gauge',
                'timestamp' => now(),
            ]);
        }
    } catch (\Exception $e) {
        \Log::error('Failed to update system metrics', ['error' => $e->getMessage()]);
    }
}

function checkOverdueUrgentCases(): void
{
    try {
        $urgentThreshold = config('notifications.urgent_threshold', 2); // hours
        $cutoffTime = now()->subHours($urgentThreshold);

        $overdueCases = SolicitudMedica::where('prioridad_ia', 'Alta')
            ->where('estado', 'pendiente_evaluacion')
            ->where('fecha_recepcion_email', '<=', $cutoffTime)
            ->get();

        foreach ($overdueCases as $case) {
            // Send escalation notification
            SendUrgentCaseNotificationJob::dispatch($case);

            \Log::warning('Overdue urgent case detected', [
                'solicitud_id' => $case->id,
                'hours_overdue' => $case->fecha_recepcion_email->diffInHours(now())
            ]);
        }
    } catch (\Exception $e) {
        \Log::error('Failed to check overdue urgent cases', ['error' => $e->getMessage()]);
    }
}

function generateDailyReports(): void
{
    try {
        $yesterday = now()->subDay();

        $dailyStats = [
            'date' => $yesterday->toDateString(),
            'total_received' => SolicitudMedica::whereDate('fecha_recepcion_email', $yesterday)->count(),
            'total_evaluated' => SolicitudMedica::whereDate('fecha_evaluacion', $yesterday)->count(),
            'urgent_cases' => SolicitudMedica::whereDate('fecha_recepcion_email', $yesterday)
                ->where('prioridad_ia', 'Alta')->count(),
            'avg_response_time' => calculateAverageResponseTime($yesterday),
        ];

        // Store daily report
        MetricaSistema::create([
            'nombre_metrica' => 'daily_report',
            'valor' => 1,
            'tipo_metrica' => 'counter',
            'etiquetas' => $dailyStats,
            'timestamp' => $yesterday,
        ]);

        \Log::info('Daily report generated', $dailyStats);
    } catch (\Exception $e) {
        \Log::error('Failed to generate daily report', ['error' => $e->getMessage()]);
    }
}

function calculateAverageResponseTime(\Carbon\Carbon $date): float
{
    $evaluatedCases = SolicitudMedica::whereDate('fecha_evaluacion', $date)
        ->whereNotNull('fecha_evaluacion')
        ->get();

    if ($evaluatedCases->isEmpty()) {
        return 0;
    }

    $totalHours = $evaluatedCases->sum(function ($case) {
        return $case->fecha_recepcion_email->diffInHours($case->fecha_evaluacion);
    });

    return round($totalHours / $evaluatedCases->count(), 2);
}
