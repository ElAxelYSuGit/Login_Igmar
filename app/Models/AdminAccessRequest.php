<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminAccessRequest extends Model
{
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

    protected $casts = [
        'expires_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function mfaChallenge()
    {
        return $this->belongsTo(MfaChallenge::class);
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}