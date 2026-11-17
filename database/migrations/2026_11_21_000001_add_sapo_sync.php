<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Mã khách hàng bên Sapo (tránh tạo trùng khách ở các lần đồng bộ sau).
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('sapo_customer_id')->nullable()->after('province');
            $table->timestamp('sapo_synced_at')->nullable()->after('sapo_customer_id');
        });

        // Trạng thái đẩy đơn / yêu cầu báo giá sang Sapo.
        Schema::table('quote_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('sapo_customer_id')->nullable()->after('status');
            $table->unsignedBigInteger('sapo_order_id')->nullable()->after('sapo_customer_id');
            $table->string('sapo_status', 20)->nullable()->index()->after('sapo_order_id'); // pending | synced | failed
            $table->text('sapo_error')->nullable()->after('sapo_status');
            $table->timestamp('sapo_synced_at')->nullable()->after('sapo_error');
        });

        // Bảng ghép SKU trên web với phiên bản (variant) trên Sapo, đồng bộ hằng ngày.
        Schema::create('sapo_variants', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->unsignedBigInteger('variant_id');
            $table->unsignedBigInteger('product_id');
            $table->string('title')->nullable();
            $table->decimal('price', 15, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sapo_variants');
        Schema::table('quote_requests', function (Blueprint $table) {
            $table->dropColumn(['sapo_customer_id', 'sapo_order_id', 'sapo_status', 'sapo_error', 'sapo_synced_at']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['sapo_customer_id', 'sapo_synced_at']);
        });
    }
};
