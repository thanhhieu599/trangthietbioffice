<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $fillable = [
        'category_id', 'sku', 'name', 'slug', 'brand', 'unit', 'pack_size',
        'short_description', 'description', 'specs', 'images', 'price',
        'min_order_qty', 'meta_title', 'meta_description', 'is_featured', 'is_active',
    ];

    protected $casts = [
        'specs' => 'array',
        'images' => 'array',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function url(): string
    {
        return route('products.show', $this);
    }

    public function mainImage(): ?string
    {
        $first = $this->images[0] ?? null;
        if (! $first) {
            return null;
        }

        return Str::startsWith($first, ['http://', 'https://', '/']) ? $first : asset('storage/'.$first);
    }

    public function formattedPrice(): string
    {
        return $this->price ? number_format($this->price, 0, ',', '.').' đ' : 'Liên hệ báo giá';
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
