<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = [
        'title', 'slug', 'excerpt', 'content', 'image', 'author',
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

    public function authorName(): string
    {
        return $this->author ?: 'Admin';
    }

    /** Đoạn tóm tắt: dùng excerpt, nếu trống thì cắt từ nội dung. */
    public function summary(int $limit = 160): string
    {
        return \Illuminate\Support\Str::limit(
            trim(preg_replace('/\s+/', ' ', strip_tags($this->excerpt ?: $this->content))),
            $limit
        );
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
