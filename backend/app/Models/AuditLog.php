<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'event', 'email', 'ip_address',
        'user_agent', 'country', 'metadata', 'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public static function record(
        string $event,
        array $data = []
    ): void {
        static::create([
            'event' => $event,
            'created_at' => now(),
            ...$data,
        ]);
    }
}
