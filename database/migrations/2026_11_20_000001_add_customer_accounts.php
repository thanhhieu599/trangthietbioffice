<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tài khoản khách hàng: thông tin liên hệ + địa chỉ giao hàng mặc định.
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 30)->nullable()->after('email');
            $table->string('company')->nullable()->after('phone');
            $table->string('tax_code', 20)->nullable()->after('company');
            $table->string('address')->nullable()->after('tax_code');   // số nhà, tên đường
            $table->string('ward')->nullable()->after('address');       // phường / xã
            $table->string('province')->nullable()->after('ward');      // tỉnh / thành phố
        });

        // Đơn đặt hàng / yêu cầu báo giá: gắn tài khoản (nếu có) và địa chỉ giao hàng đầy đủ.
        Schema::table('quote_requests', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('address')->nullable()->after('delivery_area');
            $table->string('ward')->nullable()->after('address');
            $table->string('province')->nullable()->after('ward');
            // Khách cá nhân không có công ty.
            $table->string('company')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('quote_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn(['address', 'ward', 'province']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'company', 'tax_code', 'address', 'ward', 'province']);
        });
    }
};
