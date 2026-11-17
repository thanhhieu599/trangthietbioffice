<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Từ khóa chính dùng cho bảng chấm điểm SEO trong trang quản trị (không hiển thị ra ngoài).
        foreach (['posts', 'products', 'categories'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->string('focus_keyword')->nullable()->after('meta_description');
            });
        }

        // Chuyển hướng 301: tự tạo khi đổi đường dẫn (slug) hoặc xóa bài/sản phẩm/danh mục.
        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('from_path')->unique();   // VD: /tin-tuc/ten-cu
            $table->string('to_path');               // VD: /tin-tuc/ten-moi
            $table->unsignedInteger('hits')->default(0);
            $table->timestamp('last_hit_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redirects');
        foreach (['posts', 'products', 'categories'] as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->dropColumn('focus_keyword'));
        }
    }
};
