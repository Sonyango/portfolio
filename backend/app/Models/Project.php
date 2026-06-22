<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'slug', 'description', 'content',
        'tech_stack', 'live_url', 'github_url',
        'thumbnail', 'category', 'featured', 'order', 'published',
    ];

    protected $casts = [
        'tech_stack' => 'array',
        'featured' => 'boolean',
        'pblished' => 'boolean',
    ];

    // Relationships
    public function images()
    {
        return $this->hasMany(ProjectImage::class)->orderBy('order');
    }

    public function media()
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    public function scopePublished($query)
    {
        return $query->where('published', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}
