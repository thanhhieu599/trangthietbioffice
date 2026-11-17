<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tình trạng hàng hiển thị trên trang sản phẩm và khai báo cho Google (schema Product).
        Schema::table('products', function (Blueprint $table) {
            $table->string('stock_status', 20)->default('in_stock')->after('min_order_qty');
        });
    }

    public function down(): void
    {
        Schema::table('products', fn (Blueprint $t) => $t->dropColumn('stock_status'));
    }
};
