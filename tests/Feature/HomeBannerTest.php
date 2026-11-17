<?php

namespace Tests\Feature;

use App\Models\Banner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class HomeBannerTest extends TestCase
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
        foreach (Banner::all() as $b) {
            foreach ([$b->image, $b->image_mobile] as $p) {
                if ($p && str_starts_with($p, '/images/banners/uploads/')) {
                    @unlink(public_path(ltrim($p, '/')));
                }
            }
        }
        parent::tearDown();
    }

    private function admin(): static
    {
        return $this->withSession(['admin_ok' => true]);
    }

    private function make(string $position, string $title, ?string $link = '/bao-gia', bool $active = true): Banner
    {
        return Banner::create(['position' => $position, 'title' => $title, 'image' => "/images/test-$title.jpg", 'link' => $link, 'is_active' => $active, 'sort_order' => 0]);
    }

    public function test_admin_uploads_banner_to_each_position_with_its_own_minimum_width(): void
    {
        $this->admin()->post(route('admin.banners.store'), ['position' => 'side', 'image' => UploadedFile::fake()->image('ben-phai.jpg', 600, 230), 'title' => 'Bên phải'])
            ->assertSessionHasNoErrors();
        $this->admin()->post(route('admin.banners.store'), ['position' => 'promo', 'image' => UploadedFile::fake()->image('duoi.png', 600, 400)])
            ->assertSessionHasNoErrors();
        // Banner chính cần ảnh rộng ít nhất 800 px.
        $this->admin()->post(route('admin.banners.store'), ['position' => 'slider', 'image' => UploadedFile::fake()->image('nho.jpg', 600, 230)])
            ->assertSessionHasErrors('image');

        $this->assertSame(['promo', 'side'], Banner::orderBy('position')->pluck('position')->all());
        $this->assertMatchesRegularExpression('#^/images/banners/uploads/[\w-]+\.png$#', Banner::where('position', 'promo')->value('image'));
    }

    public function test_upload_rejects_php_disguised_as_image_and_javascript_links(): void
    {
        $jpeg = UploadedFile::fake()->image('a.jpg', 900, 400);
        $php = UploadedFile::fake()->createWithContent('shell.php', file_get_contents($jpeg->getPathname()));
        $this->admin()->post(route('admin.banners.store'), ['position' => 'slider', 'image' => $php])->assertSessionHasErrors('image');

        $this->admin()->post(route('admin.banners.store'), ['position' => 'slider', 'image' => UploadedFile::fake()->image('ok.jpg', 900, 400), 'link' => 'javascript:alert(1)'])
            ->assertSessionHasErrors('link');
        $this->assertSame(0, Banner::count());
    }

    public function test_admin_page_shows_layout_map_and_groups(): void
    {
        $this->make('side', 'Ben-phai');
        $this->admin()->get(route('admin.banners.index'))->assertOk()
            ->assertSee('Sơ đồ khu banner trang chủ')->assertSee('Banner bên phải')->assertSee('Hàng banner nhỏ bên dưới')->assertSee('600 × 230');
    }

    public function test_admin_can_move_banner_to_another_position(): void
    {
        $b = $this->make('slider', 'Chinh');
        $this->admin()->post(route('admin.banners.update', $b), ['position' => 'promo', 'sort_order' => 1, 'is_active' => '1', 'link' => '/bao-gia'])
            ->assertSessionHasNoErrors();
        $this->assertSame('promo', $b->fresh()->position);
    }

    public function test_home_renders_slider_side_and_promo_rows_with_limits(): void
    {
        $this->make('slider', 'Chinh-1');
        $this->make('side', 'Phai-1');
        $this->make('side', 'Phai-2');
        $this->make('side', 'Phai-3-khong-hien');
        foreach (range(1, 5) as $i) {
            $this->make('promo', "Duoi-$i");
        }
        $this->make('promo', 'Duoi-an', active: false);

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('lg:grid-cols-[2fr_1fr]', $html);
        $this->assertStringContainsString('alt="Phai-2"', $html);
        $this->assertStringNotContainsString('Phai-3-khong-hien', $html, 'Chỉ hiện 2 banner bên phải');
        $this->assertStringContainsString('alt="Duoi-4"', $html);
        $this->assertStringNotContainsString('alt="Duoi-5"', $html, 'Chỉ hiện 4 banner hàng dưới');
        $this->assertStringNotContainsString('Duoi-an', $html);
        $this->assertSame(1, substr_count($html, 'fetchpriority="high"'), 'Chỉ ảnh đầu của banner chính được ưu tiên tải');
    }

    public function test_three_small_banners_split_into_three_equal_columns(): void
    {
        $this->make('slider', 'Chinh-1');
        foreach (range(1, 3) as $i) {
            $this->make('promo', "Nho-$i");
        }
        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('md:grid-cols-3', $html);
        $this->assertStringNotContainsString('md:grid-cols-4', $html);
        $this->assertSame(3, substr_count($html, 'alt="Nho-'));
    }

    public function test_slider_takes_full_width_when_no_side_banners(): void
    {
        $this->make('slider', 'Chinh-1');
        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringNotContainsString('lg:grid-cols-[2fr_1fr]', $html);
    }

    public function test_category_menu_is_closed_until_clicked_on_every_page(): void
    {
        foreach (['/', '/tin-tuc'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertMatchesRegularExpression('#x-data="\{ open: false \}" @click\.outside="open = false"#', $html, $url);
            $this->assertStringContainsString('@click="open = !open"', $html);
            $this->assertStringNotContainsString('@mouseenter="open = true"', $html);
        }
    }
}
