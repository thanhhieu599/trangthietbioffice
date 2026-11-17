<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tự lặp lại: hết giờ thì tự dời sang chu kỳ mới cùng độ dài (VD: Flash Sale 24h mỗi ngày).
        Schema::table('flash_sales', function (Blueprint $table) {
            $table->boolean('auto_renew')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('flash_sales', fn (Blueprint $t) => $t->dropColumn('auto_renew'));
    }
};
