<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('author')->nullable()->after('image'); // "Đăng bởi ..."; trống = Admin
        });

        // Trang nội dung tĩnh: chính sách bảo mật, vận chuyển, đổi trả...
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content')->nullable(); // trống = "đang cập nhật" + noindex
            $table->string('meta_description', 300)->nullable();
            $table->boolean('in_footer')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
        Schema::table('posts', fn (Blueprint $t) => $t->dropColumn('author'));
    }
};
