<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo de solicitud de acceso administrativo (3FA).
 *
 * Representa una solicitud de verificación de identidad presencial
 * que un admin nuevo debe completar con el admin primario. Contiene
 * el hash del código de identidad, el estado de la solicitud,
 * y la auditoría de la decisión (aprobada/rechazada).
 *
 * @property int         $id                ID único de la solicitud.
 * @property int         $user_id           ID del admin solicitante.
 * @property int|null    $mfa_challenge_id  ID del desafío MFA asociado.
 * @property string      $request_code_hash Hash del código de identidad.
 * @property string      $status            Estado: 'pending', 'approved', 'rejected'.
 * @property int|null    $reviewed_by       ID del admin que revisó.
 * @property string|null $decision_notes    Notas de auditoría de la decisión.
 * @property \Carbon\Carbon      $expires_at    Fecha/hora de expiración.
 * @property \Carbon\Carbon|null $reviewed_at   Cuándo fue revisada.
 * @property \Carbon\Carbon      $created_at    Fecha de creación.
 * @property \Carbon\Carbon      $updated_at    Fecha de actualización.
 *
 * @package App\Models
 * @standard PHPDoc (PSR-5)
 */
class AdminAccessRequest extends Model
{
    /**
     * Atributos que se pueden asignar masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'mfa_challenge_id',
        'request_code_hash',
        'status',
        'reviewed_by',
        'decision_notes',
        'expires_at',
        'reviewed_at',
    ];

    /**
     * Casts de atributos a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'expires_at'  => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Obtiene el usuario (admin) que creó esta solicitud.
     *
     * @return BelongsTo Relación inversa con User (solicitante).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obtiene el desafío MFA asociado a esta solicitud.
     *
     * @return BelongsTo Relación inversa con MfaChallenge.
     */
    public function mfaChallenge(): BelongsTo
    {
        return $this->belongsTo(MfaChallenge::class);
    }

    /**
     * Obtiene el admin primario que revisó esta solicitud.
     *
     * @return BelongsTo Relación inversa con User (revisor).
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}