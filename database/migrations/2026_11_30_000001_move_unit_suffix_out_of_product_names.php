<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Bỏ đuôi đơn vị tính khỏi tên sản phẩm ("… – ram 500 tờ") và chuyển sang cột quy cách (pack_size),
 * vì trang sản phẩm đã có dòng "ĐVT" riêng. Slug (đường dẫn) giữ nguyên để không mất SEO.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('products')->whereNotNull('unit')->orderBy('id')->each(function ($p) {
            [$name, $pack] = Product::splitUnitSuffix($p->name, $p->unit);
            if ($pack !== null) {
                DB::table('products')->where('id', $p->id)->update([
                    'name' => $name,
                    'pack_size' => $p->pack_size ?: $pack,
                ]);
            }
        });
    }

    public function down(): void
    {
        // Không khôi phục tự động (tên có thể đã được sửa tay sau đó).
    }
};
