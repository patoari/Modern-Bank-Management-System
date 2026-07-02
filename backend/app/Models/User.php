<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes;

    protected $fillable = [
        'uuid', 'email', 'phone', 'password_hash', 'user_type', 'status',
        'email_verified_at', 'phone_verified_at', 'last_login_at', 'last_login_ip',
        'failed_login_attempts', 'locked_until', 'password_changed_at',
        'force_password_change', 'two_factor_enabled', 'two_factor_type', 'two_factor_secret',
    ];

    protected $hidden = ['password_hash', 'two_factor_secret', 'remember_token'];

    protected $casts = [
        'email_verified_at'    => 'datetime',
        'phone_verified_at'    => 'datetime',
        'last_login_at'        => 'datetime',
        'locked_until'         => 'datetime',
        'password_changed_at'  => 'datetime',
        'force_password_change' => 'boolean',
        'two_factor_enabled'   => 'boolean',
    ];

    // Laravel Auth expects a "password" attribute for hashing
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    // Relationships
    public function customer()
    {
        return $this->hasOne(Customer::class);
    }

    public function staff()
    {
        return $this->hasOne(Staff::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }
}
