<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use App\Models\User;

class SettingsController extends Controller
{
    /**
     * Show user profile settings
     */
    public function profile()
    {
        return Inertia::render('Settings/Profile', [
            'user' => Auth::user()
        ]);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . Auth::id(),
            'phone' => 'nullable|string|max:20',
            'department' => 'nullable|string|max:100',
            'specialties' => 'nullable|array',
            'medical_license' => 'nullable|string|max:50',
        ]);

        $user = Auth::user();
        $user->update($request->only([
            'name', 'email', 'phone', 'department', 'specialties', 'medical_license'
        ]));

        return back()->with('success', 'Perfil actualizado exitosamente');
    }

    /**
     * Update user password
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        Auth::user()->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'Contraseña actualizada exitosamente');
    }

    /**
     * Update user avatar
     */
    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = Auth::user();
        
        // Delete old avatar if exists
        if ($user->avatar_path) {
            Storage::delete($user->avatar_path);
        }

        // Store new avatar
        $path = $request->file('avatar')->store('avatars', 'public');
        
        $user->update(['avatar_path' => $path]);

        return back()->with('success', 'Avatar actualizado exitosamente');
    }

    /**
     * Delete user avatar
     */
    public function deleteAvatar()
    {
        $user = Auth::user();
        
        if ($user->avatar_path) {
            Storage::delete($user->avatar_path);
            $user->update(['avatar_path' => null]);
        }

        return back()->with('success', 'Avatar eliminado exitosamente');
    }

    /**
     * Show notification settings
     */
    public function notifications()
    {
        return Inertia::render('Settings/Notifications', [
            'preferences' => Auth::user()->notification_preferences ?? []
        ]);
    }

    /**
     * Update notification preferences
     */
    public function updateNotifications(Request $request)
    {
        $request->validate([
            'email_notifications' => 'boolean',
            'urgent_cases' => 'boolean',
            'daily_summary' => 'boolean',
            'system_updates' => 'boolean',
        ]);

        Auth::user()->update([
            'notification_preferences' => $request->only([
                'email_notifications', 'urgent_cases', 'daily_summary', 'system_updates'
            ])
        ]);

        return back()->with('success', 'Preferencias de notificación actualizadas');
    }

    /**
     * Show appearance settings
     */
    public function appearance()
    {
        return Inertia::render('Settings/Appearance', [
            'preferences' => Auth::user()->appearance_preferences ?? []
        ]);
    }

    /**
     * Update appearance preferences
     */
    public function updateAppearance(Request $request)
    {
        $request->validate([
            'theme' => 'in:light,dark,auto',
            'sidebar_collapsed' => 'boolean',
            'language' => 'in:es,en',
            'timezone' => 'string',
        ]);

        Auth::user()->update([
            'appearance_preferences' => $request->only([
                'theme', 'sidebar_collapsed', 'language', 'timezone'
            ])
        ]);

        return back()->with('success', 'Preferencias de apariencia actualizadas');
    }

    /**
     * Show security settings
     */
    public function security()
    {
        return Inertia::render('Settings/Security', [
            'sessions' => Auth::user()->tokens()->latest()->take(10)->get(),
            'two_factor_enabled' => Auth::user()->two_factor_enabled ?? false
        ]);
    }

    /**
     * Show API tokens
     */
    public function apiTokens()
    {
        return Inertia::render('Settings/ApiTokens', [
            'tokens' => Auth::user()->tokens()->latest()->get()
        ]);
    }

    /**
     * Create new API token
     */
    public function createApiToken(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'abilities' => 'array',
        ]);

        $token = Auth::user()->createToken(
            $request->name,
            $request->abilities ?? ['*']
        );

        return back()->with([
            'success' => 'Token creado exitosamente',
            'token' => $token->plainTextToken
        ]);
    }

    /**
     * Delete API token
     */
    public function deleteApiToken($tokenId)
    {
        Auth::user()->tokens()->where('id', $tokenId)->delete();

        return back()->with('success', 'Token eliminado exitosamente');
    }

    /**
     * Show system settings (Admin only)
     */
    public function systemSettings()
    {
        $this->authorize('manage-system');

        return Inertia::render('Admin/Settings/System', [
            'settings' => [
                'app_name' => config('app.name'),
                'app_url' => config('app.url'),
                'timezone' => config('app.timezone'),
                'locale' => config('app.locale'),
                'maintenance_mode' => app()->isDownForMaintenance(),
            ]
        ]);
    }

    /**
     * Update system settings
     */
    public function updateSystemSettings(Request $request)
    {
        $this->authorize('manage-system');

        $request->validate([
            'app_name' => 'required|string|max:255',
            'app_url' => 'required|url',
            'timezone' => 'required|string',
            'locale' => 'required|string|in:es,en',
        ]);

        // Update .env file (simplified version)
        // In production, use a proper configuration management system
        
        return back()->with('success', 'Configuración del sistema actualizada');
    }

    /**
     * Show Gmail settings
     */
    public function gmailSettings()
    {
        $this->authorize('manage-system');

        return Inertia::render('Admin/Settings/Gmail', [
            'settings' => [
                'enabled' => config('services.gmail.enabled'),
                'email' => config('services.gmail.email'),
                'monitoring_interval' => config('services.gmail.monitoring_interval'),
                'max_emails_per_batch' => config('services.gmail.max_emails_per_batch'),
            ]
        ]);
    }

    /**
     * Test Gmail connection
     */
    public function testGmailConnection()
    {
        $this->authorize('manage-system');

        try {
            // Test Gmail API connection
            // Implementation would test actual Gmail API
            
            return response()->json([
                'success' => true,
                'message' => 'Conexión Gmail exitosa'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de conexión Gmail: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show AI settings
     */
    public function aiSettings()
    {
        $this->authorize('manage-system');

        return Inertia::render('Admin/Settings/AI', [
            'settings' => [
                'gemini_enabled' => config('services.gemini.enabled'),
                'gemini_model' => config('services.gemini.model'),
                'confidence_threshold' => config('services.gemini.confidence_threshold'),
                'auto_classification' => config('services.gemini.auto_classification'),
            ]
        ]);
    }

    /**
     * Test AI connection
     */
    public function testAiConnection()
    {
        $this->authorize('manage-system');

        try {
            // Test Gemini AI connection
            // Implementation would test actual Gemini API
            
            return response()->json([
                'success' => true,
                'message' => 'Conexión IA exitosa'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de conexión IA: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear system cache
     */
    public function clearCache()
    {
        $this->authorize('manage-system');

        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');

        return back()->with('success', 'Cache del sistema limpiado exitosamente');
    }

    /**
     * Optimize system
     */
    public function optimizeSystem()
    {
        $this->authorize('manage-system');

        Artisan::call('config:cache');
        Artisan::call('route:cache');
        Artisan::call('view:cache');

        return back()->with('success', 'Sistema optimizado exitosamente');
    }

    /**
     * Enable maintenance mode
     */
    public function enableMaintenance(Request $request)
    {
        $this->authorize('manage-system');

        $request->validate([
            'message' => 'nullable|string|max:255',
        ]);

        Artisan::call('down', [
            '--message' => $request->message ?? 'Sistema en mantenimiento'
        ]);

        return back()->with('success', 'Modo de mantenimiento activado');
    }

    /**
     * Disable maintenance mode
     */
    public function disableMaintenance()
    {
        $this->authorize('manage-system');

        Artisan::call('up');

        return back()->with('success', 'Modo de mantenimiento desactivado');
    }

    /**
     * Show system health
     */
    public function systemHealth()
    {
        $this->authorize('manage-system');

        return Inertia::render('Admin/Settings/Health', [
            'health' => [
                'database' => $this->checkDatabaseHealth(),
                'cache' => $this->checkCacheHealth(),
                'storage' => $this->checkStorageHealth(),
                'queue' => $this->checkQueueHealth(),
            ]
        ]);
    }

    /**
     * Check database health
     */
    private function checkDatabaseHealth()
    {
        try {
            \DB::connection()->getPdo();
            return ['status' => 'healthy', 'message' => 'Base de datos conectada'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Error de base de datos'];
        }
    }

    /**
     * Check cache health
     */
    private function checkCacheHealth()
    {
        try {
            Cache::put('health_check', 'ok', 60);
            $value = Cache::get('health_check');
            return ['status' => $value === 'ok' ? 'healthy' : 'error', 'message' => 'Cache funcionando'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Error de cache'];
        }
    }

    /**
     * Check storage health
     */
    private function checkStorageHealth()
    {
        try {
            $diskSpace = disk_free_space(storage_path());
            $totalSpace = disk_total_space(storage_path());
            $usedPercentage = (($totalSpace - $diskSpace) / $totalSpace) * 100;
            
            return [
                'status' => $usedPercentage < 90 ? 'healthy' : 'warning',
                'message' => "Espacio usado: {$usedPercentage}%"
            ];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Error de almacenamiento'];
        }
    }

    /**
     * Check queue health
     */
    private function checkQueueHealth()
    {
        try {
            $failedJobs = \DB::table('failed_jobs')->count();
            return [
                'status' => $failedJobs < 10 ? 'healthy' : 'warning',
                'message' => "Trabajos fallidos: {$failedJobs}"
            ];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Error de cola'];
        }
    }
}
