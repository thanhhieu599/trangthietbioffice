<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Thuộc tính sản phẩm (giống Sapo): 1 sản phẩm – nhiều thuộc tính, KHÔNG tách mỗi màu thành 1 sản phẩm.
 *   attrs:    [{"name": "Màu sắc", "values": ["Đen", "Đỏ"]}, {"name": "Kích thước", "values": ["A4"]}]
 *   variants:   [{"sku": "EU016-DEN", "options": {"Màu sắc": "Đen"}, "price": 9000}, ...]  (phiên bản có SKU riêng, từ Sapo)
 * Bỏ cách cũ (mỗi màu 1 sản phẩm: variant_group / color / color_hex).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->json('attrs')->nullable()->after('specs'); // "attributes" trùng tên thuộc tính nội bộ của Eloquent
            $table->json('variants')->nullable()->after('attrs');
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['variant_group']);
            $table->dropColumn(['variant_group', 'color', 'color_hex']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['attrs', 'variants']);
            $table->string('variant_group', 100)->nullable()->index();
            $table->string('color', 50)->nullable();
            $table->string('color_hex', 7)->nullable();
        });
    }
};
