<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{

    use HasApiTokens, HasFactory, Notifiable;


    protected $fillable = [
        'name', 'email', 'password', 'role',
        'locked_until', 'failed_attempts',
        'last_login_at', 'last_login_ip',
        'password_changed_at', 'mfa_enabled',
        'last_activity_at',
    ];


    protected $hidden = [
        'password',
        'remember_token',
    ];


    // protected function casts(): array
    // {
    //     return [
    //         'email_verified_at' => 'datetime',
    //         'password' => 'hashed',
    //     ];
    // }

    protected $casts = [
        'email_verified_at' => 'datetime',
        'locked_until' => 'datetime',
        'last_login_at' => 'datetime',
        'password_changed_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'mfa_enabled' => 'boolean',
        'password' => 'hashed',
    ];

    public function mfaSecret()
    {
        return $this->hasOne(MfaSecret::class);
    }

    public function isLocked(): bool
    {
        return $this->locked_until &&
                $this->locked_until->isFuture();
    }

    public function lockoutSecondsRemaining(): int
    {
        if (!$this->isLocked()) return 0;
        return (int) now()->diffInSeconds($this->locked_until);
    }

    public function incrementFailedAttempts(): void
    {
        $attempts = $this->failed_attempts + 1;

        // Progressive lockout
        $lockedUntil = match(true) {
            $attempts >= 10 => now()->addHours(24),
            $attempts >= 7 => now()->addMinutes(30),
            $attempts >= 5 => now()->addMinutes(15),
            //$attempts >= 3 => now()->addMinutes(5),
            default        => null,
        };

        $this->update([
            'failed_attempts' => $attempts,
            'locked_until'  => $lockedUntil,
        ]);
    }

    public function clearFailedAttempts(): void
    {
        $this->update([
            'failed_attempts' => 0,
            'locked_until' => null,
        ]);
    }
}
