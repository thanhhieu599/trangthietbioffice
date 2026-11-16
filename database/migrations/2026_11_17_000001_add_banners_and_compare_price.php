<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Giá niêm yết gốc; nếu lớn hơn price thì hiện giá gạch ngang + % giảm.
            $table->unsignedBigInteger('compare_price')->nullable()->after('price');
        });

        Schema::table('categories', function (Blueprint $table) {
            // Hiện thành một khối sản phẩm riêng trên trang chủ.
            $table->boolean('show_on_home')->default(false)->after('is_active');
        });

        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();     // dùng làm alt ảnh
            $table->string('image');                 // đường dẫn ảnh (khuyến nghị 1170 x 500 px)
            $table->string('image_mobile')->nullable(); // ảnh riêng cho điện thoại (tùy chọn, 800 x 450 px)
            $table->string('link')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
        Schema::table('categories', fn (Blueprint $t) => $t->dropColumn('show_on_home'));
        Schema::table('products', fn (Blueprint $t) => $t->dropColumn('compare_price'));
    }
};
