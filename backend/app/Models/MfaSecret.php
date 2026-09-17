<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MfaSecret extends Model
{
    protected $fillable = [
        'user_id', 'secret', 'enabled',
        'recovery_codes', 'enabled_at',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'recovery_codes' => 'array',
        'enabled_at' => 'datetime',
    ];

    protected $hidden = ['secret', 'recovery_codes'];

    public function user(){
        return $this->belongsTo(User::class);
    }
}
