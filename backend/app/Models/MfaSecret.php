<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class MfaSecret extends Model
{
    protected $fillable = [
        'user_id', 'secret', 'pending_secret',
        'setup_started_at', 'enabled', 'recovery_codes',
        'recovery_codes_hashed', 'enabled_at',
    ];

    protected $casts = [
        'enabled'               => 'boolean',
        'recovery_codes'        => 'array',
        'recovery_codes_hashed' => 'array',
        'setup_started_at'      => 'datetime',
        'enabled_at'            => 'datetime',
    ];

    // Never expose secret or recovery codes in API responses
    protected $hidden = [
        'secret',
        'pending_secret',
        'recovery_codes',
        'recovery_codes_hashed',
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function hasPendingSecret(): bool
    {
        return !empty($this->pending_secret) &&
               $this->setup_started_at?->gt(now()->subHour());
    }

    // Store hashed recovery codes, return plain codes once
    public static function hashRecoveryCodes(array $codes): array
    {
        return array_map(fn($code) => Hash::make($code), $codes);
    }

    public function verifyRecoveryCode(string $code): bool
    {
        if (!$this->recovery_codes_hashed) return false;
        foreach ($this->recovery_codes_hashed as $i => $hash) {
            if (Hash::check($code, $hash)) {
                // Remove used code from the list
                $hashed = $this->recovery_codes_hashed;
                unset($hashed[$i]);
                $this->update(['recovery_codes_hashed' => array_values($hashed)]);
                return true;
            }
        }
        return false;
    }
}
