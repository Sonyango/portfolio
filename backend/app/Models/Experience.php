<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Experience extends Model
{
    use HasFactory;

    protected $fillable = [
        'company', 'role', 'description', 'start_date', 'end_date',
        'current', 'location', 'order',
    ];

    protected $casts = [
        'current' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function scopeOrdered($query)
    {
        return $query->orderBy('start_date', 'desc');
    }
}
