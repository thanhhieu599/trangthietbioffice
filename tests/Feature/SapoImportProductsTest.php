<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SapoImportProductsTest extends TestCase
{
    use RefreshDatabase;

    public function test_imports_hidden_products_skips_existing_sku_and_takes_first_image(): void
    {
        config(['services.sapo.store' => 'demo', 'services.sapo.key' => 'k', 'services.sapo.secret' => 's']);
        Product::create(['sku' => 'DA-CO', 'name' => 'Đã có trên web', 'slug' => 'da-co', 'price' => 1000, 'is_active' => true]);

        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        Http::fake([
            'demo.mysapo.net/admin/custom_collections.json*' => Http::response(['custom_collections' => [['id' => 7, 'name' => 'Văn phòng phẩm', 'alias' => 'van-phong-pham']]]),
            'demo.mysapo.net/admin/products.json*' => Http::response(['products' => [
                ['id' => 1, 'name' => 'Bút bi A', 'alias' => 'but-bi-a', 'vendor' => 'Deli', 'content' => '<p>Mô tả</p>', 'summary' => null,
                    'meta_title' => null, 'meta_description' => null,
                    'images' => [['position' => 2, 'src' => 'https://cdn.test/hai.png'], ['position' => 1, 'src' => 'https://cdn.test/mot.png']],
                    'variants' => [['sku' => 'BB-A', 'title' => 'Default Title', 'price' => 5000, 'compare_at_price' => 6000, 'unit' => 'cây', 'inventory_management' => null]]],
                ['id' => 2, 'name' => 'Sản phẩm trùng', 'alias' => 'trung', 'vendor' => null, 'content' => '', 'summary' => null, 'meta_title' => null, 'meta_description' => null,
                    'images' => [], 'variants' => [['sku' => 'da-co', 'title' => 'Default Title', 'price' => 0]]],
                ['id' => 3, 'name' => 'Không SKU', 'alias' => 'khong-sku', 'vendor' => null, 'content' => '', 'summary' => null, 'meta_title' => null, 'meta_description' => null,
                    'images' => [], 'variants' => [['sku' => '', 'title' => 'Default Title', 'price' => 0]]],
                // Nhiều màu: web đang có bản cũ tách mỗi màu 1 sản phẩm -> phải gộp lại thành 1
                ['id' => 4, 'name' => 'Bút màu', 'alias' => 'but-mau', 'vendor' => 'Deli', 'content' => '', 'summary' => null, 'meta_title' => null, 'meta_description' => null,
                    'images' => [], 'options' => [['name' => 'Màu sắc', 'position' => 1, 'values' => ['Đỏ', 'Xanh']]],
                    'variants' => [['sku' => 'BM-DO', 'option1' => 'Đỏ', 'title' => 'Đỏ', 'price' => 7000], ['sku' => 'BM-XANH', 'option1' => 'Xanh', 'title' => 'Xanh', 'price' => 7000]]],
            ]]),
            'cdn.test/*' => Http::response($png, 200, ['Content-Type' => 'image/png']),
        ]);

        Product::create(['sku' => 'BM-DO', 'name' => 'Bút màu – Đỏ', 'slug' => 'but-mau-do', 'is_active' => false]);
        Product::create(['sku' => 'BM-XANH', 'name' => 'Bút màu – Xanh', 'slug' => 'but-mau-xanh', 'is_active' => false]);

        $this->artisan('sapo:import-products', ['collections' => ['Văn phòng phẩm']])
            ->expectsOutputToContain('Tạo mới: 1 · Đã có (cập nhật thuộc tính/thương hiệu): 1 · Gộp sản phẩm tách theo màu: 1 · Bỏ qua không có SKU: 1')
            ->assertSuccessful();

        // Gộp: còn 1 sản phẩm "Bút màu" có thuộc tính Màu sắc + 2 phiên bản
        $pen = Product::where('name', 'Bút màu')->sole();
        $this->assertSame([['name' => 'Màu sắc', 'values' => ['Đỏ', 'Xanh']]], $pen->attrList());
        $this->assertSame(['BM-DO', 'BM-XANH'], array_column($pen->variantList(), 'sku'));
        $this->assertSame(0, Product::where('sku', 'BM-XANH')->count());
        $this->assertSame($pen->id, Product::resolveSkus(['BM-XANH'])['bm-xanh'][0]->id);

        $p = Product::where('sku', 'BB-A')->firstOrFail();
        $this->assertFalse($p->is_active, 'Sản phẩm mới phải đang ẩn');
        $this->assertSame(5000, (int) $p->price);
        $this->assertSame(6000, (int) $p->compare_price);
        $this->assertCount(1, $p->images, 'Chỉ lấy 1 ảnh');
        $this->assertFileExists(public_path(ltrim($p->images[0], '/')));
        Http::assertSent(fn ($r) => $r->url() === 'https://cdn.test/mot.png'); // ảnh vị trí 1
        Http::assertNotSent(fn ($r) => $r->url() === 'https://cdn.test/hai.png');

        $this->assertSame('Đã có trên web', Product::where('sku', 'DA-CO')->value('name'), 'Không ghi đè SKU đã có');
        $this->assertFalse(Category::where('name', 'Văn phòng phẩm')->value('is_active'), 'Danh mục mới tạo ở trạng thái ẩn');

        @unlink(public_path(ltrim($p->images[0], '/')));
    }
}
