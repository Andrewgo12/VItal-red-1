<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'department',
        'phone',
        'medical_license',
        'specialties',
        'is_active',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'specialties' => 'array',
        ];
    }

    /**
     * Check if user is administrator
     */
    public function isAdministrator(): bool
    {
        return $this->role === 'administrador';
    }

    /**
     * Check if user is medico
     */
    public function isMedico(): bool
    {
        return $this->role === 'medico';
    }

    /**
     * Check if user is active
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Relationships
     */
    public function solicitudesEvaluadas()
    {
        return $this->hasMany(SolicitudMedica::class, 'medico_evaluador_id');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    public function notificacionesEnviadas()
    {
        return $this->hasMany(NotificacionInterna::class, 'enviado_por_user_id');
    }

    public function notificacionesRecibidas()
    {
        return $this->hasMany(NotificacionInterna::class, 'destinatario_user_id');
    }

    /**
     * Role checking methods
     */
    public function hasRole($role)
    {
        return $this->role === $role;
    }

    public function isAdmin()
    {
        return $this->role === 'administrador';
    }

    /**
     * Scope methods
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeMedicos($query)
    {
        return $query->where('role', 'medico');
    }

    public function scopeAdmins($query)
    {
        return $query->where('role', 'administrador');
    }

    public function scopeByDepartment($query, $department)
    {
        return $query->where('department', $department);
    }

    /**
     * Utility methods
     */
    public function getFullNameAttribute()
    {
        return $this->name;
    }

    public function hasSpecialty($specialty)
    {
        return in_array($specialty, $this->specialties ?? []);
    }

    public function getSpecialtiesListAttribute()
    {
        return implode(', ', $this->specialties ?? []);
    }

    public function isOnline()
    {
        return $this->last_login_at && $this->last_login_at->diffInMinutes(now()) < 30;
    }

    public function getStatusAttribute()
    {
        if (!$this->is_active) return 'inactive';
        if ($this->isOnline()) return 'online';
        return 'offline';
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'online' => 'success',
            'offline' => 'warning',
            'inactive' => 'danger',
            default => 'secondary'
        };
    }
}
