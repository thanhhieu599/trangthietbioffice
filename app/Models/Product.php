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
        'short_description', 'description', 'specs', 'attrs', 'variants', 'images', 'price', 'compare_price',
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
        'attrs' => 'array',
        'variants' => 'array',
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

    /**
     * Tìm theo từng từ khóa: mọi từ đều phải có trong tên, mã hoặc thương hiệu
     * ("bút xanh" khớp "Bút bi xanh TL-027"). Tên bắt đầu bằng cụm từ khách gõ được xếp lên đầu.
     * Ký tự đại diện của LIKE được thoát bằng "!" (chạy giống nhau trên MySQL và SQLite).
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $escape = fn (string $s) => str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $s);
        $term = mb_substr(trim(preg_replace('/\s+/u', ' ', $term)), 0, 100);
        $words = array_slice(array_unique(explode(' ', $term)), 0, 6);

        foreach ($words as $word) {
            $like = '%'.$escape($word).'%';
            $query->where(fn ($q) => $q->whereRaw("name like ? escape '!'", [$like])
                ->orWhereRaw("sku like ? escape '!'", [$like])
                ->orWhereRaw("brand like ? escape '!'", [$like]));
        }

        return $query->orderByRaw("case when name like ? escape '!' then 0 when sku like ? escape '!' then 1 else 2 end",
            [$escape($term).'%', $escape($term).'%'])->orderBy('name');
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

    /**
     * Tách đuôi quy cách khỏi tên: "Giấy in A4 80gsm – ram 500 tờ" (ĐVT ram)
     * -> ["Giấy in A4 80gsm", "500 tờ/ram"]. Chỉ tách khi đuôi bắt đầu bằng đúng ĐVT
     * (giữ nguyên "Bìa còng 7cm – Kingjim").
     *
     * @return array{0: string, 1: ?string} [tên, quy cách]
     */
    public static function splitUnitSuffix(string $name, ?string $unit): array
    {
        $unit = trim((string) $unit);
        if ($unit !== '' && preg_match('/^(.*\S)\s+[–—-]\s+'.preg_quote($unit, '/').'\s+(\S.*)$/iu', trim($name), $m)) {
            return [$m[1], trim($m[2]).'/'.$unit];
        }

        return [$name, null];
    }

    /* ===================== Thuộc tính & phiên bản ===================== */

    /** Thuộc tính tên có chữ "màu" (Màu sắc, Màu mực...) hiện dạng chấm màu. */
    public static function isColorAttr(?string $name): bool
    {
        return (bool) preg_match('/m[aà]u/iu', (string) $name);
    }

    /**
     * Thuộc tính đã chuẩn hóa: [['name' => 'Màu sắc', 'values' => ['Đen', 'Đỏ']], ...] (bỏ dòng trống, bỏ giá trị trùng).
     *
     * @return list<array{name: string, values: list<string>}>
     */
    public function attrList(): array
    {
        return collect($this->attrs ?? [])
            ->map(fn ($a) => [
                'name' => trim((string) ($a['name'] ?? '')),
                'values' => collect($a['values'] ?? [])->map(fn ($v) => trim((string) $v))->filter()->unique()->values()->all(),
            ])
            ->filter(fn ($a) => $a['name'] !== '' && $a['values'])
            ->values()->all();
    }

    /**
     * Phiên bản có SKU riêng (từ Sapo): [['sku', 'options' => [tên thuộc tính => giá trị], 'price', 'compare_price', 'stock_status'], ...]
     *
     * @return list<array>
     */
    public function variantList(): array
    {
        return collect($this->variants ?? [])->filter(fn ($v) => filled($v['sku'] ?? null))->values()->all();
    }

    /** Phiên bản theo SKU (không phân biệt hoa thường), null nếu không có. */
    public function variantBySku(?string $sku): ?array
    {
        $sku = mb_strtolower(trim((string) $sku));

        return collect($this->variantList())->first(fn ($v) => mb_strtolower(trim($v['sku'])) === $sku);
    }

    /** "Màu sắc: Hồng · Kích thước: A4" – mô tả lựa chọn của 1 phiên bản / lựa chọn của khách. */
    public static function optionLabel(array $options): string
    {
        return collect($options)->filter()->map(fn ($v, $k) => "$k: $v")->implode(' · ');
    }

    /** Mọi SKU của sản phẩm (SKU chính + SKU phiên bản). */
    public function allSkus(): array
    {
        return array_values(array_unique(array_merge([$this->sku], array_column($this->variantList(), 'sku'))));
    }

    /**
     * Tìm sản phẩm theo SKU chính HOẶC SKU phiên bản.
     *
     * @return array<string, array{0: Product, 1: ?array}> [sku viết thường => [sản phẩm, phiên bản|null]]
     */
    public static function resolveSkus(array $skus, bool $activeOnly = false): array
    {
        $skus = collect($skus)->map(fn ($s) => trim((string) $s))->filter()->unique()->values();
        if ($skus->isEmpty()) {
            return [];
        }
        $out = [];
        $base = fn () => $activeOnly ? self::active() : self::query();

        foreach ($base()->whereIn('sku', $skus)->get() as $p) {
            $out[mb_strtolower($p->sku)] = [$p, null];
        }
        $missing = $skus->reject(fn ($s) => isset($out[mb_strtolower($s)]));
        if ($missing->isNotEmpty()) {
            // SKU phiên bản nằm trong cột JSON "variants": lọc thô bằng LIKE rồi so khớp chính xác
            $candidates = $base()->whereNotNull('variants')
                ->where(fn ($q) => $missing->each(fn ($s) => $q->orWhere('variants', 'like', '%'.str_replace(['%', '_'], '', $s).'%')))
                ->get();
            foreach ($missing as $s) {
                foreach ($candidates as $p) {
                    if ($v = $p->variantBySku($s)) {
                        $out[mb_strtolower($s)] = [$p, $v];
                        break;
                    }
                }
            }
        }

        return $out;
    }

    /** ĐVT hiển thị: "Ram", kèm quy cách nếu có: "Ram (500 tờ/ram)". */
    public function unitLabel(): ?string
    {
        if (! $this->unit) {
            return null;
        }

        return Str::ucfirst($this->unit).($this->pack_size ? " ({$this->pack_size})" : '');
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
