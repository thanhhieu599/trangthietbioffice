<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/** Banner danh mục: CHỈ LÀ ẢNH (không chữ/nút). Chưa có ảnh thì không hiện vị trí đó. */
class CategoryBannerTest extends TestCase
{
    use RefreshDatabase;

    private Category $cat;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['admin.password' => 'pass-123456789']);
        $this->cat = Category::create(['name' => 'Bút viết', 'slug' => 'but-viet', 'show_on_home' => true, 'intro' => 'Bút bi, bút gel, bút ký.']);
        foreach (range(1, 12) as $i) {
            Product::create(['name' => "Bút $i", 'slug' => "but-$i", 'sku' => "B$i", 'category_id' => $this->cat->id, 'is_active' => true]);
        }
    }

    protected function tearDown(): void
    {
        foreach (['banner', 'page_banner'] as $f) {
            if ($p = Category::find($this->cat->id)?->{$f}) {
                @unlink(public_path(ltrim($p, '/')));
            }
        }
        parent::tearDown();
    }

    private function homeSection(): string
    {
        $html = $this->get('/')->assertOk()->getContent();
        $start = strpos($html, 'id="sec-but-viet"');

        return substr($html, $start, strpos($html, '</section>', $start) - $start);
    }

    private function upload(array $files): void
    {
        $this->withSession(['admin_ok' => true])->post(route('admin.categories.update', $this->cat), [
            'name' => 'Bút viết', 'is_active' => '1', 'show_on_home' => '1',
        ] + $files)->assertSessionHasNoErrors();
    }

    public function test_without_image_home_section_keeps_banner_slot_without_text(): void
    {
        $section = $this->homeSection();
        $this->assertStringContainsString('lg:grid-cols-[minmax(0,1fr)_minmax(0,2fr)]', $section, 'Luôn giữ cột banner dọc');
        $this->assertStringContainsString('lg:grid-cols-4', $section);
        $this->assertSame(8, substr_count($section, '<article'));
        $this->assertStringContainsString('aria-label="Bút viết"', $section); // ô banner (chờ ảnh) có mặt
        $this->assertStringNotContainsString('Báo giá sỉ theo số lượng', $section, 'Banner không có chữ');
        $this->assertStringNotContainsString('Xem tất cả 12 sản phẩm', $section, 'Banner không có nút');
    }

    public function test_with_vertical_banner_home_section_has_image_only_banner_and_two_rows_of_four(): void
    {
        $this->upload(['banner' => UploadedFile::fake()->image('doc.jpg', 600, 1000), 'banner_link' => '/flash-sale']);
        $banner = $this->cat->fresh()->banner;

        $section = $this->homeSection();
        $this->assertStringContainsString('lg:grid-cols-[minmax(0,1fr)_minmax(0,2fr)]', $section);
        $this->assertStringContainsString('src="'.url($banner).'"', $section);
        $this->assertStringContainsString('href="/flash-sale"', $section);
        $this->assertSame(8, substr_count($section, '<article'));
    }

    public function test_category_page_shows_wide_image_banner_on_top_then_products(): void
    {
        // Chưa có banner ngang: vẫn giữ ô banner (chờ ảnh), không có thẻ ảnh banner.
        $html = $this->get('/danh-muc/but-viet')->assertOk()->getContent();
        $this->assertStringContainsString('aspect-[1200/380]', $html);
        $this->assertStringNotContainsString('width="1200" height="380"', $html);
        $this->assertSame(12, substr_count($html, '<article'));

        $this->upload(['page_banner' => UploadedFile::fake()->image('ngang.jpg', 1200, 380)]);
        $page = $this->cat->fresh()->page_banner;
        $this->assertMatchesRegularExpression('#^/images/categories/uploads/banner-ngang-but-viet-\w+\.jpg$#', $page);

        $html = $this->get('/danh-muc/but-viet')->assertOk()->getContent();
        $this->assertStringContainsString('src="'.url($page).'"', $html);
        $this->assertLessThan(strpos($html, '<article'), strpos($html, 'aspect-[1200/380]'), 'Banner nằm trên lưới sản phẩm');
        $this->assertStringContainsString('lg:grid-cols-5', $html);
        $this->assertStringNotContainsString('Gửi yêu cầu báo giá <', $html, 'Banner không có nút');
    }

    public function test_child_category_inherits_banners_and_small_images_are_rejected(): void
    {
        $this->upload(['page_banner' => UploadedFile::fake()->image('ngang.jpg', 1200, 380)]);
        $child = Category::create(['name' => 'Bút bi', 'slug' => 'but-bi', 'parent_id' => $this->cat->id]);
        $this->assertSame(url($this->cat->fresh()->page_banner), $child->pageBannerUrl());

        $this->withSession(['admin_ok' => true])->post(route('admin.categories.update', $this->cat), [
            'name' => 'Bút viết', 'page_banner' => UploadedFile::fake()->image('nho.jpg', 500, 160),
        ])->assertSessionHasErrors('page_banner');
    }

    public function test_banner_link_rejects_javascript(): void
    {
        $this->withSession(['admin_ok' => true])->post(route('admin.categories.update', $this->cat), [
            'name' => 'Bút viết', 'banner_link' => 'javascript:alert(1)',
        ])->assertSessionHasErrors('banner_link');
    }
}
