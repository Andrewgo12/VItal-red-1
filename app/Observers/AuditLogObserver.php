<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;

class AuditLogObserver
{
    /**
     * Handle the AuditLog "creating" event.
     */
    public function creating(AuditLog $auditLog): void
    {
        // Set default values if not provided
        if (empty($auditLog->ip_address)) {
            $auditLog->ip_address = request()->ip();
        }

        if (empty($auditLog->user_agent)) {
            $auditLog->user_agent = request()->userAgent();
        }

        if (empty($auditLog->user_id) && auth()->check()) {
            $auditLog->user_id = auth()->id();
        }

        // Add session information if available
        if (session()->getId()) {
            $metadata = $auditLog->metadata ?? [];
            $metadata['session_id'] = session()->getId();
            $auditLog->metadata = $metadata;
        }

        // Add request information for web requests
        if (request()->hasHeader('referer')) {
            $metadata = $auditLog->metadata ?? [];
            $metadata['referer'] = request()->header('referer');
            $auditLog->metadata = $metadata;
        }
    }

    /**
     * Handle the AuditLog "created" event.
     */
    public function created(AuditLog $auditLog): void
    {
        // Log critical actions to system log
        $criticalActions = [
            'deleted',
            'force_deleted',
            'role_changed',
            'permissions_changed',
            'system_config_changed',
            'backup_deleted',
            'security_breach'
        ];

        if (in_array($auditLog->action, $criticalActions)) {
            Log::warning('Critical action logged in audit', [
                'audit_id' => $auditLog->id,
                'user_id' => $auditLog->user_id,
                'action' => $auditLog->action,
                'model_type' => $auditLog->model_type,
                'model_id' => $auditLog->model_id,
                'ip_address' => $auditLog->ip_address,
            ]);

            // Notify administrators about critical actions
            $this->notifyAdministratorsOfCriticalAction($auditLog);
        }

        // Check for suspicious activity patterns
        $this->checkForSuspiciousActivity($auditLog);

        // Update user activity timestamp
        if ($auditLog->user_id) {
            try {
                \App\Models\User::where('id', $auditLog->user_id)
                    ->update(['last_activity_at' => now()]);
            } catch (\Exception $e) {
                Log::error('Failed to update user activity timestamp', [
                    'user_id' => $auditLog->user_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Handle the AuditLog "deleting" event.
     */
    public function deleting(AuditLog $auditLog): void
    {
        // Audit logs should generally not be deleted
        Log::warning('Audit log being deleted', [
            'audit_id' => $auditLog->id,
            'action' => $auditLog->action,
            'model_type' => $auditLog->model_type,
            'model_id' => $auditLog->model_id,
            'deleted_by' => auth()->id(),
        ]);

        // Only allow deletion by super administrators
        if (!auth()->check() || auth()->user()->role !== 'super_administrador') {
            Log::critical('Unauthorized attempt to delete audit log', [
                'audit_id' => $auditLog->id,
                'attempted_by' => auth()->id(),
                'ip_address' => request()->ip(),
            ]);

            // Prevent deletion
            return false;
        }
    }

    /**
     * Handle the AuditLog "deleted" event.
     */
    public function deleted(AuditLog $auditLog): void
    {
        Log::critical('Audit log deleted', [
            'audit_id' => $auditLog->id,
            'action' => $auditLog->action,
            'model_type' => $auditLog->model_type,
            'model_id' => $auditLog->model_id,
            'deleted_by' => auth()->id(),
            'ip_address' => request()->ip(),
        ]);

        // Create a new audit log for the deletion
        try {
            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'audit_log_deleted',
                'model_type' => AuditLog::class,
                'model_id' => $auditLog->id,
                'old_values' => [
                    'action' => $auditLog->action,
                    'model_type' => $auditLog->model_type,
                    'model_id' => $auditLog->model_id,
                    'created_at' => $auditLog->created_at,
                ],
                'new_values' => null,
                'metadata' => [
                    'deletion_reason' => request()->input('deletion_reason'),
                    'original_audit_id' => $auditLog->id,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create audit log for audit log deletion', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify administrators about critical actions.
     */
    private function notifyAdministratorsOfCriticalAction(AuditLog $auditLog): void
    {
        try {
            $administrators = \App\Models\User::where('role', 'administrador')
                                             ->where('activo', true)
                                             ->get();

            $actionMessages = [
                'deleted' => 'Elemento eliminado del sistema',
                'force_deleted' => 'Elemento eliminado permanentemente',
                'role_changed' => 'Rol de usuario modificado',
                'permissions_changed' => 'Permisos de usuario modificados',
                'system_config_changed' => 'Configuración del sistema modificada',
                'backup_deleted' => 'Backup del sistema eliminado',
                'security_breach' => 'Posible brecha de seguridad detectada',
            ];

            $message = $actionMessages[$auditLog->action] ?? "Acción crítica: {$auditLog->action}";

            foreach ($administrators as $admin) {
                \App\Models\Notification::create([
                    'user_id' => $admin->id,
                    'type' => 'security_alert',
                    'title' => 'Alerta de Seguridad',
                    'message' => $message,
                    'data' => [
                        'audit_id' => $auditLog->id,
                        'action' => $auditLog->action,
                        'model_type' => $auditLog->model_type,
                        'model_id' => $auditLog->model_id,
                        'performed_by' => $auditLog->user_id,
                        'ip_address' => $auditLog->ip_address,
                        'timestamp' => $auditLog->created_at->toISOString(),
                    ],
                    'priority' => 'urgent',
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify administrators of critical action', [
                'audit_id' => $auditLog->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check for suspicious activity patterns.
     */
    private function checkForSuspiciousActivity(AuditLog $auditLog): void
    {
        if (!$auditLog->user_id) {
            return;
        }

        try {
            // Check for rapid successive actions (potential automation/bot)
            $recentActions = AuditLog::where('user_id', $auditLog->user_id)
                                   ->where('created_at', '>=', now()->subMinutes(5))
                                   ->count();

            if ($recentActions > 50) {
                Log::warning('Suspicious activity: High frequency actions detected', [
                    'user_id' => $auditLog->user_id,
                    'actions_in_5_minutes' => $recentActions,
                    'ip_address' => $auditLog->ip_address,
                ]);

                $this->createSecurityAlert($auditLog->user_id, 'high_frequency_actions', [
                    'actions_count' => $recentActions,
                    'time_window' => '5 minutes',
                ]);
            }

            // Check for actions from multiple IP addresses
            $recentIPs = AuditLog::where('user_id', $auditLog->user_id)
                                ->where('created_at', '>=', now()->subHour())
                                ->distinct('ip_address')
                                ->count('ip_address');

            if ($recentIPs > 3) {
                Log::warning('Suspicious activity: Multiple IP addresses detected', [
                    'user_id' => $auditLog->user_id,
                    'ip_count' => $recentIPs,
                    'current_ip' => $auditLog->ip_address,
                ]);

                $this->createSecurityAlert($auditLog->user_id, 'multiple_ip_addresses', [
                    'ip_count' => $recentIPs,
                    'time_window' => '1 hour',
                ]);
            }

            // Check for unusual time patterns (actions outside normal hours)
            $hour = now()->hour;
            if ($hour < 6 || $hour > 22) {
                $offHoursActions = AuditLog::where('user_id', $auditLog->user_id)
                                         ->whereRaw('HOUR(created_at) < 6 OR HOUR(created_at) > 22')
                                         ->where('created_at', '>=', now()->subDays(7))
                                         ->count();

                if ($offHoursActions > 10) {
                    Log::info('Unusual activity pattern: Off-hours activity detected', [
                        'user_id' => $auditLog->user_id,
                        'off_hours_actions' => $offHoursActions,
                        'current_hour' => $hour,
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('Failed to check for suspicious activity', [
                'audit_id' => $auditLog->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Create a security alert.
     */
    private function createSecurityAlert(int $userId, string $alertType, array $data): void
    {
        try {
            $administrators = \App\Models\User::where('role', 'administrador')
                                             ->where('activo', true)
                                             ->get();

            $alertMessages = [
                'high_frequency_actions' => 'Actividad de alta frecuencia detectada',
                'multiple_ip_addresses' => 'Acceso desde múltiples direcciones IP',
                'unusual_time_pattern' => 'Patrón de tiempo inusual detectado',
            ];

            $message = $alertMessages[$alertType] ?? "Actividad sospechosa: {$alertType}";

            foreach ($administrators as $admin) {
                \App\Models\Notification::create([
                    'user_id' => $admin->id,
                    'type' => 'security_alert',
                    'title' => 'Alerta de Seguridad - Actividad Sospechosa',
                    'message' => $message,
                    'data' => array_merge($data, [
                        'alert_type' => $alertType,
                        'suspicious_user_id' => $userId,
                        'detected_at' => now()->toISOString(),
                    ]),
                    'priority' => 'high',
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to create security alert', [
                'user_id' => $userId,
                'alert_type' => $alertType,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
