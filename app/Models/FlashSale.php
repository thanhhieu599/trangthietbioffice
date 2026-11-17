<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FlashSale extends Model
{
    protected $fillable = ['name', 'starts_at', 'ends_at', 'banner', 'banner_mobile', 'is_active', 'auto_renew'];

    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'is_active' => 'boolean', 'auto_renew' => 'boolean'];

    /** Độ dài một chu kỳ (giây) – dùng cho đồng hồ đếm ngược tự quay lại khi chương trình tự lặp. */
    public function cycleSeconds(): int
    {
        return $this->auto_renew ? max(60, $this->starts_at->diffInSeconds($this->ends_at)) : 0;
    }

    /**
     * Chương trình "tự lặp lại" đã hết giờ -> dời sang chu kỳ hiện tại (giữ nguyên độ dài).
     * Chạy khi có người xem trang, không cần cron. Số "Đã bán" tính theo chu kỳ nên tự đếm lại từ đầu.
     */
    public static function renewExpired(): void
    {
        foreach (static::where('is_active', true)->where('auto_renew', true)->where('ends_at', '<=', now())->get() as $sale) {
            $len = max(60, $sale->starts_at->diffInSeconds($sale->ends_at));
            $cycles = (int) floor($sale->ends_at->diffInSeconds(now()) / $len) + 1;
            $sale->starts_at = $sale->starts_at->copy()->addSeconds($cycles * $len);
            $sale->ends_at = $sale->ends_at->copy()->addSeconds($cycles * $len);
            $sale->save();
        }
    }

    /** Ghi nhớ theo từng request (mỗi request là một đối tượng Request mới). */
    private static array $memo = [];

    public function items(): HasMany
    {
        return $this->hasMany(FlashSaleItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function scopeRunning(Builder $q): Builder
    {
        return $q->where('is_active', true)->where('starts_at', '<=', now())->where('ends_at', '>', now());
    }

    public function status(): string
    {
        return match (true) {
            ! $this->is_active => 'off',
            $this->starts_at->isFuture() => 'upcoming',
            $this->ends_at->isPast() => 'ended',
            default => 'running',
        };
    }

    public function statusLabel(): string
    {
        return ['off' => 'Đang tắt', 'upcoming' => 'Sắp diễn ra', 'ended' => 'Đã kết thúc', 'running' => 'Đang diễn ra'][$this->status()];
    }

    public function bannerUrl(?string $path = null): ?string
    {
        $path ??= $this->banner;

        return $path ? (str_starts_with($path, '/') || str_starts_with($path, 'http') ? $path : asset('storage/'.$path)) : null;
    }

    /**
     * Chương trình đang chạy + các sản phẩm (đang bán, giá flash < giá gốc), kèm số đã bán thật trong thời gian chương trình.
     *
     * @return array{sale: ?FlashSale, items: Collection<int, FlashSaleItem>}
     */
    public static function context(): array
    {
        $key = spl_object_id(request());
        if (isset(self::$memo[$key])) {
            return self::$memo[$key];
        }
        self::$memo = []; // chỉ giữ request hiện tại

        static::renewExpired();
        $sale = static::running()->orderByDesc('starts_at')->first();
        $items = collect();

        if ($sale) {
            $items = $sale->items()->with('product.category')->get()
                ->filter(fn (FlashSaleItem $i) => $i->product && $i->product->is_active && $i->product->price && $i->sale_price < $i->product->price)
                ->values();

            // Số lượng đã đặt trong thời gian chương trình (từ đơn thật).
            $sold = DB::table('quote_request_items')
                ->join('quote_requests', 'quote_requests.id', '=', 'quote_request_items.quote_request_id')
                ->whereIn('quote_request_items.product_id', $items->pluck('product_id'))
                ->where('quote_request_items.is_flash_sale', true)
                ->whereBetween('quote_requests.created_at', [$sale->starts_at, $sale->ends_at])
                ->groupBy('quote_request_items.product_id')
                ->selectRaw('quote_request_items.product_id, sum(quote_request_items.quantity) as qty')
                ->pluck('qty', 'product_id');

            $items->each(function (FlashSaleItem $i) use ($sale, $sold) {
                $i->sold = (int) ($sold[$i->product_id] ?? 0);
                $i->setRelation('flashSale', $sale);
            });
            $items = $items->keyBy('product_id');
        }

        return self::$memo[$key] = ['sale' => $sale, 'items' => $items];
    }

    /** Mục flash sale đang chạy của một sản phẩm (null nếu không có). */
    public static function itemFor(?int $productId): ?FlashSaleItem
    {
        return $productId ? (self::context()['items'][$productId] ?? null) : null;
    }
}
