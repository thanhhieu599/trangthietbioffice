<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteRequestItem extends Model
{
    protected $fillable = ['quote_request_id', 'product_id', 'sku', 'name', 'quantity', 'unit_price', 'is_flash_sale'];

    protected $casts = ['is_flash_sale' => 'boolean'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
