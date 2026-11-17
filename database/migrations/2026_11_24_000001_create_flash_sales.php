<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Chương trình Flash Sale: chỉ hiện trên web trong khoảng starts_at → ends_at khi đang bật.
        Schema::create('flash_sales', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamp('starts_at')->index();
            $table->timestamp('ends_at')->index();
            $table->string('banner')->nullable();          // ảnh đầu trang Flash Sale (1200 × 300 px)
            $table->string('banner_mobile')->nullable();   // ảnh cho điện thoại (800 × 400 px)
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Sản phẩm trong chương trình + giá flash sale + số suất (tùy chọn, để hiện thanh "Đã bán").
        Schema::create('flash_sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flash_sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('sale_price');
            $table->unsignedInteger('quantity_limit')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['flash_sale_id', 'product_id']);
        });

        // Giá tại thời điểm đặt (giá flash sale nếu đang chạy) – để đơn hàng / Sapo không bị lệch giá về sau.
        Schema::table('quote_request_items', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_price')->nullable()->after('quantity');
            $table->boolean('is_flash_sale')->default(false)->after('unit_price');
        });
    }

    public function down(): void
    {
        Schema::table('quote_request_items', fn (Blueprint $t) => $t->dropColumn(['unit_price', 'is_flash_sale']));
        Schema::dropIfExists('flash_sale_items');
        Schema::dropIfExists('flash_sales');
    }
};
