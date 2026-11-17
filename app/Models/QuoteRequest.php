<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Đơn đặt hàng / yêu cầu báo giá (khách có tài khoản hoặc khách vãng lai). */
class QuoteRequest extends Model
{
    public const STATUSES = [
        'new' => 'Mới tiếp nhận',
        'quoted' => 'Đã báo giá',
        'won' => 'Đã chốt đơn',
        'lost' => 'Đã hủy',
    ];

    protected $fillable = [
        'user_id', 'company', 'contact_name', 'phone', 'email', 'tax_code',
        'delivery_area', 'address', 'ward', 'province', 'frequency', 'note', 'source', 'status',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(QuoteRequestItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function fullAddress(): string
    {
        return collect([$this->address, $this->ward, $this->province])->filter()->implode(', ') ?: (string) $this->delivery_area;
    }

    /** Mã đơn hiển thị cho khách, VD: DT000123. */
    public function code(): string
    {
        return 'DT'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }
}
