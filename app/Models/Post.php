<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = [
        'title', 'slug', 'excerpt', 'content', 'image',
        'meta_title', 'meta_description', 'faqs', 'published_at',
    ];

    protected $casts = [
        'faqs' => 'array',
        'published_at' => 'datetime',
    ];

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function url(): string
    {
        return route('posts.show', $this);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
