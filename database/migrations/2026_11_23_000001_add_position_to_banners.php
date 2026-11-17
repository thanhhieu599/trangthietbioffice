<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Vị trí banner ở đầu trang chủ: slider (trình chiếu lớn), side (2 ảnh bên phải), promo (hàng 4 ảnh bên dưới).
        Schema::table('banners', function (Blueprint $table) {
            $table->string('position', 20)->default('slider')->after('id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('banners', fn (Blueprint $t) => $t->dropColumn('position'));
    }
};
