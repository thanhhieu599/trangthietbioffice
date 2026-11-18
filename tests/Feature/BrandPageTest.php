<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class BrandPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['admin.password' => 'pass-123456789']);
    }

    protected function tearDown(): void
    {
        foreach (glob(public_path('images/brands/banners/deli.*')) ?: [] as $f) {
            @unlink($f);
        }
        parent::tearDown();
    }

    public function test_brand_page_groups_products_by_root_category_and_merges_spellings(): void
    {
        $pens = Category::create(['name' => 'Bút viết', 'slug' => 'but-viet', 'is_active' => true, 'sort_order' => 1]);
        $gel = Category::create(['name' => 'Bút gel', 'slug' => 'but-gel', 'parent_id' => $pens->id, 'is_active' => true]);
        $paper = Category::create(['name' => 'Giấy in', 'slug' => 'giay-in', 'is_active' => true, 'sort_order' => 2]);
        $mk = fn ($sku, $brand, $cat) => Product::create(['sku' => $sku, 'name' => "SP $sku", 'slug' => strtolower($sku), 'brand' => $brand, 'category_id' => $cat, 'is_active' => true]);
        $mk('A1', 'DELI', $gel->id);   // danh mục con -> nhóm "Bút viết"
        $mk('A2', 'Deli', $pens->id);  // cách viết khác, cùng thương hiệu
        $mk('A3', 'DELI', $paper->id);
        $mk('B1', 'Plus', $paper->id); // thương hiệu khác

        $html = $this->get(route('brands.show', 'deli'))->assertOk()->getContent();
        $this->assertSame(2, substr_count($html, 'id="nhom-'));
        $this->assertStringContainsString('id="nhom-but-viet"', $html);
        $this->assertStringContainsString('id="nhom-giay-in"', $html);
        $this->assertStringNotContainsString('SP B1', $html);
        $this->assertTrue(strpos($html, 'id="nhom-but-viet"') < strpos($html, 'id="nhom-giay-in"'), 'Theo thứ tự danh mục');
        $this->assertStringContainsString('<strong class="text-ink-900">3</strong> sản phẩm', $html);

        $this->get(route('brands.index'))->assertOk()->assertSeeText('DELI')->assertDontSeeText('Deli (');
    }

    public function test_admin_uploads_brand_banner_shown_on_brand_page(): void
    {
        Product::create(['sku' => 'A1', 'name' => 'SP', 'slug' => 'sp', 'brand' => 'DELI', 'is_active' => true]);
        $admin = $this->withSession(['admin_ok' => true]);

        $admin->get(route('admin.media.brands'))->assertOk()->assertSee('DELI');
        $admin->post(route('admin.media.brand', 'deli'), ['image' => UploadedFile::fake()->image('b.jpg', 1200, 300)])->assertRedirect();

        $this->assertFileExists(public_path('images/brands/banners/deli.jpg'));
        $this->get(route('brands.show', 'deli'))->assertSee('/images/brands/banners/deli.jpg', false);

        $admin->post(route('admin.media.brand', 'deli'), ['remove' => 1])->assertRedirect();
        $this->assertFileDoesNotExist(public_path('images/brands/banners/deli.jpg'));
        $admin->post(route('admin.media.brand', '..%2Fx'), ['remove' => 1])->assertNotFound();
    }
}
