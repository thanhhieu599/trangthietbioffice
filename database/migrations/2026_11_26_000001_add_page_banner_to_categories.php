<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Banner ngang đầu trang danh mục ("Xem tất cả"), chỉ là ảnh – khuyến nghị 1200 × 380 px.
        Schema::table('categories', function (Blueprint $table) {
            $table->string('page_banner')->nullable()->after('banner_link');
        });
    }

    public function down(): void
    {
        Schema::table('categories', fn (Blueprint $t) => $t->dropColumn('page_banner'));
    }
};
