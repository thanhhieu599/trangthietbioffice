<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Banner dọc đại diện cho danh mục: cạnh lưới sản phẩm ở trang chủ và trang danh mục.
        Schema::table('categories', function (Blueprint $table) {
            $table->string('banner')->nullable()->after('image');       // khuyến nghị 600 × 1000 px
            $table->string('banner_link', 500)->nullable()->after('banner'); // trống = mở trang danh mục
        });
    }

    public function down(): void
    {
        Schema::table('categories', fn (Blueprint $t) => $t->dropColumn(['banner', 'banner_link']));
    }
};
