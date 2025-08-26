<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\AuditLog;

class AuditMiddleware
{
    /**
     * Handle an incoming request and log for audit purposes
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);
        
        // Process the request
        $response = $next($request);
        
        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2); // Duration in milliseconds
        
        // Log the request for audit
        $this->logRequest($request, $response, $duration);
        
        return $response;
    }
    
    /**
     * Log request details for audit purposes
     */
    private function logRequest(Request $request, $response, float $duration): void
    {
        try {
            $user = Auth::user();
            $statusCode = method_exists($response, 'getStatusCode') ? $response->getStatusCode() : 200;
            
            // Only log certain routes for audit
            $auditableRoutes = [
                'solicitudes-medicas',
                'gmail-monitor',
                'admin',
                'medico'
            ];
            
            $shouldAudit = false;
            foreach ($auditableRoutes as $route) {
                if (str_contains($request->path(), $route)) {
                    $shouldAudit = true;
                    break;
                }
            }
            
            if (!$shouldAudit) {
                return;
            }
            
            $auditData = [
                'user_id' => $user?->id,
                'user_name' => $user?->name,
                'user_role' => $user?->role,
                'action' => $this->getActionFromRequest($request),
                'resource' => $this->getResourceFromRequest($request),
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'path' => $request->path(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status_code' => $statusCode,
                'duration_ms' => $duration,
                'request_data' => $this->sanitizeRequestData($request),
                'timestamp' => now()
            ];
            
            // Log to database if AuditLog model exists
            if (class_exists(AuditLog::class)) {
                AuditLog::create($auditData);
            }
            
            // Also log to Laravel log for backup
            Log::channel('audit')->info('Request audited', $auditData);
            
        } catch (\Exception $e) {
            Log::error('Error logging audit data: ' . $e->getMessage());
        }
    }
    
    /**
     * Determine the action from the request
     */
    private function getActionFromRequest(Request $request): string
    {
        $method = $request->method();
        $path = $request->path();
        
        // Map HTTP methods to actions
        $actionMap = [
            'GET' => 'view',
            'POST' => 'create',
            'PUT' => 'update',
            'PATCH' => 'update',
            'DELETE' => 'delete'
        ];
        
        $baseAction = $actionMap[$method] ?? 'unknown';
        
        // Specific action detection
        if (str_contains($path, 'evaluar')) {
            return 'evaluate_medical_request';
        } elseif (str_contains($path, 'gmail-monitor/start')) {
            return 'start_gmail_monitoring';
        } elseif (str_contains($path, 'gmail-monitor/stop')) {
            return 'stop_gmail_monitoring';
        } elseif (str_contains($path, 'solicitudes-medicas')) {
            return $baseAction . '_medical_request';
        } elseif (str_contains($path, 'bandeja-casos')) {
            return 'view_medical_cases';
        }
        
        return $baseAction;
    }
    
    /**
     * Determine the resource from the request
     */
    private function getResourceFromRequest(Request $request): string
    {
        $path = $request->path();
        
        if (str_contains($path, 'solicitudes-medicas')) {
            return 'medical_request';
        } elseif (str_contains($path, 'gmail-monitor')) {
            return 'gmail_monitor';
        } elseif (str_contains($path, 'bandeja-casos')) {
            return 'medical_cases';
        } elseif (str_contains($path, 'admin')) {
            return 'admin_panel';
        } elseif (str_contains($path, 'medico')) {
            return 'medical_area';
        }
        
        return 'unknown';
    }
    
    /**
     * Sanitize request data for logging (remove sensitive information)
     */
    private function sanitizeRequestData(Request $request): array
    {
        $data = $request->all();
        
        // Remove sensitive fields
        $sensitiveFields = [
            'password',
            'password_confirmation',
            'token',
            'api_key',
            '_token',
            'csrf_token'
        ];
        
        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[REDACTED]';
            }
        }
        
        // Limit data size for storage
        $jsonData = json_encode($data);
        if (strlen($jsonData) > 5000) {
            return ['message' => 'Request data too large for audit log'];
        }
        
        return $data;
    }
}
