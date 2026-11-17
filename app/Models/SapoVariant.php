<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Ghép SKU trên web với phiên bản sản phẩm (variant) trên Sapo. Cập nhật bằng lệnh sapo:sync-variants. */
class SapoVariant extends Model
{
    protected $fillable = ['sku', 'variant_id', 'product_id', 'title', 'price'];
}
