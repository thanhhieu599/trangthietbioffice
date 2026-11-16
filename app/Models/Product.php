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
        'short_description', 'description', 'specs', 'images', 'price', 'compare_price',
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
        return $this->price ? self::money($this->price) : 'Liên hệ';
    }

    /** Có giá gốc cao hơn giá bán -> hiện giá gạch ngang và % giảm. */
    public function hasDiscount(): bool
    {
        return $this->price && $this->compare_price && $this->compare_price > $this->price;
    }

    public function discountPercent(): int
    {
        return $this->hasDiscount() ? (int) round(100 - $this->price * 100 / $this->compare_price) : 0;
    }

    public static function money(int $amount): string
    {
        return number_format($amount, 0, ',', '.').'đ';
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
