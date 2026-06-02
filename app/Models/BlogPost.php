<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BlogPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'wp_post_id',
        'title',
        'slug',
        'excerpt',
        'content_html',
        'status',
        'published_at',
        'modified_at',
        'featured_image_url',
        'featured_image_alt',
        'seo_title',
        'seo_description',
        'seo_focus_keyword',
        'canonical_url',
        'is_indexable',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'modified_at' => 'datetime',
            'is_indexable' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', 'publish')
            ->whereNotNull('published_at');
    }

    public function terms(): BelongsToMany
    {
        return $this->belongsToMany(BlogTerm::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->terms()->where('taxonomy', 'category');
    }

    public function tags(): BelongsToMany
    {
        return $this->terms()->where('taxonomy', 'post_tag');
    }
}
