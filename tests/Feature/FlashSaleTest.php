<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\FlashSale;
use App\Models\Product;
use App\Models\QuoteRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlashSaleTest extends TestCase
{
    use RefreshDatabase;

    private Category $paper;

    private Category $pens;

    private Category $empty;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['admin.password' => 'pass-123456789']);
        $this->paper = Category::create(['name' => 'Giấy in', 'slug' => 'giay-in', 'sort_order' => 1]);
        $this->pens = Category::create(['name' => 'Bút viết', 'slug' => 'but-viet', 'sort_order' => 2]);
        $this->empty = Category::create(['name' => 'Vệ sinh văn phòng', 'slug' => 've-sinh', 'sort_order' => 3]);
        $sub = Category::create(['name' => 'Bút bi', 'slug' => 'but-bi', 'parent_id' => $this->pens->id]);

        foreach ([['Giấy A4 Double A', 'DA', 100000, $this->paper->id], ['Bút bi TL-027', 'TL', 50000, $sub->id], ['Bìa còng', 'BC', 30000, $this->empty->id]] as [$n, $sku, $price, $cat]) {
            Product::create(['name' => $n, 'slug' => strtolower($sku), 'sku' => $sku, 'price' => $price, 'category_id' => $cat, 'is_active' => true]);
        }
    }

    private function sale(array $over = []): FlashSale
    {
        $sale = FlashSale::create(array_merge(['name' => 'Mừng 20/11', 'starts_at' => now()->subHour(), 'ends_at' => now()->addHours(5), 'is_active' => true], $over));
        $sale->items()->create(['product_id' => Product::where('sku', 'DA')->value('id'), 'sale_price' => 70000, 'quantity_limit' => 10, 'sort_order' => 0]);
        $sale->items()->create(['product_id' => Product::where('sku', 'TL')->value('id'), 'sale_price' => 40000, 'sort_order' => 1]);

        return $sale;
    }

    public function test_home_shows_flash_strip_only_when_running(): void
    {
        $this->get('/')->assertOk()->assertDontSee('flash-sale-title', false);

        $this->sale();
        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('flash-sale-title', $html);
        $this->assertStringContainsString('70.000đ', $html);
        $this->assertStringContainsString('-30%', $html);
        $this->assertStringContainsString(route('flash-sale'), $html);
        // Nằm giữa lưới danh mục và các khối sản phẩm.
        $this->assertLessThan(strpos($html, 'flash-sale-title'), strpos($html, 'Danh mục sản phẩm'));
    }

    public function test_ended_upcoming_or_disabled_sales_are_hidden(): void
    {
        $this->sale(['starts_at' => now()->subDays(2), 'ends_at' => now()->subDay()]);
        $this->sale(['name' => 'Sắp tới', 'starts_at' => now()->addDay(), 'ends_at' => now()->addDays(2)]);
        $this->sale(['name' => 'Tắt', 'is_active' => false]);

        $this->get('/')->assertDontSee('flash-sale-title', false);
        $this->get(route('flash-sale'))->assertOk()->assertSee('Hiện chưa có chương trình Flash Sale')
            ->assertSee('<meta name="robots" content="noindex, follow">', false);
    }

    public function test_auto_renew_sale_restarts_a_new_cycle_after_ending(): void
    {
        // Chu kỳ 24h đã hết từ 1 giờ trước -> phải tự sang chu kỳ mới, còn ~23h.
        $sale = $this->sale(['starts_at' => now()->subHours(25), 'ends_at' => now()->subHour(), 'auto_renew' => true]);

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('flash-sale-title', $html, 'Chương trình tự lặp vẫn hiện sau khi hết chu kỳ');
        $this->assertStringContainsString(', 86400)', $html, 'Đồng hồ nhận độ dài chu kỳ 24h để tự đếm lại');

        $sale->refresh();
        $this->assertTrue($sale->ends_at->isFuture());
        $this->assertEqualsWithDelta(23 * 3600, now()->diffInSeconds($sale->ends_at), 5);
        $this->assertSame(86400, (int) $sale->starts_at->diffInSeconds($sale->ends_at));

        // Không tự lặp thì hết giờ là ẩn.
        $sale->update(['auto_renew' => false, 'starts_at' => now()->subHours(25), 'ends_at' => now()->subHour()]);
        $this->get('/')->assertDontSee('flash-sale-title', false);
    }

    public function test_flash_page_groups_by_root_category_and_hides_empty_ones(): void
    {
        $this->sale();
        $html = $this->get(route('flash-sale'))->assertOk()->getContent();

        $this->assertStringContainsString('id="fs-giay-in"', $html);
        $this->assertStringContainsString('id="fs-but-viet"', $html, 'Sản phẩm ở danh mục con gom về danh mục gốc');
        $this->assertStringNotContainsString('id="fs-ve-sinh"', $html, 'Danh mục không có sản phẩm sale thì ẩn');
        $this->assertLessThan(strpos($html, 'id="fs-but-viet"'), strpos($html, 'id="fs-giay-in"'));
        $this->assertStringContainsString('FLASH', $html);
        $this->assertStringNotContainsString('noindex', $html);
    }

    public function test_product_page_and_schema_use_flash_price(): void
    {
        $sale = $this->sale();
        $html = $this->get('/san-pham/da')->assertOk()->getContent();
        $this->assertStringContainsString('70.000đ', $html);
        $this->assertStringContainsString('100.000đ', $html);

        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
        $product = collect($m[1])->map(fn ($j) => json_decode($j, true))->firstWhere('@type', 'Product');
        $this->assertSame(70000, $product['offers']['price']);
        $this->assertSame($sale->ends_at->toDateString(), $product['offers']['priceValidUntil']);
    }

    public function test_orders_store_flash_price_and_count_as_sold(): void
    {
        $this->sale();
        $this->post(route('quote.store'), [
            'contact_name' => 'An', 'email' => 'an@x.vn', 'phone' => '0909123456', 'address' => '1 A', 'ward' => 'B', 'province' => 'C',
            'items' => [['sku' => 'DA', 'quantity' => 8], ['sku' => 'BC', 'quantity' => 2]], 'consent' => '1',
        ])->assertRedirect(route('quote.thanks'));

        $items = QuoteRequest::first()->items->keyBy('sku');
        $this->assertSame(70000, (int) $items['DA']->unit_price);
        $this->assertTrue($items['DA']->is_flash_sale);
        $this->assertSame(30000, (int) $items['BC']->unit_price);
        $this->assertFalse($items['BC']->is_flash_sale);

        // 8/10 suất -> 80% -> "Sắp hết"
        $this->get(route('flash-sale'))->assertSee('Sắp hết – còn 2')->assertSee('width: 80%', false);
    }

    public function test_admin_creates_sale_with_validation(): void
    {
        $da = Product::where('sku', 'DA')->value('id');
        $base = ['name' => 'Black Friday', 'starts_at' => now()->addHour()->format('Y-m-d\TH:i'), 'ends_at' => now()->addDay()->format('Y-m-d\TH:i'), 'is_active' => '1'];

        // Giá flash không thấp hơn giá gốc -> lỗi.
        $this->withSession(['admin_ok' => true])->post(route('admin.flash-sales.store'), $base + ['items' => [['product_id' => $da, 'sale_price' => 100000]]])
            ->assertSessionHasErrors('items');

        $this->withSession(['admin_ok' => true])->post(route('admin.flash-sales.store'), $base + ['items' => [['product_id' => $da, 'sale_price' => 79000, 'quantity_limit' => 50]]])
            ->assertSessionHasNoErrors();
        $this->assertSame(79000, (int) FlashSale::first()->items->first()->sale_price);

        // Chương trình thứ hai trùng giờ -> lỗi.
        $this->withSession(['admin_ok' => true])->post(route('admin.flash-sales.store'), $base + ['name' => 'Trùng'])->assertSessionHasErrors('starts_at');

        $this->withSession(['admin_ok' => true])->get(route('admin.flash-sales.index'))->assertOk()->assertSee('Black Friday')->assertSee('Sắp diễn ra');
        $this->withSession(['admin_ok' => true])->getJson(route('admin.flash-sales.products', ['q' => 'Double']))->assertOk()->assertJsonFragment(['sku' => 'DA']);
    }
}
