<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Bộ test SEO kỹ thuật. "Production" được giả lập với tên miền .example (dành riêng cho tài liệu, không phải tên miền thật).
 */
class SeoTest extends TestCase
{
    use RefreshDatabase;

    private const PROD = 'https://www.dt-office.example';

    private Category $paper;

    private Category $empty;

    private Product $priced;

    private Product $quoteOnly;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Cache::flush();

        $root = Category::create(['name' => 'Giấy in – giấy photo', 'slug' => 'giay-in-giay-photo', 'is_active' => true]);
        $this->paper = Category::create(['name' => 'Giấy A4', 'slug' => 'giay-a4', 'parent_id' => $root->id, 'is_active' => true]);
        $this->empty = Category::create(['name' => 'Giấy in nhiệt', 'slug' => 'giay-in-nhiet', 'parent_id' => $root->id, 'is_active' => true]);
        $this->priced = Product::create(['name' => 'Giấy in A4 Double A 80gsm', 'slug' => 'giay-a4-double-a-80', 'sku' => 'DA80',
            'category_id' => $this->paper->id, 'price' => 75000, 'brand' => 'Double A', 'is_active' => true]);
        $this->quoteOnly = Product::create(['name' => 'Giấy A4 IK Plus 70gsm', 'slug' => 'giay-a4-ik-plus-70', 'sku' => 'IK70',
            'category_id' => $this->paper->id, 'price' => null, 'is_active' => true]);
        Post::create(['title' => 'Cách chọn giấy in', 'slug' => 'cach-chon-giay-in', 'content' => '<p>Nội dung</p>', 'published_at' => now()->subDay()]);
        Post::create(['title' => 'Bài nháp', 'slug' => 'bai-nhap', 'content' => '<p>x</p>', 'published_at' => null]);
    }

    private function production(): void
    {
        $this->app['env'] = 'production';
        config(['app.url' => self::PROD, 'app.debug' => false]);
    }

    private function canonical(TestResponse $r): ?string
    {
        return preg_match('#<link rel="canonical" href="([^"]+)"#', $r->getContent(), $m) ? html_entity_decode($m[1]) : null;
    }

    private function jsonLd(TestResponse $r): array
    {
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $r->getContent(), $m);

        return array_map(function ($raw) {
            $data = json_decode($raw, true);
            $this->assertSame(JSON_ERROR_NONE, json_last_error(), 'JSON-LD không hợp lệ: '.$raw);

            return $data;
        }, $m[1]);
    }

    /* ---------------- robots / staging ---------------- */

    public function test_production_robots_allows_crawl_and_points_to_production_sitemap(): void
    {
        $this->production();
        $r = $this->get('/robots.txt')->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8');

        $this->assertDoesNotMatchRegularExpression('/^Disallow:\s*\/\s*$/m', $r->getContent());
        $this->assertStringContainsString('Sitemap: '.self::PROD.'/sitemap.xml', $r->getContent());
        $this->assertStringContainsString('Disallow: /quan-tri', $r->getContent());
        $r->assertHeaderMissing('X-Robots-Tag');
    }

    public function test_non_production_blocks_crawl_and_sends_noindex_header(): void
    {
        $this->get('/robots.txt')->assertSee("Disallow: /\n", false);
        $this->get('/')->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    /* ---------------- canonical / host ---------------- */

    public function test_production_absolute_urls_ignore_request_host(): void
    {
        $this->production();
        // Request tới một Host khác (IP, www/không www, Host giả) vẫn phải ra canonical theo APP_URL.
        $r = $this->get('http://evil.example/danh-muc/giay-a4')->assertOk();

        $this->assertSame(self::PROD.'/danh-muc/giay-a4', $this->canonical($r));
        $r->assertSee('<meta property="og:url" content="'.self::PROD.'/danh-muc/giay-a4">', false);
        $this->assertStringNotContainsString('evil.example', $r->getContent());

        // Sitemap đã cache cũng không bị "đầu độc" bởi Host giả.
        $this->assertStringNotContainsString('evil.example', $this->get('http://evil.example/sitemap.xml')->getContent());
    }

    public function test_canonical_strips_query_and_index_php(): void
    {
        $this->production();
        $this->assertSame(self::PROD.'/danh-muc/giay-a4', $this->canonical($this->get('/danh-muc/giay-a4?sort=name&brand=Double+A')));
        $this->assertSame(self::PROD, $this->canonical($this->get('/?utm_source=zalo')));
    }

    /* ---------------- sitemap ---------------- */

    public function test_sitemap_is_valid_and_contains_only_indexable_production_urls(): void
    {
        $this->production();
        $r = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $xml = simplexml_load_string($r->getContent());
        $this->assertNotFalse($xml);
        $locs = array_map('strval', $xml->xpath('//*[local-name()="loc"]'));

        foreach ($locs as $loc) {
            $this->assertStringStartsWith(self::PROD, $loc);
            $this->assertStringNotContainsString('localhost', $loc);
        }
        $this->assertContains(self::PROD.'/danh-muc/giay-a4', $locs);
        $this->assertContains(self::PROD.'/danh-muc/giay-in-giay-photo', $locs, 'Danh mục cha có sản phẩm ở danh mục con vẫn được index');
        $this->assertContains(self::PROD.'/san-pham/giay-a4-double-a-80', $locs);
        $this->assertContains(self::PROD.'/tin-tuc/cach-chon-giay-in', $locs);
        $this->assertNotContains(self::PROD.'/danh-muc/giay-in-nhiet', $locs, 'Danh mục rỗng không vào sitemap');
        $this->assertNotContains(self::PROD.'/tin-tuc/bai-nhap', $locs, 'Bài nháp không vào sitemap');
        $this->assertNotContains(self::PROD.'/tim-kiem', $locs);

        // Mọi URL trong sitemap: 200, không noindex, canonical trùng loc.
        foreach ($locs as $loc) {
            $page = $this->get(substr($loc, strlen(self::PROD)) ?: '/');
            $page->assertOk();
            $this->assertDoesNotMatchRegularExpression('#<meta name="robots" content="[^"]*noindex#', $page->getContent(), $loc);
            $this->assertSame(rtrim($loc, '/'), rtrim((string) $this->canonical($page), '/'), $loc);
        }
    }

    /* ---------------- indexability / status ---------------- */

    public function test_empty_category_is_noindex_but_still_reachable(): void
    {
        $this->get('/danh-muc/giay-in-nhiet')->assertOk()->assertSee('<meta name="robots" content="noindex, follow">', false);
        $this->get('/danh-muc/giay-a4')->assertOk()->assertDontSee('<meta name="robots"', false);
    }

    public function test_out_of_range_pagination_and_missing_pages_return_404(): void
    {
        $this->get('/danh-muc/giay-a4?page=99')->assertNotFound();
        $this->get('/tin-tuc?page=99')->assertNotFound();
        $this->get('/thuong-hieu/double-a?page=99')->assertNotFound();
        $this->get('/san-pham/khong-ton-tai')->assertNotFound();
        $this->get('/danh-muc/giay-a4?page=abc')->assertOk(); // tham số rác -> trang 1, canonical sạch
    }

    public function test_paginated_listing_canonical_points_to_itself(): void
    {
        $this->production();
        foreach (range(1, 13) as $i) {
            Post::create(['title' => "Bài $i", 'slug' => "bai-$i", 'content' => '<p>x</p>', 'published_at' => now()->subDays($i + 1)]);
        }
        $r = $this->get('/tin-tuc?page=2')->assertOk();
        $this->assertSame(self::PROD.'/tin-tuc?page=2', $this->canonical($r));
        $r->assertSee('Trang 2', false);
    }

    public function test_404_page_is_branded_noindex_without_canonical(): void
    {
        $r = $this->get('/khong-co-trang-nay')->assertNotFound();
        $r->assertSee('Không tìm thấy trang')->assertSee('<meta name="robots" content="noindex, follow">', false);
        $this->assertNull($this->canonical($r));
        $r->assertSee('href="'.url('/danh-muc/giay-in-giay-photo').'"', false); // gợi ý danh mục
    }

    public function test_private_and_utility_pages_are_not_indexable(): void
    {
        $this->get('/tim-kiem?q=giấy')->assertOk()->assertSee('<meta name="robots" content="noindex, follow">', false);
        $this->get('/bao-gia/cam-on')->assertOk()->assertSee('noindex, nofollow', false);
    }

    /* ---------------- structured data ---------------- */

    public function test_product_schema_has_offer_only_with_real_price(): void
    {
        $priced = collect($this->jsonLd($this->get('/san-pham/giay-a4-double-a-80')))->firstWhere('@type', 'Product');
        $this->assertSame(75000, $priced['offers']['price']);
        $this->assertSame('VND', $priced['offers']['priceCurrency']);
        $this->assertFalse($priced['offers']['priceSpecification']['valueAddedTaxIncluded']);
        $this->assertSame('DA80', $priced['sku']);
        $this->assertSame('Double A', $priced['brand']['name']);

        $quote = collect($this->jsonLd($this->get('/san-pham/giay-a4-ik-plus-70')))->firstWhere('@type', 'Product');
        $this->assertArrayNotHasKey('offers', $quote, 'Hàng "Liên hệ" không được có Offer / giá giả');
    }

    public function test_every_template_outputs_valid_json_ld_and_breadcrumbs(): void
    {
        $types = collect($this->jsonLd($this->get('/')))->pluck('@type');
        $this->assertTrue($types->contains('Organization') && $types->contains('WebSite'));

        foreach (['/danh-muc/giay-a4', '/san-pham/giay-a4-double-a-80', '/tin-tuc/cach-chon-giay-in', '/thuong-hieu/double-a'] as $url) {
            $ld = collect($this->jsonLd($this->get($url)));
            $crumb = $ld->firstWhere('@type', 'BreadcrumbList');
            $this->assertNotNull($crumb, $url);
            foreach ($crumb['itemListElement'] as $item) {
                $this->get(parse_url($item['item'], PHP_URL_PATH) ?: '/')->assertOk(); // không breadcrumb tới 404
            }
        }
        $this->assertNotNull(collect($this->jsonLd($this->get('/tin-tuc/cach-chon-giay-in')))->firstWhere('@type', 'Article'));
    }

    /* ---------------- metadata ---------------- */

    public function test_key_pages_have_unique_title_description_and_single_h1(): void
    {
        $titles = [];
        foreach (['/', '/danh-muc/giay-a4', '/san-pham/giay-a4-double-a-80', '/tin-tuc', '/tin-tuc/cach-chon-giay-in', '/thuong-hieu', '/lien-he', '/bao-gia'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertMatchesRegularExpression('#<title>[^<]{10,}</title>#', $html, $url);
            $this->assertMatchesRegularExpression('#<meta name="description" content="[^"]{20,}">#', $html, $url);
            $this->assertSame(1, preg_match_all('#<h1[\s>]#', $html), "$url phải có đúng 1 H1");
            $this->assertStringContainsString('<html lang="vi">', $html);
            preg_match('#<title>([^<]+)</title>#', $html, $m);
            $titles[] = $m[1];
        }
        $this->assertSame(count($titles), count(array_unique($titles)), 'Title bị trùng giữa các trang');
    }

    public function test_search_escapes_like_wildcards(): void
    {
        $this->get('/tim-kiem?q=%25')->assertOk()->assertDontSee('Giấy in A4 Double A 80gsm');
        $this->get('/tim-kiem?q=Double')->assertOk()->assertSee('Giấy in A4 Double A 80gsm');
    }

    public function test_security_headers_present(): void
    {
        $this->get('/')->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }

    /* ---------------- deployment gate ---------------- */

    public function test_seo_check_command_passes_in_production_config(): void
    {
        $this->production();
        $code = Artisan::call('seo:check', ['--sample' => 0]);
        $this->assertSame(0, $code, Artisan::output());
    }

    public function test_seo_check_command_fails_for_local_config(): void
    {
        config(['app.url' => 'http://localhost:8000', 'app.debug' => true]);
        $this->assertSame(1, Artisan::call('seo:check'));
        $out = Artisan::output();
        $this->assertStringContainsString('Disallow: /', $out);
        $this->assertStringContainsString('localhost', $out);
    }
}
