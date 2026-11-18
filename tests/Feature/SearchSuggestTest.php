<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchSuggestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $cat = Category::create(['name' => 'Bút viết', 'slug' => 'but-viet', 'is_active' => true]);
        $make = fn ($name, $sku, $price = null, $active = true) => Product::create(['category_id' => $cat->id, 'name' => $name, 'slug' => \Illuminate\Support\Str::slug($name), 'sku' => $sku, 'price' => $price, 'is_active' => $active]);
        $make('Bút bi Thiên Long TL-027 xanh', 'TL-027', 4500);
        $make('Bút bi Thiên Long TL-079 đỏ', 'TL-079');
        $make('Bút dạ quang vàng', 'DQ-01', 9000);
        $make('Bút bi ngừng bán', 'OLD-1', 1000, false);
    }

    public function test_returns_matching_products_with_price_and_categories(): void
    {
        $json = $this->getJson(route('search.suggest', ['q' => 'bút bi']))->assertOk()->json();

        $this->assertSame(2, $json['total'], 'Sản phẩm ngừng bán không được gợi ý');
        $this->assertSame(['TL-027', 'TL-079'], array_column($json['products'], 'sku'));
        $this->assertSame('4.500đ', $json['products'][0]['price']);
        $this->assertSame('Liên hệ báo giá', $json['products'][1]['price']);
        $this->assertStringContainsString('/san-pham/', $json['products'][0]['url']);

        $this->assertSame('Bút viết', $this->getJson(route('search.suggest', ['q' => 'bút v']))->json('categories.0.name'));
    }

    public function test_matches_every_word_in_any_order_and_by_sku(): void
    {
        $this->assertSame(['TL-027'], array_column($this->getJson(route('search.suggest', ['q' => 'xanh bút']))->json('products'), 'sku'));
        $this->assertSame(['TL-079'], array_column($this->getJson(route('search.suggest', ['q' => 'tl-079']))->json('products'), 'sku'));
    }

    public function test_short_or_wildcard_terms_return_nothing(): void
    {
        $this->getJson(route('search.suggest', ['q' => 'b']))->assertExactJson(['products' => [], 'categories' => [], 'total' => 0]);
        $this->assertSame(0, $this->getJson(route('search.suggest', ['q' => '%%']))->json('total'));
    }

    public function test_header_has_suggest_box(): void
    {
        $this->get('/')->assertOk()->assertSee('searchSuggest(', false)->assertSee('\/api\/goi-y-tim-kiem', false);
    }
}
