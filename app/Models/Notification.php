<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'read_at',
        'priority',
        'channel',
        'action_url',
        'expires_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [];

    /**
     * Get the user that owns the notification.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include unread notifications.
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope a query to only include read notifications.
     */
    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Scope a query to only include notifications of a specific type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to only include high priority notifications.
     */
    public function scopeHighPriority($query)
    {
        return $query->where('priority', 'high');
    }

    /**
     * Scope a query to only include urgent notifications.
     */
    public function scopeUrgent($query)
    {
        return $query->where('priority', 'urgent');
    }

    /**
     * Scope a query to only include non-expired notifications.
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($query) {
            $query->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope a query to only include notifications for a specific channel.
     */
    public function scopeForChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    /**
     * Mark the notification as read.
     */
    public function markAsRead(): bool
    {
        if ($this->read_at) {
            return false;
        }

        return $this->update(['read_at' => now()]);
    }

    /**
     * Mark the notification as unread.
     */
    public function markAsUnread(): bool
    {
        if (!$this->read_at) {
            return false;
        }

        return $this->update(['read_at' => null]);
    }

    /**
     * Check if the notification is read.
     */
    public function isRead(): bool
    {
        return !is_null($this->read_at);
    }

    /**
     * Check if the notification is unread.
     */
    public function isUnread(): bool
    {
        return is_null($this->read_at);
    }

    /**
     * Check if the notification is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if the notification is urgent.
     */
    public function isUrgent(): bool
    {
        return $this->priority === 'urgent';
    }

    /**
     * Check if the notification is high priority.
     */
    public function isHighPriority(): bool
    {
        return in_array($this->priority, ['high', 'urgent']);
    }

    /**
     * Get the notification icon based on type.
     */
    public function getIconAttribute(): string
    {
        $icons = [
            'urgent_medical_case' => 'fas fa-exclamation-triangle',
            'medical_evaluation' => 'fas fa-user-md',
            'follow_up_reminder' => 'fas fa-clock',
            'system_error' => 'fas fa-bug',
            'system_maintenance' => 'fas fa-tools',
            'new_message' => 'fas fa-envelope',
            'case_assigned' => 'fas fa-clipboard-list',
            'case_completed' => 'fas fa-check-circle',
            'backup_completed' => 'fas fa-save',
            'backup_failed' => 'fas fa-exclamation-circle',
            'user_login' => 'fas fa-sign-in-alt',
            'password_changed' => 'fas fa-key',
            'profile_updated' => 'fas fa-user-edit'
        ];

        return $icons[$this->type] ?? 'fas fa-bell';
    }

    /**
     * Get the notification color based on priority.
     */
    public function getColorAttribute(): string
    {
        $colors = [
            'urgent' => 'danger',
            'high' => 'warning',
            'medium' => 'info',
            'low' => 'secondary'
        ];

        return $colors[$this->priority] ?? 'primary';
    }

    /**
     * Get the formatted time ago.
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Get the notification data for a specific key.
     */
    public function getData(string $key, $default = null)
    {
        return data_get($this->data, $key, $default);
    }

    /**
     * Set notification data for a specific key.
     */
    public function setData(string $key, $value): bool
    {
        $data = $this->data ?? [];
        data_set($data, $key, $value);
        
        return $this->update(['data' => $data]);
    }

    /**
     * Get the route for the notification action.
     */
    public function getActionRoute(): ?string
    {
        if ($this->action_url) {
            return $this->action_url;
        }

        // Generate route based on notification type
        switch ($this->type) {
            case 'urgent_medical_case':
            case 'medical_evaluation':
                $solicitudId = $this->getData('solicitud_id');
                return $solicitudId ? route('medico.evaluar-solicitud', $solicitudId) : null;
                
            case 'case_assigned':
                $solicitudId = $this->getData('solicitud_id');
                return $solicitudId ? route('medico.evaluar-solicitud', $solicitudId) : null;
                
            case 'follow_up_reminder':
                $solicitudId = $this->getData('solicitud_id');
                return $solicitudId ? route('medico.evaluar-solicitud', $solicitudId) : null;
                
            case 'system_error':
                return route('admin.system-status');
                
            default:
                return route('dashboard');
        }
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Set default values
        static::creating(function ($notification) {
            if (!$notification->priority) {
                $notification->priority = 'medium';
            }
            
            if (!$notification->channel) {
                $notification->channel = 'web';
            }
        });
    }
}
