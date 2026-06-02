<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_primary_admin',
        'admin_pin_hash',
        'admin_verified_once_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'admin_pin_hash',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_primary_admin' => 'boolean',
        'admin_verified_once_at' => 'datetime',
    ];

    public function mfaChallenges()
    {
        return $this->hasMany(MfaChallenge::class);
    }

    public function adminAccessRequests()
    {
        return $this->hasMany(AdminAccessRequest::class);
    }

    public function isGuest(): bool
    {
        return $this->role === 'guest';
    }

    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isPrimaryAdmin(): bool
    {
        return $this->role === 'admin' && $this->is_primary_admin;
    }

    public function requiresAdminIdentityVerification(): bool
    {
        return $this->isAdmin() && is_null($this->admin_verified_once_at);
    }
}