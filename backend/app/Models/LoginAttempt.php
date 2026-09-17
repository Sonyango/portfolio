<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginAttempt extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'email', 'ip_address', 'user_agent',
        'successful', 'failure_reason', 'attempted_at',
    ];

    protected $casts = [
        'successful' => 'boolean',
        'attempted_at' => 'datetime',
    ];

    // Count recent failed attempts for an IP
    public static function recentFailedForIp(
        string $ip,
        int $minutes = 30
    ): int {
        return static::where('ip_address', $ip)
            ->where('successful', false)
            ->where('attempted_at', '>=', now()->subMinutes($minutes))
            ->count();
    }

    // Count recent failed attempts for an email
    public function recentFailedForEmail(
        string $email,
        int $minutes = 30
    ): int {
        return static::where('email', $email)
            ->where('successful', false)
            ->where('attempted_at', '>=', now()->subMinutes($minutes))
            ->count();
    }
}
