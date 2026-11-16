<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Banner extends Model
{
    protected $fillable = ['title', 'image', 'image_mobile', 'link', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function imageUrl(?string $path = null): string
    {
        $path ??= $this->image;

        return Str::startsWith($path, ['http://', 'https://', '/']) ? $path : asset('storage/'.$path);
    }
}
