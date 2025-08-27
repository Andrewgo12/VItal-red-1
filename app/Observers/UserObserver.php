<?php

namespace App\Observers;

use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    /**
     * Handle the User "creating" event.
     */
    public function creating(User $user): void
    {
        // Set default values
        if (is_null($user->activo)) {
            $user->activo = true;
        }

        if (empty($user->email_verified_at) && app()->environment('local')) {
            $user->email_verified_at = now();
        }

        Log::info('Creating new user', [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'especialidad' => $user->especialidad,
        ]);
    }

    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        // Create audit log
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'created',
            'model_type' => User::class,
            'model_id' => $user->id,
            'old_values' => null,
            'new_values' => $user->only([
                'name', 'email', 'role', 'especialidad', 'activo'
            ]),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => [
                'created_by' => auth()->id(),
                'registration_method' => 'manual',
            ]
        ]);

        Log::info('User created successfully', [
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ]);

        // Send welcome notification to administrators
        $this->notifyAdministrators($user, 'created');

        // Initialize user preferences
        $this->initializeUserPreferences($user);
    }

    /**
     * Handle the User "updating" event.
     */
    public function updating(User $user): void
    {
        // Log sensitive changes
        $sensitiveFields = ['email', 'role', 'activo', 'especialidad'];
        $changes = [];

        foreach ($sensitiveFields as $field) {
            if ($user->isDirty($field)) {
                $changes[$field] = [
                    'old' => $user->getOriginal($field),
                    'new' => $user->getAttribute($field)
                ];
            }
        }

        if (!empty($changes)) {
            Log::warning('Sensitive user data being updated', [
                'user_id' => $user->id,
                'updated_by' => auth()->id(),
                'changes' => $changes,
            ]);
        }
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        // Create audit log for changes
        $changes = $user->getChanges();
        
        if (!empty($changes)) {
            // Remove sensitive data from audit log
            unset($changes['password'], $changes['remember_token']);

            AuditLog::create([
                'user_id' => auth()->id() ?? $user->id,
                'action' => 'updated',
                'model_type' => User::class,
                'model_id' => $user->id,
                'old_values' => array_intersect_key($user->getOriginal(), $changes),
                'new_values' => $changes,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => [
                    'updated_by' => auth()->id(),
                    'fields_changed' => array_keys($changes),
                ]
            ]);

            Log::info('User updated', [
                'user_id' => $user->id,
                'updated_by' => auth()->id(),
                'fields_changed' => array_keys($changes),
            ]);

            // Notify about role changes
            if (array_key_exists('role', $changes)) {
                $this->notifyRoleChange($user, $user->getOriginal('role'), $changes['role']);
            }

            // Notify about status changes
            if (array_key_exists('activo', $changes)) {
                $this->notifyStatusChange($user, $changes['activo']);
            }
        }
    }

    /**
     * Handle the User "deleting" event.
     */
    public function deleting(User $user): void
    {
        Log::warning('User being deleted', [
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'deleted_by' => auth()->id(),
        ]);

        // Check if user has active medical cases
        $activeCases = $user->solicitudesMedicasEvaluador()
                           ->whereIn('estado', ['pendiente_evaluacion', 'en_evaluacion'])
                           ->count();

        if ($activeCases > 0) {
            Log::warning('Deleting user with active medical cases', [
                'user_id' => $user->id,
                'active_cases' => $activeCases,
            ]);
        }
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        // Create audit log
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'deleted',
            'model_type' => User::class,
            'model_id' => $user->id,
            'old_values' => $user->only([
                'name', 'email', 'role', 'especialidad', 'activo'
            ]),
            'new_values' => null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => [
                'deleted_by' => auth()->id(),
                'deletion_reason' => request()->input('deletion_reason'),
            ]
        ]);

        Log::warning('User deleted', [
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'deleted_by' => auth()->id(),
        ]);

        // Notify administrators
        $this->notifyAdministrators($user, 'deleted');
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        // Create audit log
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'restored',
            'model_type' => User::class,
            'model_id' => $user->id,
            'old_values' => null,
            'new_values' => $user->only([
                'name', 'email', 'role', 'especialidad', 'activo'
            ]),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => [
                'restored_by' => auth()->id(),
            ]
        ]);

        Log::info('User restored', [
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'restored_by' => auth()->id(),
        ]);
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        Log::critical('User force deleted', [
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'deleted_by' => auth()->id(),
        ]);

        // This is a permanent deletion - notify all administrators
        $this->notifyAdministrators($user, 'force_deleted');
    }

    /**
     * Notify administrators about user changes.
     */
    private function notifyAdministrators(User $user, string $action): void
    {
        try {
            $administrators = User::where('role', 'administrador')
                                 ->where('activo', true)
                                 ->where('id', '!=', $user->id)
                                 ->get();

            $messages = [
                'created' => "Nuevo usuario registrado: {$user->name} ({$user->role})",
                'deleted' => "Usuario eliminado: {$user->name} ({$user->role})",
                'force_deleted' => "Usuario eliminado permanentemente: {$user->name} ({$user->role})",
            ];

            foreach ($administrators as $admin) {
                \App\Models\Notification::create([
                    'user_id' => $admin->id,
                    'type' => 'user_management',
                    'title' => 'Gestión de Usuarios',
                    'message' => $messages[$action] ?? "Usuario {$action}: {$user->name}",
                    'data' => [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'user_email' => $user->email,
                        'user_role' => $user->role,
                        'action' => $action,
                        'performed_by' => auth()->id(),
                    ],
                    'priority' => $action === 'force_deleted' ? 'high' : 'medium',
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify administrators about user change', [
                'user_id' => $user->id,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify about role changes.
     */
    private function notifyRoleChange(User $user, string $oldRole, string $newRole): void
    {
        try {
            $administrators = User::where('role', 'administrador')
                                 ->where('activo', true)
                                 ->where('id', '!=', $user->id)
                                 ->get();

            foreach ($administrators as $admin) {
                \App\Models\Notification::create([
                    'user_id' => $admin->id,
                    'type' => 'role_change',
                    'title' => 'Cambio de Rol de Usuario',
                    'message' => "Rol de {$user->name} cambió de {$oldRole} a {$newRole}",
                    'data' => [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'old_role' => $oldRole,
                        'new_role' => $newRole,
                        'changed_by' => auth()->id(),
                    ],
                    'priority' => 'high',
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify role change', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify about status changes.
     */
    private function notifyStatusChange(User $user, bool $newStatus): void
    {
        try {
            $action = $newStatus ? 'activado' : 'desactivado';
            
            $administrators = User::where('role', 'administrador')
                                 ->where('activo', true)
                                 ->where('id', '!=', $user->id)
                                 ->get();

            foreach ($administrators as $admin) {
                \App\Models\Notification::create([
                    'user_id' => $admin->id,
                    'type' => 'status_change',
                    'title' => 'Cambio de Estado de Usuario',
                    'message' => "Usuario {$user->name} ha sido {$action}",
                    'data' => [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'new_status' => $newStatus,
                        'changed_by' => auth()->id(),
                    ],
                    'priority' => 'medium',
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify status change', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Initialize user preferences.
     */
    private function initializeUserPreferences(User $user): void
    {
        try {
            // Set default notification preferences
            $user->update([
                'email_notifications_enabled' => true,
                'push_notifications_enabled' => true,
                'sms_notifications_enabled' => false,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to initialize user preferences', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
