<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\QuoteRequest;
use App\Models\User;
use App\Notifications\ResetPasswordVi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function info(array $over = []): array
    {
        return array_merge([
            'name' => 'Nguyễn Văn An',
            'email' => 'an@congty.vn',
            'phone' => '0909 123 456',
            'company' => 'Công ty TNHH ABC',
            'tax_code' => '0312345678',
            'address' => '87 Đường 16, KDC Phong Phú 4',
            'ward' => 'Xã Bình Hưng',
            'province' => 'TP Hồ Chí Minh',
        ], $over);
    }

    private function customer(array $over = []): User
    {
        return User::create($this->info($over) + ['password' => 'matkhau123']);
    }

    private function order(array $over = []): array
    {
        $i = $this->info();

        return array_merge([
            'contact_name' => $i['name'], 'email' => $i['email'], 'phone' => $i['phone'],
            'company' => $i['company'], 'tax_code' => $i['tax_code'],
            'address' => $i['address'], 'ward' => $i['ward'], 'province' => $i['province'],
            'items' => [['sku' => 'DA80', 'quantity' => 5]],
            'consent' => '1',
        ], $over);
    }

    /* ---------- header ---------- */

    public function test_header_shows_login_register_for_guests_and_name_for_customers(): void
    {
        $this->get('/')->assertOk()->assertSee('Đăng nhập')->assertSee('Đăng ký')->assertSee(route('login'), false);

        $this->actingAs($this->customer())->get('/')->assertOk()
            ->assertSee('Xin chào')->assertSee('An')->assertSee('Đơn hàng của tôi')->assertDontSee('href="'.route('register').'"', false);
    }

    /* ---------- đăng ký / đăng nhập ---------- */

    public function test_register_requires_full_information(): void
    {
        $this->post(route('register.submit'), ['email' => 'x@y.vn', 'password' => '12345678', 'password_confirmation' => '12345678'])
            ->assertSessionHasErrors(['name', 'phone', 'address', 'ward', 'province', 'terms']);
        $this->assertGuest();
        $this->assertSame('Vui lòng nhập số điện thoại.', session('errors')->first('phone'));
    }

    public function test_register_creates_account_with_all_fields_and_logs_in(): void
    {
        $this->post(route('register.submit'), $this->info() + ['password' => 'matkhau123', 'password_confirmation' => 'matkhau123', 'terms' => '1'])
            ->assertRedirect(route('account.show'));

        $user = User::firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('Xã Bình Hưng', $user->ward);
        $this->assertSame('0312345678', $user->tax_code);
        $this->assertTrue(Hash::check('matkhau123', $user->password));
        $this->assertTrue($user->hasDeliveryInfo());
    }

    public function test_register_rejects_existing_email(): void
    {
        $this->customer();
        $this->post(route('register.submit'), $this->info() + ['password' => 'matkhau123', 'password_confirmation' => 'matkhau123', 'terms' => '1'])
            ->assertSessionHasErrors('email');
        $this->assertSame(1, User::count());
    }

    public function test_login_wrong_password_then_success_returns_to_checkout(): void
    {
        $this->customer();
        $this->post(route('login.submit'), ['email' => 'an@congty.vn', 'password' => 'sai'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->get(route('login', ['quay-lai' => '/bao-gia']))->assertOk();
        $this->post(route('login.submit'), ['email' => 'an@congty.vn', 'password' => 'matkhau123'])->assertRedirect('/bao-gia');
        $this->assertAuthenticated();
    }

    public function test_login_ignores_external_return_url(): void
    {
        $this->customer();
        $this->get(route('login', ['quay-lai' => '//evil.example/x']));
        $this->post(route('login.submit'), ['email' => 'an@congty.vn', 'password' => 'matkhau123'])->assertRedirect(route('account.show'));
    }

    public function test_logged_in_users_are_redirected_away_from_login(): void
    {
        $this->actingAs($this->customer())->get(route('login'))->assertRedirect(route('account.show'));
    }

    public function test_logout(): void
    {
        $this->actingAs($this->customer())->post(route('logout'))->assertRedirect(route('home'));
        $this->assertGuest();
    }

    /* ---------- đặt hàng ---------- */

    public function test_guest_checkout_requires_contact_and_delivery_address(): void
    {
        $this->post(route('quote.store'), ['items' => [['sku' => 'DA80', 'quantity' => 1]]])
            ->assertSessionHasErrors(['contact_name', 'phone', 'email', 'address', 'ward', 'province', 'consent']);
        $this->assertSame(0, QuoteRequest::count());
    }

    public function test_guest_can_checkout_without_account_and_company_is_optional(): void
    {
        Product::create(['name' => 'Giấy A4 Double A', 'slug' => 'giay-a4-da', 'sku' => 'DA80', 'is_active' => true]);

        $this->post(route('quote.store'), $this->order(['company' => '', 'tax_code' => '']))
            ->assertRedirect(route('quote.thanks'))->assertSessionHas('quote_code', 'DT000001');

        $order = QuoteRequest::with('items')->firstOrFail();
        $this->assertNull($order->user_id);
        $this->assertNull($order->company);
        $this->assertSame('87 Đường 16, KDC Phong Phú 4, Xã Bình Hưng, TP Hồ Chí Minh', $order->fullAddress());
        $this->assertSame('Giấy A4 Double A', $order->items->first()->name);
    }

    public function test_checkout_page_prefills_customer_info_and_links_order_to_account(): void
    {
        $user = $this->customer(['address' => '12 Lê Lợi']);
        $this->actingAs($user);

        $this->get(route('quote.create'))->assertOk()
            ->assertSee('value="12 Lê Lợi"', false)->assertSee('value="Nguyễn Văn An"', false)->assertDontSee('Đã có tài khoản?');

        $this->post(route('quote.store'), $this->order(['address' => '99 Nguyễn Huệ', 'save_to_account' => '1']))->assertRedirect(route('quote.thanks'));

        $order = QuoteRequest::firstOrFail();
        $this->assertSame($user->id, $order->user_id);
        $this->assertSame('99 Nguyễn Huệ', $user->fresh()->address, 'Chọn "lưu vào tài khoản" thì cập nhật địa chỉ mặc định');
    }

    public function test_checkout_does_not_change_profile_without_consent(): void
    {
        $user = $this->customer(['address' => '12 Lê Lợi']);
        $this->actingAs($user)->post(route('quote.store'), $this->order(['address' => '99 Nguyễn Huệ']));
        $this->assertSame('12 Lê Lợi', $user->fresh()->address);
    }

    public function test_guest_checkout_page_invites_login_and_returns_after(): void
    {
        $this->get(route('quote.create'))->assertOk()->assertSee('Đã có tài khoản?')
            ->assertSee(route('login', ['quay-lai' => '/bao-gia']), false);
    }

    /* ---------- tài khoản / đơn hàng ---------- */

    public function test_account_pages_require_login(): void
    {
        $this->get(route('account.show'))->assertRedirect(route('login'));
        $this->get(route('account.orders'))->assertRedirect(route('login'));
    }

    public function test_customer_sees_only_own_orders(): void
    {
        $me = $this->customer();
        $other = $this->customer(['email' => 'khac@congty.vn']);
        $mine = QuoteRequest::create(['user_id' => $me->id, 'contact_name' => 'A', 'phone' => '0909123456', 'email' => 'an@congty.vn', 'address' => 'x', 'ward' => 'y', 'province' => 'z']);
        $theirs = QuoteRequest::create(['user_id' => $other->id, 'contact_name' => 'B', 'phone' => '0909123457', 'email' => 'khac@congty.vn']);

        $this->actingAs($me);
        $this->get(route('account.orders'))->assertOk()->assertSee($mine->code())->assertDontSee($theirs->code());
        $this->get(route('account.order', $mine))->assertOk()->assertSee('Mới tiếp nhận');
        $this->get(route('account.order', $theirs))->assertNotFound();
    }

    public function test_update_profile_and_change_password(): void
    {
        $user = $this->customer();
        $this->actingAs($user);

        $this->post(route('account.update'), $this->info(['phone' => '0911 222 333']))->assertSessionHasNoErrors();
        $this->assertSame('0911 222 333', $user->fresh()->phone);

        $this->post(route('account.password'), ['current_password' => 'sai', 'password' => 'moi12345', 'password_confirmation' => 'moi12345'])
            ->assertSessionHasErrors('current_password', null, 'password');
        $this->post(route('account.password'), ['current_password' => 'matkhau123', 'password' => 'moi12345', 'password_confirmation' => 'moi12345'])
            ->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('moi12345', $user->fresh()->password));
    }

    public function test_forgot_and_reset_password(): void
    {
        Notification::fake();
        $user = $this->customer();

        // Cùng một thông báo dù email có hay không (không lộ tài khoản).
        $this->post(route('password.email'), ['email' => 'khongco@x.vn'])->assertSessionHas('ok');
        $this->post(route('password.email'), ['email' => 'an@congty.vn'])->assertSessionHas('ok');

        $token = null;
        Notification::assertSentTo($user, ResetPasswordVi::class, function ($n) use (&$token, $user) {
            $token = $n->token;
            $this->assertSame('Đặt lại mật khẩu – '.config('site.brand'), $n->toMail($user)->subject);

            return true;
        });

        $this->post(route('password.update'), ['token' => $token, 'email' => 'an@congty.vn', 'password' => 'moi12345', 'password_confirmation' => 'moi12345'])
            ->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('moi12345', $user->fresh()->password));
    }

    /* ---------- SEO ---------- */

    public function test_account_pages_are_not_indexable(): void
    {
        $this->get(route('login'))->assertSee('<meta name="robots" content="noindex, follow">', false);
        $this->get(route('register'))->assertSee('<meta name="robots" content="noindex, follow">', false);
        $this->actingAs($this->customer())->get(route('account.show'))->assertSee('<meta name="robots" content="noindex, nofollow">', false);

        $this->app['env'] = 'production';
        config(['app.url' => 'https://www.dt-office.example']);
        $this->get('/robots.txt')->assertSee('Disallow: /tai-khoan');
        $this->assertStringNotContainsString('dang-nhap', $this->get('/sitemap.xml')->getContent());
    }
}
