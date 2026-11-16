<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $fillable = ['title', 'slug', 'content', 'meta_description', 'in_footer', 'sort_order'];

    protected $casts = ['in_footer' => 'boolean'];

    public function url(): string
    {
        return route('pages.show', $this);
    }

    public function hasContent(): bool
    {
        return trim(strip_tags((string) $this->content)) !== '';
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
