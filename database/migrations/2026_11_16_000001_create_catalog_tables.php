<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('intro')->nullable();          // đoạn giới thiệu đầu trang danh mục
            $table->longText('content')->nullable();    // nội dung SEO cuối trang danh mục
            $table->string('image')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 300)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sku')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('brand')->nullable();
            $table->string('unit')->nullable();          // đơn vị tính: ram, hộp, cây...
            $table->string('pack_size')->nullable();     // quy cách: 5 ram/thùng...
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->json('specs')->nullable();           // thông số dạng {"Định lượng":"80gsm"}
            $table->json('images')->nullable();          // danh sách đường dẫn ảnh
            $table->unsignedBigInteger('price')->nullable(); // VND, null = liên hệ báo giá
            $table->unsignedInteger('min_order_qty')->default(1);
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 300)->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'category_id']);
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content');
            $table->string('image')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 300)->nullable();
            $table->json('faqs')->nullable();            // [{"q":"...","a":"..."}] -> FAQPage schema
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('quote_requests', function (Blueprint $table) {
            $table->id();
            $table->string('company');
            $table->string('contact_name');
            $table->string('phone', 30);
            $table->string('email');
            $table->string('tax_code', 20)->nullable();
            $table->string('delivery_area')->nullable();  // KCN / tỉnh thành
            $table->string('frequency')->nullable();      // một lần / hàng tháng / hàng quý
            $table->text('note')->nullable();
            $table->string('source')->nullable();         // utm_source hoặc trang giới thiệu
            $table->string('status')->default('new');     // new / quoted / won / lost
            $table->timestamps();
        });

        Schema::create('quote_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sku')->nullable();
            $table->string('name');
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_request_items');
        Schema::dropIfExists('quote_requests');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};
