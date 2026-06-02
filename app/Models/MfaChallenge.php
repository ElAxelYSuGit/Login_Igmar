<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MfaChallenge extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'code_hash',
        'attempts',
        'expires_at',
        'verified_at',
        'consumed_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}