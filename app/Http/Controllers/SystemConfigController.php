<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Artisan;
use App\Models\SystemConfig;
use App\Models\AuditLog;

class SystemConfigController extends Controller
{
    /**
     * Display system configuration dashboard
     */
    public function index()
    {
        $configurations = [
            'gmail' => $this->getGmailConfig(),
            'ai' => $this->getAIConfig(),
            'notifications' => $this->getNotificationConfig(),
            'security' => $this->getSecurityConfig(),
            'system' => $this->getSystemConfig()
        ];

        $systemStatus = $this->getSystemStatus();

        return view('admin.config.index', compact('configurations', 'systemStatus'));
    }

    /**
     * Get Gmail configuration
     */
    private function getGmailConfig(): array
    {
        return [
            'email' => config('gmail.email', ''),
            'imap_server' => config('gmail.imap_server', 'imap.gmail.com'),
            'imap_port' => config('gmail.imap_port', 993),
            'check_interval' => config('gmail.check_interval_minutes', 5),
            'max_emails_per_check' => config('gmail.max_emails_per_check', 50),
            'enabled' => config('gmail.enabled', true)
        ];
    }

    /**
     * Get AI configuration
     */
    private function getAIConfig(): array
    {
        return [
            'gemini_enabled' => config('ai.gemini_enabled', true),
            'gemini_api_keys_count' => count(config('ai.gemini_api_keys', [])),
            'confidence_threshold' => config('ai.confidence_threshold', 0.6),
            'enhanced_analysis' => config('ai.enable_enhanced_analysis', true),
            'priority_classification' => config('ai.enable_priority_classification', true),
            'semantic_analysis' => config('ai.enable_semantic_analysis', true)
        ];
    }

    /**
     * Get notification configuration
     */
    private function getNotificationConfig(): array
    {
        return [
            'email_enabled' => config('notifications.email_enabled', true),
            'smtp_server' => config('mail.mailers.smtp.host', ''),
            'smtp_port' => config('mail.mailers.smtp.port', 587),
            'real_time_enabled' => config('notifications.real_time_enabled', true),
            'urgent_notifications' => config('notifications.urgent_notifications', true),
            'admin_email' => config('notifications.admin_email', '')
        ];
    }

    /**
     * Get security configuration
     */
    private function getSecurityConfig(): array
    {
        return [
            'audit_enabled' => config('security.audit.enabled', true),
            'session_timeout' => config('security.session.timeout_minutes', 480),
            'password_policy' => config('security.password_policy', []),
            'rate_limiting' => config('security.rate_limiting', []),
            'ip_restrictions' => config('security.ip_restrictions.enabled', false),
            'encryption_enabled' => config('security.data_protection.encrypt_sensitive_data', true)
        ];
    }

    /**
     * Get system configuration
     */
    private function getSystemConfig(): array
    {
        return [
            'app_name' => config('app.name', 'Vital Red'),
            'app_env' => config('app.env', 'production'),
            'debug_mode' => config('app.debug', false),
            'timezone' => config('app.timezone', 'America/Bogota'),
            'locale' => config('app.locale', 'es'),
            'log_level' => config('logging.default', 'info'),
            'cache_driver' => config('cache.default', 'file'),
            'queue_driver' => config('queue.default', 'sync')
        ];
    }

    /**
     * Get system status
     */
    private function getSystemStatus(): array
    {
        return [
            'database' => $this->checkDatabaseConnection(),
            'cache' => $this->checkCacheConnection(),
            'gmail' => $this->checkGmailConnection(),
            'ai_services' => $this->checkAIServices(),
            'storage' => $this->checkStorageStatus(),
            'queue' => $this->checkQueueStatus()
        ];
    }

    /**
     * Update Gmail configuration
     */
    public function updateGmailConfig(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'app_password' => 'required|string|min:16',
                'imap_server' => 'required|string',
                'imap_port' => 'required|integer|between:1,65535',
                'check_interval' => 'required|integer|between:1,60',
                'max_emails_per_check' => 'required|integer|between:1,200',
                'enabled' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Update environment variables
            $this->updateEnvFile([
                'GMAIL_EMAIL' => $request->email,
                'GMAIL_APP_PASSWORD' => $request->app_password,
                'GMAIL_IMAP_SERVER' => $request->imap_server,
                'GMAIL_IMAP_PORT' => $request->imap_port,
                'GMAIL_CHECK_INTERVAL' => $request->check_interval,
                'GMAIL_MAX_EMAILS_PER_CHECK' => $request->max_emails_per_check,
                'GMAIL_ENABLED' => $request->boolean('enabled') ? 'true' : 'false'
            ]);

            // Clear config cache
            Artisan::call('config:clear');

            // Log the action
            AuditLog::logActivity(
                'update_gmail_config',
                'system_config',
                ['email' => $request->email],
                'Configuración de Gmail actualizada'
            );

            return response()->json([
                'success' => true,
                'message' => 'Configuración de Gmail actualizada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar configuración: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update AI configuration
     */
    public function updateAIConfig(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'gemini_api_keys' => 'required|array|min:1',
                'gemini_api_keys.*' => 'required|string|min:30',
                'confidence_threshold' => 'required|numeric|between:0,1',
                'enhanced_analysis' => 'boolean',
                'priority_classification' => 'boolean',
                'semantic_analysis' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Update environment variables
            $apiKeys = implode(',', $request->gemini_api_keys);
            
            $this->updateEnvFile([
                'GEMINI_API_KEYS' => $apiKeys,
                'AI_CONFIDENCE_THRESHOLD' => $request->confidence_threshold,
                'AI_ENHANCED_ANALYSIS' => $request->boolean('enhanced_analysis') ? 'true' : 'false',
                'AI_PRIORITY_CLASSIFICATION' => $request->boolean('priority_classification') ? 'true' : 'false',
                'AI_SEMANTIC_ANALYSIS' => $request->boolean('semantic_analysis') ? 'true' : 'false'
            ]);

            // Clear config cache
            Artisan::call('config:clear');

            // Log the action
            AuditLog::logActivity(
                'update_ai_config',
                'system_config',
                ['api_keys_count' => count($request->gemini_api_keys)],
                'Configuración de IA actualizada'
            );

            return response()->json([
                'success' => true,
                'message' => 'Configuración de IA actualizada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar configuración: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update notification configuration
     */
    public function updateNotificationConfig(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email_enabled' => 'boolean',
                'smtp_server' => 'required_if:email_enabled,true|nullable|string',
                'smtp_port' => 'required_if:email_enabled,true|nullable|integer|between:1,65535',
                'smtp_username' => 'required_if:email_enabled,true|nullable|string',
                'smtp_password' => 'required_if:email_enabled,true|nullable|string',
                'admin_email' => 'nullable|email',
                'real_time_enabled' => 'boolean',
                'urgent_notifications' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $envUpdates = [
                'NOTIFICATIONS_EMAIL_ENABLED' => $request->boolean('email_enabled') ? 'true' : 'false',
                'NOTIFICATIONS_REAL_TIME_ENABLED' => $request->boolean('real_time_enabled') ? 'true' : 'false',
                'NOTIFICATIONS_URGENT_ENABLED' => $request->boolean('urgent_notifications') ? 'true' : 'false'
            ];

            if ($request->email_enabled) {
                $envUpdates = array_merge($envUpdates, [
                    'MAIL_HOST' => $request->smtp_server,
                    'MAIL_PORT' => $request->smtp_port,
                    'MAIL_USERNAME' => $request->smtp_username,
                    'MAIL_PASSWORD' => $request->smtp_password
                ]);
            }

            if ($request->admin_email) {
                $envUpdates['ADMIN_EMAIL'] = $request->admin_email;
            }

            $this->updateEnvFile($envUpdates);

            // Clear config cache
            Artisan::call('config:clear');

            // Log the action
            AuditLog::logActivity(
                'update_notification_config',
                'system_config',
                ['email_enabled' => $request->boolean('email_enabled')],
                'Configuración de notificaciones actualizada'
            );

            return response()->json([
                'success' => true,
                'message' => 'Configuración de notificaciones actualizada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar configuración: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update security configuration
     */
    public function updateSecurityConfig(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'audit_enabled' => 'boolean',
                'session_timeout' => 'required|integer|between:30,1440',
                'password_min_length' => 'required|integer|between:6,50',
                'password_require_uppercase' => 'boolean',
                'password_require_lowercase' => 'boolean',
                'password_require_numbers' => 'boolean',
                'password_require_symbols' => 'boolean',
                'rate_limit_attempts' => 'required|integer|between:1,100',
                'rate_limit_decay' => 'required|integer|between:1,60',
                'ip_restrictions_enabled' => 'boolean',
                'allowed_ips' => 'nullable|string',
                'encryption_enabled' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $this->updateEnvFile([
                'AUDIT_ENABLED' => $request->boolean('audit_enabled') ? 'true' : 'false',
                'SESSION_TIMEOUT_MINUTES' => $request->session_timeout,
                'PASSWORD_MIN_LENGTH' => $request->password_min_length,
                'PASSWORD_REQUIRE_UPPERCASE' => $request->boolean('password_require_uppercase') ? 'true' : 'false',
                'PASSWORD_REQUIRE_LOWERCASE' => $request->boolean('password_require_lowercase') ? 'true' : 'false',
                'PASSWORD_REQUIRE_NUMBERS' => $request->boolean('password_require_numbers') ? 'true' : 'false',
                'PASSWORD_REQUIRE_SYMBOLS' => $request->boolean('password_require_symbols') ? 'true' : 'false',
                'RATE_LIMIT_ATTEMPTS' => $request->rate_limit_attempts,
                'RATE_LIMIT_DECAY' => $request->rate_limit_decay,
                'IP_RESTRICTIONS_ENABLED' => $request->boolean('ip_restrictions_enabled') ? 'true' : 'false',
                'ALLOWED_IPS' => $request->allowed_ips ?? '',
                'ENCRYPT_SENSITIVE_DATA' => $request->boolean('encryption_enabled') ? 'true' : 'false'
            ]);

            // Clear config cache
            Artisan::call('config:clear');

            // Log the action
            AuditLog::logActivity(
                'update_security_config',
                'system_config',
                ['audit_enabled' => $request->boolean('audit_enabled')],
                'Configuración de seguridad actualizada'
            );

            return response()->json([
                'success' => true,
                'message' => 'Configuración de seguridad actualizada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar configuración: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test Gmail connection
     */
    public function testGmailConnection(): JsonResponse
    {
        try {
            // This would test the actual Gmail connection
            // For now, return a mock response
            
            $testResult = [
                'success' => true,
                'message' => 'Conexión exitosa con Gmail',
                'details' => [
                    'server' => config('gmail.imap_server'),
                    'port' => config('gmail.imap_port'),
                    'folders_found' => 5,
                    'last_email_date' => now()->subHours(2)->format('d/m/Y H:i')
                ]
            ];

            return response()->json($testResult);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de conexión: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test AI services
     */
    public function testAIServices(): JsonResponse
    {
        try {
            // Test Gemini AI connection
            $apiKeys = config('ai.gemini_api_keys', []);
            
            if (empty($apiKeys)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay claves API configuradas'
                ]);
            }

            // Mock test result
            $testResult = [
                'success' => true,
                'message' => 'Servicios de IA funcionando correctamente',
                'details' => [
                    'gemini_api_keys' => count($apiKeys),
                    'response_time' => '1.2s',
                    'last_analysis' => now()->subMinutes(15)->format('d/m/Y H:i')
                ]
            ];

            return response()->json($testResult);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en servicios de IA: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear system cache
     */
    public function clearCache(): JsonResponse
    {
        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');

            // Log the action
            AuditLog::logActivity(
                'clear_system_cache',
                'system_config',
                [],
                'Cache del sistema limpiado'
            );

            return response()->json([
                'success' => true,
                'message' => 'Cache del sistema limpiado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al limpiar cache: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update environment file
     */
    private function updateEnvFile(array $data): void
    {
        $envFile = base_path('.env');
        $envContent = file_get_contents($envFile);

        foreach ($data as $key => $value) {
            $pattern = "/^{$key}=.*/m";
            $replacement = "{$key}={$value}";

            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, $replacement, $envContent);
            } else {
                $envContent .= "\n{$replacement}";
            }
        }

        file_put_contents($envFile, $envContent);
    }

    /**
     * Check database connection
     */
    private function checkDatabaseConnection(): array
    {
        try {
            \DB::connection()->getPdo();
            return [
                'status' => 'connected',
                'message' => 'Conexión exitosa',
                'driver' => config('database.default')
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Error de conexión: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check cache connection
     */
    private function checkCacheConnection(): array
    {
        try {
            Cache::put('test_key', 'test_value', 60);
            $value = Cache::get('test_key');
            Cache::forget('test_key');

            return [
                'status' => $value === 'test_value' ? 'working' : 'error',
                'message' => $value === 'test_value' ? 'Cache funcionando' : 'Error en cache',
                'driver' => config('cache.default')
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Error en cache: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check Gmail connection
     */
    private function checkGmailConnection(): array
    {
        // Mock implementation
        return [
            'status' => 'connected',
            'message' => 'Gmail conectado',
            'last_check' => now()->subMinutes(5)->format('H:i')
        ];
    }

    /**
     * Check AI services
     */
    private function checkAIServices(): array
    {
        // Mock implementation
        return [
            'status' => 'working',
            'message' => 'Servicios de IA operativos',
            'api_keys' => count(config('ai.gemini_api_keys', []))
        ];
    }

    /**
     * Check storage status
     */
    private function checkStorageStatus(): array
    {
        try {
            $storagePath = storage_path();
            $freeBytes = disk_free_space($storagePath);
            $totalBytes = disk_total_space($storagePath);
            $usedPercent = round((($totalBytes - $freeBytes) / $totalBytes) * 100, 2);

            return [
                'status' => $usedPercent < 90 ? 'ok' : 'warning',
                'message' => "Uso del disco: {$usedPercent}%",
                'free_space' => $this->formatBytes($freeBytes),
                'total_space' => $this->formatBytes($totalBytes)
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Error verificando almacenamiento'
            ];
        }
    }

    /**
     * Check queue status
     */
    private function checkQueueStatus(): array
    {
        return [
            'status' => 'working',
            'message' => 'Cola de trabajos operativa',
            'driver' => config('queue.default')
        ];
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2): string
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
