<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BlogTerm extends Model
{
    use HasFactory;

    protected $fillable = [
        'wp_term_id',
        'taxonomy',
        'name',
        'slug',
        'post_count',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(BlogPost::class);
    }
}
