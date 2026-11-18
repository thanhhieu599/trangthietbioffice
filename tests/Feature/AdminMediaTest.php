<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\FlashSale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AdminMediaTest extends TestCase
{
    use RefreshDatabase;

    private array $made = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['admin.password' => 'pass-123456789']);
        $this->withSession(['admin_ok' => true]);
    }

    protected function tearDown(): void
    {
        foreach ($this->made as $path) {
            @unlink(public_path(ltrim($path, '/')));
        }
        parent::tearDown();
    }

    public function test_all_tabs_open_and_menu_has_image_section(): void
    {
        Category::create(['name' => 'Giấy in', 'slug' => 'giay-in', 'is_active' => true]);
        FlashSale::create(['name' => 'Flash mỗi ngày', 'starts_at' => now()->subHour(), 'ends_at' => now()->addDay(), 'is_active' => true]);

        $this->get(route('admin.banners.index'))->assertOk()->assertSee('Hình ảnh &amp; banner', false)->assertSee('Chuyển hướng 301')->assertSee('Đồng bộ Sapo');
        $this->get(route('admin.media.categories'))->assertOk()->assertSee('Giấy in')->assertSee('Banner ngang')->assertSee('Banner dọc');
        $this->get(route('admin.media.flash'))->assertOk()->assertSee('Flash mỗi ngày')->assertSee('Banner điện thoại');
        $this->get(route('admin.media.contact'))->assertOk()->assertSee('Ảnh nền đầu trang Liên hệ');
        $this->get(route('admin.contacts.index'))->assertOk()->assertDontSee('Ảnh nền đầu trang Liên hệ');
    }

    public function test_failed_upload_shows_vietnamese_message(): void
    {
        $this->assertSame('vi', app()->getLocale());
        $c = Category::create(['name' => 'Giấy in', 'slug' => 'giay-in', 'is_active' => true]);
        $tmp = tempnam(sys_get_temp_dir(), 'up');
        file_put_contents($tmp, 'x');
        // Mô phỏng PHP từ chối file vì vượt upload_max_filesize
        $file = new UploadedFile($tmp, 'anh-lon.jpg', 'image/jpeg', UPLOAD_ERR_INI_SIZE, true);

        $this->post(route('admin.media.category', [$c, 'banner']), ['image' => $file])
            ->assertRedirect()->assertInvalid(['image' => 'chưa tải lên được']);
        @unlink($tmp);
    }

    public function test_category_banner_upload_shows_on_site_and_can_be_removed(): void
    {
        $c = Category::create(['name' => 'Giấy in', 'slug' => 'giay-in', 'is_active' => true]);

        $this->post(route('admin.media.category', [$c, 'page_banner']), ['image' => UploadedFile::fake()->image('a.jpg', 300, 100)])
            ->assertSessionHasErrors('image'); // quá nhỏ

        $this->post(route('admin.media.category', [$c, 'page_banner']), ['image' => UploadedFile::fake()->image('a.jpg', 1200, 380)])->assertRedirect();
        $path = $c->fresh()->page_banner;
        $this->made[] = $path;
        $this->assertStringStartsWith('/images/categories/uploads/banner-ngang-giay-in', $path);
        $this->assertFileExists(public_path(ltrim($path, '/')));

        $this->post(route('admin.media.category', [$c, 'page_banner']), ['remove' => 1])->assertRedirect();
        $this->assertNull($c->fresh()->page_banner);
        $this->assertFileDoesNotExist(public_path(ltrim($path, '/')));

        $this->post(route('admin.media.category', [$c, 'name']), ['remove' => 1])->assertNotFound();
    }
}
