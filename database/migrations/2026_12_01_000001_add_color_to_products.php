<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Màu sắc sản phẩm: mỗi màu là 1 sản phẩm (SKU riêng, giống phiên bản trên Sapo).
 * Các sản phẩm cùng "variant_group" hiện chung một hàng chấm màu ở trang sản phẩm.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('variant_group', 100)->nullable()->index()->after('sku'); // VD: sapo:96428005
            $table->string('color', 50)->nullable()->after('pack_size');            // VD: Xanh lá
            $table->string('color_hex', 7)->nullable()->after('color');              // VD: #2e9e4f (trống = tự đoán theo tên)
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['variant_group', 'color', 'color_hex']);
        });
    }
};
