<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\QuoteRequest;
use App\Support\Colors;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Thuộc tính sản phẩm (Màu sắc = chấm màu) và phiên bản có SKU riêng – 1 sản phẩm, không tách mỗi màu 1 sản phẩm. */
class ProductColorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['admin.password' => 'pass-123456789']);
    }

    private function highlighter(array $over = []): Product
    {
        return Product::create(array_merge([
            'sku' => 'EU366-CAM', 'name' => 'Bút highlight Deli EU366', 'slug' => 'but-highlight-deli-eu366', 'brand' => 'DELI',
            'price' => 9000, 'is_active' => true,
            'attrs' => [['name' => 'Màu sắc', 'values' => ['Cam', 'Hồng']]],
            'variants' => [
                ['sku' => 'EU366-CAM', 'options' => ['Màu sắc' => 'Cam'], 'price' => 9000, 'stock_status' => 'in_stock'],
                ['sku' => 'EU366-HONG', 'options' => ['Màu sắc' => 'Hồng'], 'price' => 9500, 'stock_status' => 'out_of_stock'],
            ],
        ], $over));
    }

    public function test_color_names_map_to_hex(): void
    {
        $this->assertSame('#2e9e4f', Colors::hex('Xanh lá'));
        $this->assertSame('#1e63c6', Colors::hex('Xanh dương'));
        $this->assertSame('#4b5563', Colors::hex('Xám đậm'));
        $this->assertNull(Colors::hex('Màu lạ'));
    }

    public function test_product_page_shows_brand_and_color_swatches_from_attributes(): void
    {
        $p = $this->highlighter(['attrs' => [['name' => 'Màu sắc', 'values' => ['Cam', 'Hồng']], ['name' => 'Kích thước', 'values' => ['Nhỏ', 'Lớn']]]]);

        $html = $this->get($p->url())->assertOk()->getContent();
        $this->assertStringContainsString('>DELI</a>', $html);
        $this->assertStringContainsString('aria-label="Màu sắc Cam"', $html);
        $this->assertStringContainsString('aria-label="Màu sắc Hồng"', $html);
        $this->assertStringContainsString('background-color: #f28c28', $html);
        $this->assertStringContainsString('aria-label="Kích thước"', $html); // thuộc tính khác = nút
        $this->assertStringContainsString('>Lớn</button>', $html);
        $this->assertSame(1, Product::count(), 'Chỉ 1 sản phẩm, không tách theo màu');
    }

    public function test_admin_saves_attributes_from_name_value_rows(): void
    {
        $p = Product::create(['sku' => 'BB-1', 'name' => 'Bút bi', 'slug' => 'but-bi', 'price' => 3000, 'is_active' => true]);

        $this->withSession(['admin_ok' => true])->post(route('admin.products.update', $p), [
            'name' => 'Bút bi', 'sku' => 'BB-1', 'price' => 3000, 'is_active' => 1,
            'attrs' => [['name' => 'Màu sắc', 'values' => 'Xanh, Đỏ, Xanh'], ['name' => 'Kích thước', 'values' => '0.5mm'], ['name' => '', 'values' => 'bỏ']],
        ])->assertSessionHasNoErrors();

        $this->assertSame([['name' => 'Màu sắc', 'values' => ['Xanh', 'Đỏ']], ['name' => 'Kích thước', 'values' => ['0.5mm']]], $p->fresh()->attrs);
        $this->get($p->url())->assertSee('aria-label="Màu sắc Đỏ"', false);
    }

    public function test_variant_sku_in_cart_resolves_to_product_and_option_label(): void
    {
        $this->highlighter();
        $plain = Product::create(['sku' => 'BB-1', 'name' => 'Bút bi', 'slug' => 'but-bi', 'price' => 3000, 'is_active' => true,
            'attrs' => [['name' => 'Màu sắc', 'values' => ['Xanh', 'Đỏ']]]]);

        $this->getJson(route('quote.lookup', ['skus' => ['EU366-HONG', 'BB-1']]))->assertOk()
            ->assertJsonFragment(['sku' => 'EU366-HONG', 'name' => 'Bút highlight Deli EU366', 'variant' => 'Màu sắc: Hồng'])
            ->assertJsonFragment(['sku' => 'BB-1', 'variant' => null]);

        $this->post(route('quote.store'), [
            'contact_name' => 'An', 'email' => 'an@x.vn', 'phone' => '0909123456', 'address' => '1 Đường A', 'ward' => 'P1', 'province' => 'Hà Nội', 'consent' => '1',
            'items' => [['sku' => 'EU366-HONG', 'quantity' => 2], ['sku' => 'BB-1', 'quantity' => 5, 'opts' => 'Màu sắc: Đỏ']],
        ])->assertRedirect(route('quote.thanks'));

        $items = QuoteRequest::firstOrFail()->items()->orderBy('id')->get();
        $this->assertSame('Bút highlight Deli EU366 (Màu sắc: Hồng)', $items[0]->name);
        $this->assertSame(9500, (int) $items[0]->unit_price, 'Giá riêng của phiên bản');
        $this->assertSame('Bút bi (Màu sắc: Đỏ)', $items[1]->name);
        $this->assertSame($plain->id, $items[1]->product_id);
    }

    public function test_card_shows_color_dots_and_opens_product_to_choose(): void
    {
        $p = $this->highlighter();
        $html = $this->get(route('search', ['q' => 'highlight']))->assertOk()->getContent();
        $this->assertStringContainsString('aria-label="Chọn phân loại Bút highlight Deli EU366"', $html);
        $this->assertStringContainsString('title="Cam, Hồng"', $html);
    }
}
