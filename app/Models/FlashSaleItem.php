<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlashSaleItem extends Model
{
    protected $fillable = ['flash_sale_id', 'product_id', 'sale_price', 'quantity_limit', 'sort_order'];

    /** Số đã bán trong chương trình (tính trong FlashSale::context(), không lưu DB). */
    public int $sold = 0;

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function flashSale(): BelongsTo
    {
        return $this->belongsTo(FlashSale::class);
    }

    public function discountPercent(): int
    {
        $base = (int) $this->product?->price;

        return $base > 0 ? (int) round(100 - $this->sale_price * 100 / $base) : 0;
    }

    /** % thanh tiến trình = đã bán / số suất. Không đặt số suất thì 0 (chỉ hiện chữ "Đã bán N", không bịa tỉ lệ). */
    public function soldPercent(): int
    {
        return $this->quantity_limit ? (int) min(100, round($this->sold * 100 / $this->quantity_limit)) : 0;
    }

    public function soldLabel(): string
    {
        if ($this->quantity_limit && $this->sold >= $this->quantity_limit) {
            return 'Đã bán hết suất';
        }
        if ($this->quantity_limit && $this->soldPercent() >= 80) {
            return 'Sắp hết – còn '.($this->quantity_limit - $this->sold);
        }

        return $this->sold > 0 ? 'Đã bán '.$this->sold : 'Vừa mở bán';
    }
}
