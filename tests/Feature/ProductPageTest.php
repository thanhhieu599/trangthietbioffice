<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_unit_row_and_name_suffix_split(): void
    {
        $this->assertSame(['Giấy in A4 Paper One 80gsm', '500 tờ/ram'], Product::splitUnitSuffix('Giấy in A4 Paper One 80gsm – ram 500 tờ', 'ram'));
        $this->assertSame(['Băng keo trong 48mm x 100 yard', '6 cuộn/cây'], Product::splitUnitSuffix('Băng keo trong 48mm x 100 yard – cây 6 cuộn', 'cây'));
        $this->assertSame(['Bìa còng bật 7cm khổ A4 – Kingjim', null], Product::splitUnitSuffix('Bìa còng bật 7cm khổ A4 – Kingjim', 'cái'));

        $p = $this->product(['unit' => 'ram', 'pack_size' => '500 tờ/ram']);
        $this->get($p->url())->assertOk()->assertSeeInOrder(['Tình trạng:', 'ĐVT:', 'Ram (500 tờ/ram)']);
    }

    private function product(array $over = []): Product
    {
        return Product::create(array_merge([
            'name' => 'Bìa hộp đựng hồ sơ Thiên Long Flexoffice A4', 'slug' => 'bia-hop-flexoffice-a4', 'sku' => '50006295',
            'price' => 82800, 'compare_price' => 92000, 'unit' => 'cái', 'brand' => 'Thiên Long', 'is_active' => true,
            'images' => ['/images/products/uploads/a.jpg', '/images/products/uploads/b.jpg'],
            'description' => '<p>Bìa hộp giúp lưu trữ tài liệu gọn gàng.</p><p><img src="/images/editor/uploads/x.jpg" alt="Bìa hộp mở nắp"></p>',
        ], $over));
    }

    private function schema(string $html): array
    {
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);

        return collect($m[1])->map(fn ($j) => json_decode($j, true))->firstWhere('@type', 'Product');
    }

    public function test_layout_shows_code_status_price_buttons_share_and_description(): void
    {
        $p = $this->product();
        $html = $this->get($p->url())->assertOk()->getContent();

        $this->assertMatchesRegularExpression('#Mã sản phẩm:</dt>\s*<dd[^>]*>50006295</dd>#', $html);
        $this->assertMatchesRegularExpression('#Tình trạng:</dt>\s*<dd[^>]*>Còn hàng</dd>#', $html);
        $this->assertStringContainsString('82.800đ', $html);
        $this->assertStringContainsString('92.000đ', $html);
        $this->assertStringContainsString('-10%', $html);
        $this->assertStringContainsString('Thêm vào giỏ', $html);
        $this->assertStringContainsString('Mua ngay', $html);
        $this->assertStringContainsString('facebook.com/sharer/sharer.php?u='.urlencode($p->url()), $html);
        $this->assertStringContainsString('pinterest.com/pin/create', $html);
        $this->assertStringContainsString('Sao chép liên kết', $html);
        $this->assertStringContainsString('Mô tả sản phẩm', $html);
        $this->assertStringContainsString('alt="Bìa hộp mở nắp"', $html); // ảnh chèn trong mô tả
        $this->assertStringNotContainsString('Đơn vị chịu trách nhiệm', $html);
        $this->assertSame(1, preg_match_all('#<h1[\s>]#', $html));
    }

    public function test_stock_status_drives_label_and_schema(): void
    {
        $p = $this->product(['stock_status' => 'out_of_stock']);
        $html = $this->get($p->url())->getContent();
        $this->assertStringContainsString('Hết hàng', $html);
        $this->assertSame('https://schema.org/OutOfStock', $this->schema($html)['offers']['availability']);

        $p->update(['stock_status' => 'contact']);
        $offer = $this->schema($this->get($p->url())->getContent())['offers'];
        $this->assertArrayNotHasKey('availability', $offer, '"Liên hệ" thì không khai báo tình trạng cho Google');
    }

    public function test_product_without_images_or_description_still_renders(): void
    {
        $p = $this->product(['images' => null, 'description' => null, 'price' => null, 'compare_price' => null]);
        $this->get($p->url())->assertOk()->assertSee('Chưa có ảnh')->assertSee('Liên hệ báo giá')->assertSee('đang được cập nhật');
    }

    public function test_admin_can_set_stock_status(): void
    {
        config(['admin.password' => 'pass-123456789']);
        $p = $this->product();
        $this->withSession(['admin_ok' => true])->get(route('admin.products.edit', $p))->assertOk()->assertSee('Tình trạng hàng');

        $this->withSession(['admin_ok' => true])->post(route('admin.products.update', $p), [
            'name' => $p->name, 'sku' => $p->sku, 'stock_status' => 'preorder', 'is_active' => '1',
            'keep_images' => $p->images,
        ])->assertSessionHasNoErrors();
        $this->assertSame('preorder', $p->fresh()->stock_status);
        $this->assertSame('Đặt trước', $p->fresh()->stockLabel());
    }
}