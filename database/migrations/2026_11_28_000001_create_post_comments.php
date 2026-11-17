<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bình luận bài viết: chỉ hiện sau khi quản trị duyệt (chống spam).
        Schema::create('post_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 100);
            $table->string('email');
            $table->text('content');
            $table->boolean('is_approved')->default(false)->index();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
            $table->index(['post_id', 'is_approved']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_comments');
    }
};
