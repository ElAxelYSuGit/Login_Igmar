<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo de desafío MFA (Multi-Factor Authentication).
 *
 * Almacena los códigos OTP generados durante el segundo factor
 * de autenticación. Los códigos se guardan como hashes seguros
 * (bcrypt) y tienen tiempo de expiración y límite de intentos.
 *
 * @property int         $id           ID único del desafío.
 * @property int         $user_id      ID del usuario asociado.
 * @property string      $type         Tipo de desafío ('email_otp').
 * @property string      $code_hash    Hash bcrypt del código OTP.
 * @property int         $attempts     Número de intentos realizados.
 * @property \Carbon\Carbon      $expires_at   Fecha/hora de expiración.
 * @property \Carbon\Carbon|null $verified_at  Cuándo fue verificado.
 * @property \Carbon\Carbon|null $consumed_at  Cuándo fue consumido.
 * @property \Carbon\Carbon      $created_at   Fecha de creación.
 * @property \Carbon\Carbon      $updated_at   Fecha de actualización.
 *
 * @package App\Models
 * @standard PHPDoc (PSR-5)
 */
class MfaChallenge extends Model
{
    /**
     * Atributos que se pueden asignar masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'type',
        'code_hash',
        'attempts',
        'expires_at',
        'verified_at',
        'consumed_at',
    ];

    /**
     * Casts de atributos a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'expires_at'  => 'datetime',
        'verified_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];

    /**
     * Obtiene el usuario propietario de este desafío MFA.
     *
     * @return BelongsTo Relación inversa con el modelo User.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}