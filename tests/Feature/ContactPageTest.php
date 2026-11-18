<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['admin.password' => 'pass-123456789']);
    }

    public function test_page_shows_form_info_map_and_uploaded_images(): void
    {
        $this->get(route('contact'))->assertOk()
            ->assertSee('Gửi yêu cầu cho chúng tôi')
            ->assertSee('Hợp tác doanh nghiệp')
            ->assertSee('maps.google.com/maps?q=', false)
            ->assertDontSee('/images/banners/uploads/hero.jpg');

        Banner::create(['position' => 'contact_hero', 'image' => '/images/banners/uploads/hero.jpg', 'is_active' => true]);

        $this->get(route('contact'))->assertOk()
            ->assertSee('/images/banners/uploads/hero.jpg')
            ->assertDontSee('Sẵn sàng hỗ trợ')
            ->assertDontSee('Thông tin chỉ dùng để phản hồi');
    }

    public function test_message_is_saved_and_listed_in_admin(): void
    {
        $this->post(route('contact.store'), [
            'topic' => 'partner', 'name' => 'Anh Minh', 'phone' => '0909 123 456', 'email' => 'minh@cty.vn', 'content' => 'Muốn hợp tác cung ứng.',
        ])->assertRedirect(route('contact').'#gui-tin-nhan');

        $m = ContactMessage::firstOrFail();
        $this->assertSame('Hợp tác doanh nghiệp', $m->topicLabel());
        $this->get(route('contact'))->assertDontSee('Muốn hợp tác cung ứng'); // không hiện công khai

        $admin = $this->withSession(['admin_ok' => true]);
        $admin->get(route('admin.contacts.index'))->assertOk()->assertSee('Anh Minh')->assertSee('Muốn hợp tác cung ứng');
        $admin->post(route('admin.contacts.read', $m))->assertRedirect();
        $this->assertTrue($m->fresh()->is_read);
    }

    public function test_validation_and_spam_trap(): void
    {
        $this->post(route('contact.store'), ['topic' => 'xyz', 'name' => '', 'phone' => 'abc', 'email' => 'sai', 'content' => ''])
            ->assertSessionHasErrors(['topic', 'name', 'phone', 'email', 'content']);

        $this->post(route('contact.store'), ['topic' => 'quote', 'name' => 'Bot', 'phone' => '0909123456', 'email' => 'b@x.vn', 'content' => 'spam spam', 'website' => 'x'])
            ->assertRedirect();
        $this->assertSame(0, ContactMessage::count());
    }

    public function test_product_without_price_links_to_contact_with_prefilled_message(): void
    {
        $cat = \App\Models\Category::create(['name' => 'Băng keo', 'slug' => 'bang-keo']);
        $p = \App\Models\Product::create(['category_id' => $cat->id, 'name' => 'Băng keo 2 mặt 24mm', 'slug' => 'bang-keo-2-mat', 'sku' => 'DEMO-029', 'unit' => 'cuộn', 'price' => null, 'is_active' => true]);

        $this->get($p->url())->assertOk()
            ->assertSee('Liên hệ báo giá')
            ->assertSee(route('contact', ['san-pham' => 'DEMO-029']).'#gui-tin-nhan', false)
            ->assertDontSee('Mua ngay')
            ->assertDontSee('text-2xl font-bold text-primary-600 md:text-3xl', false); // không còn khung "Giá: Liên hệ báo giá"

        $this->get(route('contact', ['san-pham' => 'DEMO-029', 'so-luong' => 20]))->assertOk()
            ->assertSee('Tôi cần báo giá sản phẩm: Băng keo 2 mặt 24mm (Mã: DEMO-029), số lượng: 20 cuộn.');

        $p->update(['price' => 50000]);
        $this->get($p->url())->assertSee('Mua ngay');
    }

    public function test_contact_positions_stay_off_home_banner_page(): void
    {
        $this->withSession(['admin_ok' => true])->get(route('admin.banners.index'))->assertOk()->assertDontSee('Ảnh nền đầu trang Liên hệ');
    }
}
