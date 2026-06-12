<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Modelo de Usuario del sistema de autenticación multi-factor.
 *
 * Soporta 3 roles: guest (invitado, 1FA), user (operativo, 2FA),
 * admin (administrador, 3FA). El admin primario tiene privilegios
 * adicionales para aprobar/rechazar solicitudes de identidad.
 *
 * Las contraseñas se almacenan hasheadas con bcrypt (Hash::make).
 * El PIN administrativo también se almacena como hash seguro.
 *
 * @property int         $id                     ID único del usuario.
 * @property string      $name                   Nombre completo.
 * @property string      $email                  Correo electrónico (único).
 * @property string      $password               Hash de la contraseña.
 * @property string      $role                   Rol: 'guest', 'user' o 'admin'.
 * @property bool        $is_primary_admin       Si es el administrador primario.
 * @property string|null $admin_pin_hash         Hash del PIN administrativo.
 * @property \Carbon\Carbon|null $admin_verified_once_at Fecha de primera verificación.
 * @property \Carbon\Carbon|null $email_verified_at      Fecha de verificación de email.
 * @property \Carbon\Carbon      $created_at             Fecha de creación.
 * @property \Carbon\Carbon      $updated_at             Fecha de actualización.
 *
 * @package App\Models
 * @standard PHPDoc (PSR-5)
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Atributos que se pueden asignar masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_primary_admin',
        'admin_pin_hash',
        'admin_verified_once_at',
    ];

    /**
     * Atributos ocultos en serialización JSON/array.
     *
     * Se ocultan datos sensibles como password y PIN hash
     * para evitar exposición accidental en respuestas API.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'admin_pin_hash',
    ];

    /**
     * Casts de atributos a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at'      => 'datetime',
        'is_primary_admin'       => 'boolean',
        'admin_verified_once_at' => 'datetime',
    ];

    /**
     * Obtiene los desafíos MFA asociados al usuario.
     *
     * @return HasMany Relación uno a muchos con MfaChallenge.
     */
    public function mfaChallenges(): HasMany
    {
        return $this->hasMany(MfaChallenge::class);
    }

    /**
     * Obtiene las solicitudes de acceso admin del usuario.
     *
     * @return HasMany Relación uno a muchos con AdminAccessRequest.
     */
    public function adminAccessRequests(): HasMany
    {
        return $this->hasMany(AdminAccessRequest::class);
    }

    /**
     * Determina si el usuario tiene rol de invitado (guest).
     *
     * Los invitados solo requieren 1FA (credenciales + reCAPTCHA)
     * y tienen acceso de solo lectura.
     *
     * @return bool True si el rol es 'guest'.
     */
    public function isGuest(): bool
    {
        return $this->role === 'guest';
    }

    /**
     * Determina si el usuario tiene rol de usuario operativo (user).
     *
     * Los usuarios requieren 2FA (credenciales + OTP por email)
     * y tienen permisos de lectura y escritura en inventarios.
     *
     * @return bool True si el rol es 'user'.
     */
    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    /**
     * Determina si el usuario tiene rol de administrador (admin).
     *
     * Los administradores requieren 3FA completo (credenciales
     * + OTP + identidad presencial/PIN) y tienen control total.
     *
     * @return bool True si el rol es 'admin'.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Determina si el usuario es el administrador primario.
     *
     * El admin primario tiene privilegios adicionales: aprobar
     * solicitudes de identidad de otros admins y asignar PINs.
     *
     * @return bool True si es admin Y tiene is_primary_admin=true.
     */
    public function isPrimaryAdmin(): bool
    {
        return $this->role === 'admin' && $this->is_primary_admin;
    }

    /**
     * Determina si el admin requiere verificación de identidad presencial.
     *
     * Los admins nuevos (sin admin_verified_once_at) deben pasar
     * por el proceso de verificación presencial con el admin primario
     * antes de poder acceder al sistema.
     *
     * @return bool True si es admin y nunca ha sido verificado presencialmente.
     */
    public function requiresAdminIdentityVerification(): bool
    {
        return $this->isAdmin() && is_null($this->admin_verified_once_at);
    }
}