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
        'min_order_qty', 'stock_status', 'meta_title', 'meta_description', 'focus_keyword', 'is_featured', 'is_active',
    ];

    /** Tình trạng hàng: nhãn hiển thị + giá trị schema.org (null = không khai báo cho Google). */
    public const STOCK = [
        'in_stock' => ['label' => 'Còn hàng', 'schema' => 'InStock'],
        'out_of_stock' => ['label' => 'Hết hàng', 'schema' => 'OutOfStock'],
        'preorder' => ['label' => 'Đặt trước', 'schema' => 'PreOrder'],
        'contact' => ['label' => 'Liên hệ để biết tình trạng', 'schema' => null],
    ];

    /** Mục flash sale đang chạy của sản phẩm (null = không trong flash sale). */
    public function flashItem(): ?FlashSaleItem
    {
        return FlashSale::itemFor($this->id);
    }

    /** Giá bán thực tế lúc này: giá flash sale nếu đang chạy, không thì giá thường. */
    public function currentPrice(): ?int
    {
        return $this->flashItem()?->sale_price ?? ($this->price ? (int) $this->price : null);
    }

    public function stockLabel(): string
    {
        return self::STOCK[$this->stock_status]['label'] ?? self::STOCK['in_stock']['label'];
    }

    public function stockSchema(): ?string
    {
        return array_key_exists($this->stock_status, self::STOCK) ? self::STOCK[$this->stock_status]['schema'] : 'InStock';
    }

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
