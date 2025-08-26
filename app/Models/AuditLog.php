<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    protected $fillable = [
        'user_id',
        'user_name',
        'user_role',
        'action',
        'resource',
        'method',
        'url',
        'path',
        'ip_address',
        'user_agent',
        'status_code',
        'duration_ms',
        'request_data',
        'metadata',
        'description',
        'timestamp'
    ];

    protected $casts = [
        'request_data' => 'array',
        'metadata' => 'array',
        'timestamp' => 'datetime',
        'duration_ms' => 'decimal:2'
    ];

    /**
     * Relationship with User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for filtering by action
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope for filtering by resource
     */
    public function scopeByResource($query, string $resource)
    {
        return $query->where('resource', $resource);
    }

    /**
     * Scope for filtering by user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for filtering by date range
     */
    public function scopeBetweenDates($query, Carbon $startDate, Carbon $endDate)
    {
        return $query->whereBetween('timestamp', [$startDate, $endDate]);
    }

    /**
     * Scope for filtering by IP address
     */
    public function scopeByIpAddress($query, string $ipAddress)
    {
        return $query->where('ip_address', $ipAddress);
    }

    /**
     * Scope for failed requests (4xx, 5xx status codes)
     */
    public function scopeFailedRequests($query)
    {
        return $query->where('status_code', '>=', 400);
    }

    /**
     * Scope for successful requests (2xx, 3xx status codes)
     */
    public function scopeSuccessfulRequests($query)
    {
        return $query->where('status_code', '<', 400);
    }

    /**
     * Scope for recent logs (last 24 hours)
     */
    public function scopeRecent($query)
    {
        return $query->where('timestamp', '>=', now()->subDay());
    }

    /**
     * Scope for high-risk actions
     */
    public function scopeHighRiskActions($query)
    {
        $highRiskActions = [
            'delete_medical_request',
            'start_gmail_monitoring',
            'stop_gmail_monitoring',
            'evaluate_medical_request'
        ];

        return $query->whereIn('action', $highRiskActions);
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDurationAttribute(): string
    {
        if ($this->duration_ms) {
            if ($this->duration_ms >= 1000) {
                return round($this->duration_ms / 1000, 2) . 's';
            } else {
                return $this->duration_ms . 'ms';
            }
        }
        return 'N/A';
    }

    /**
     * Get status category
     */
    public function getStatusCategoryAttribute(): string
    {
        if ($this->status_code >= 500) {
            return 'Server Error';
        } elseif ($this->status_code >= 400) {
            return 'Client Error';
        } elseif ($this->status_code >= 300) {
            return 'Redirect';
        } elseif ($this->status_code >= 200) {
            return 'Success';
        } else {
            return 'Unknown';
        }
    }

    /**
     * Get risk level based on action
     */
    public function getRiskLevelAttribute(): string
    {
        $highRiskActions = [
            'delete_medical_request',
            'start_gmail_monitoring',
            'stop_gmail_monitoring'
        ];

        $mediumRiskActions = [
            'evaluate_medical_request',
            'update_medical_request',
            'create_medical_request'
        ];

        if (in_array($this->action, $highRiskActions)) {
            return 'High';
        } elseif (in_array($this->action, $mediumRiskActions)) {
            return 'Medium';
        } else {
            return 'Low';
        }
    }

    /**
     * Create audit log entry
     */
    public static function logActivity(
        string $action,
        string $resource,
        array $metadata = null,
        string $description = null
    ): self {
        $user = auth()->user();
        $request = request();

        return static::create([
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'user_role' => $user?->role,
            'action' => $action,
            'resource' => $resource,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status_code' => 200, // Default success
            'request_data' => $request->except(['password', 'password_confirmation', '_token']),
            'metadata' => $metadata,
            'description' => $description,
            'timestamp' => now()
        ]);
    }

    /**
     * Get audit summary for dashboard
     */
    public static function getAuditSummary(int $days = 7): array
    {
        $startDate = now()->subDays($days);

        return [
            'total_actions' => static::where('timestamp', '>=', $startDate)->count(),
            'unique_users' => static::where('timestamp', '>=', $startDate)->distinct('user_id')->count(),
            'failed_requests' => static::failedRequests()->where('timestamp', '>=', $startDate)->count(),
            'high_risk_actions' => static::highRiskActions()->where('timestamp', '>=', $startDate)->count(),
            'top_actions' => static::where('timestamp', '>=', $startDate)
                ->selectRaw('action, COUNT(*) as count')
                ->groupBy('action')
                ->orderByDesc('count')
                ->limit(5)
                ->get(),
            'top_users' => static::where('timestamp', '>=', $startDate)
                ->selectRaw('user_name, COUNT(*) as count')
                ->whereNotNull('user_name')
                ->groupBy('user_name')
                ->orderByDesc('count')
                ->limit(5)
                ->get()
        ];
    }

    /**
     * Clean old audit logs (retention policy)
     */
    public static function cleanOldLogs(int $retentionDays = 90): int
    {
        $cutoffDate = now()->subDays($retentionDays);
        return static::where('timestamp', '<', $cutoffDate)->delete();
    }
}
