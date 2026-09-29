<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MfaPending extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'method',
        'email_code_hash',
        'attempts',
        'expires_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'attempts'   => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function hasExceededAttempts(int $max = 5): bool
    {
        return $this->attempts >= $max;
    }

    public function incrementAttempts(): void
    {
        $this->increment('attempts');
    }
}
