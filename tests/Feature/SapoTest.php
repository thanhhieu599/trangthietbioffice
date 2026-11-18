<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\QuoteRequest;
use App\Models\SapoVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Đồng bộ Sapo (gọi API giả lập bằng Http::fake – không gọi Sapo thật). Hàng đợi trong test chạy đồng bộ. */
class SapoTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = 'https://cua-hang-thu.mysapo.net/admin';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['services.sapo' => [
            'store' => 'cua-hang-thu', 'key' => 'key-thu', 'secret' => 'secret-thu',
            'order_mode' => 'order', 'tags' => 'web-vpp', 'country' => 'Vietnam',
        ]]);
    }

    private function fakeSapo(int $orderStatus = 201): void
    {
        Http::fake([
            self::BASE.'/customers/count.json' => Http::response(['count' => 12]),
            self::BASE.'/products/count.json' => Http::response(['count' => 3]),
            self::BASE.'/customers.json?*' => Http::response(['customers' => []]), // tìm khách: chưa có
            self::BASE.'/customers.json' => Http::response(['customer' => ['id' => 5551]], 201),
            self::BASE.'/customers/*' => Http::response(['customer' => ['id' => 5551]]),
            self::BASE.'/orders.json' => Http::response(['order' => ['id' => 9001]], $orderStatus),
            self::BASE.'/products.json*' => Http::response(['products' => [
                ['id' => 1, 'name' => 'Giấy A4 Double A', 'variants' => [['id' => 77, 'product_id' => 1, 'sku' => 'DA80', 'title' => '80gsm', 'price' => 75000]]],
            ]]),
        ]);
    }

    private function order(array $over = []): array
    {
        return array_merge([
            'contact_name' => 'Nguyễn Văn An', 'email' => 'an@congty.vn', 'phone' => '0909123456',
            'company' => 'Công ty ABC', 'tax_code' => '0312345678',
            'address' => '87 Đường 16', 'ward' => 'Xã Bình Hưng', 'province' => 'TP Hồ Chí Minh',
            'items' => [['sku' => 'DA80', 'quantity' => 5], ['sku' => 'IK70', 'quantity' => 2]], 'consent' => '1',
        ], $over);
    }

    public function test_disabled_when_not_configured_and_site_still_works(): void
    {
        config(['services.sapo.key' => null]);
        Http::fake();
        $this->post(route('quote.store'), $this->order())->assertRedirect(route('quote.thanks'));

        Http::assertNothingSent();
        $this->assertNull(QuoteRequest::first()->sapo_status);
    }

    public function test_write_switch_off_sends_nothing_to_sapo(): void
    {
        config(['services.sapo.sync' => false]);
        Http::fake();

        $this->post(route('quote.store'), $this->order())->assertRedirect(route('quote.thanks'));
        $this->post(route('register.submit'), $this->registerData(['email' => 'khac@x.vn']))->assertRedirect();
        Artisan::call('sapo:run', ['action' => 'retry']);
        $this->withSession(['admin_ok' => true])->post(route('admin.sapo.retry', QuoteRequest::first()))->assertSessionHasErrors('sapo');

        Http::assertNothingSent();
        $this->assertNull(QuoteRequest::first()->sapo_status, 'Đơn chỉ lưu trên web');
    }

    public function test_register_creates_customer_in_sapo_with_basic_auth(): void
    {
        $this->fakeSapo();
        $this->post(route('register.submit'), [
            'name' => 'Nguyễn Văn An', 'email' => 'an@congty.vn', 'phone' => '0909123456', 'company' => 'Công ty ABC',
            'tax_code' => '0312345678', 'address' => '87 Đường 16', 'ward' => 'Xã Bình Hưng', 'province' => 'TP Hồ Chí Minh',
            'password' => 'matkhau123', 'password_confirmation' => 'matkhau123', 'terms' => '1',
        ])->assertRedirect();

        $this->assertSame(5551, (int) User::first()->sapo_customer_id);
        Http::assertSent(function (Request $r) {
            if ($r->method() !== 'POST' || $r->url() !== self::BASE.'/customers.json') {
                return false;
            }
            $c = $r['customer'];
            $this->assertSame('Basic '.base64_encode('key-thu:secret-thu'), $r->header('Authorization')[0]);
            $this->assertSame('An', $c['first_name']);          // Tên
            $this->assertSame('Nguyễn Văn', $c['last_name']);   // Họ + đệm
            $this->assertSame('0909123456', $c['phone']);
            $this->assertStringContainsString('MST: 0312345678', $c['note']);
            $this->assertSame('Xã Bình Hưng', $c['addresses'][0]['address2']);
            $this->assertSame('TP Hồ Chí Minh', $c['addresses'][0]['province']);

            return true;
        });
    }

    public function test_known_customer_is_updated_not_duplicated(): void
    {
        $this->fakeSapo();
        $user = User::create(['name' => 'An', 'email' => 'an@congty.vn', 'password' => 'matkhau123', 'phone' => '0909123456',
            'address' => 'x', 'ward' => 'y', 'province' => 'z']);
        $user->forceFill(['sapo_customer_id' => 5551])->save(); // không cho gán hàng loạt (form không ghi đè được)

        $this->actingAs($user)->post(route('account.update'), ['name' => 'An Mới', 'email' => 'an@congty.vn', 'phone' => '0909123456',
            'address' => 'x', 'ward' => 'y', 'province' => 'z'])->assertSessionHasNoErrors();

        Http::assertSent(fn (Request $r) => $r->method() === 'PUT' && $r->url() === self::BASE.'/customers/5551.json' && $r['customer']['last_name'] === 'An' && $r['customer']['first_name'] === 'Mới');
        Http::assertNotSent(fn (Request $r) => $r->method() === 'POST' && str_ends_with($r->url(), '/customers.json'));
    }

    public function test_order_is_pushed_with_matched_variants_and_custom_lines(): void
    {
        $this->fakeSapo();
        Product::create(['name' => 'Giấy A4 IK Plus', 'slug' => 'ik', 'sku' => 'IK70', 'price' => 68000, 'is_active' => true]);
        SapoVariant::create(['sku' => 'DA80', 'variant_id' => 77, 'product_id' => 1]);

        $this->post(route('quote.store'), $this->order())->assertRedirect(route('quote.thanks'));

        $q = QuoteRequest::first();
        $this->assertSame('synced', $q->sapo_status);
        $this->assertSame(9001, (int) $q->sapo_order_id);
        $this->assertSame(5551, (int) $q->sapo_customer_id);
        $this->assertStringContainsString('IK70', $q->sapo_error); // ghi lại mã chưa ghép

        Http::assertSent(function (Request $r) use ($q) {
            if ($r->url() !== self::BASE.'/orders.json') {
                return false;
            }
            $o = $r['order'];
            $this->assertSame(['id' => 5551], $o['customer']);
            $this->assertSame(['variant_id' => 77, 'quantity' => 5], $o['line_items'][0]);
            $this->assertSame(['title' => 'Giấy A4 IK Plus (IK70)', 'price' => 68000, 'quantity' => 2], $o['line_items'][1]);
            $this->assertSame('87 Đường 16', $o['shipping_address']['address1']);
            $this->assertStringContainsString($q->code(), $o['note']);
            $this->assertFalse($o['send_receipt']);

            return true;
        });
    }

    public function test_draft_order_mode_uses_draft_endpoint(): void
    {
        config(['services.sapo.order_mode' => 'draft_order']);
        $this->fakeSapo();
        Http::fake([self::BASE.'/draft_orders.json' => Http::response(['draft_order' => ['id' => 333]], 201)]);

        $this->post(route('quote.store'), $this->order());
        $this->assertSame(333, (int) QuoteRequest::first()->sapo_order_id);
    }

    public function test_sapo_failure_never_breaks_checkout_and_is_marked_for_retry(): void
    {
        $this->fakeSapo(orderStatus: 422);

        // Đơn vẫn lưu, khách vẫn tới trang cảm ơn dù Sapo từ chối.
        $this->post(route('quote.store'), $this->order())->assertRedirect(route('quote.thanks'));
        $q = QuoteRequest::first();
        $this->assertNotNull($q);
        $this->assertNull($q->sapo_order_id);
        $this->assertContains($q->sapo_status, ['pending', 'failed']);
    }

    public function test_retry_never_creates_a_second_order(): void
    {
        $this->fakeSapo();
        $this->post(route('quote.store'), $this->order());
        Http::assertSentCount(3); // tìm khách theo SĐT + tạo khách + tạo đơn

        Artisan::call('sapo:run', ['action' => 'retry', '--id' => QuoteRequest::first()->id]);
        Http::assertSentCount(3); // đơn đã có sapo_order_id -> không gửi lại
    }

    public function test_sync_variants_and_check_commands(): void
    {
        $this->fakeSapo();
        Product::create(['name' => 'Giấy', 'slug' => 'g', 'sku' => 'DA80', 'is_active' => true]);
        Product::create(['name' => 'Bút', 'slug' => 'b', 'sku' => 'KHONG-CO', 'is_active' => true]);

        $this->assertSame(0, Artisan::call('sapo:run', ['action' => 'sync-variants']));
        $this->assertSame(77, (int) SapoVariant::where('sku', 'DA80')->value('variant_id'));
        $this->assertStringContainsString('KHONG-CO', Artisan::output());

        $this->assertSame(0, Artisan::call('sapo:run', ['action' => 'check']));
        $this->assertStringContainsString('Khách hàng trên Sapo: 12', Artisan::output());
    }

    public function test_admin_sapo_page_never_shows_secrets(): void
    {
        config(['admin.password' => 'pass-123456789']);
        $html = $this->withSession(['admin_ok' => true])->get(route('admin.sapo.index'))->assertOk()->getContent();
        $this->assertStringContainsString('cua-hang-thu.mysapo.net', $html);
        $this->assertStringNotContainsString('secret-thu', $html);
        $this->assertStringNotContainsString('key-thu', $html);
    }

    public function test_existing_sapo_customer_is_reused_instead_of_created(): void
    {
        Http::fake([
            self::BASE.'/customers.json?*' => Http::response(['customers' => [['id' => 4242, 'email' => 'AN@congty.vn', 'phone' => '+84909123456']]]),
            self::BASE.'/customers/4242.json' => Http::response(['customer' => ['id' => 4242]]),
            self::BASE.'/customers.json' => Http::response(['errors' => [['message' => 'has already been taken']]], 422),
            self::BASE.'/orders.json' => Http::response(['order' => ['id' => 9002]], 201),
        ]);

        $this->post(route('quote.store'), $this->order())->assertRedirect(route('quote.thanks'));

        $q = QuoteRequest::first();
        $this->assertSame('synced', $q->sapo_status, (string) $q->sapo_error);
        $this->assertSame(9002, (int) $q->sapo_order_id);
        // Khách đã có (trùng SĐT) -> chỉ liên kết: không tạo mới, không ghi đè dữ liệu khách bên Sapo
        Http::assertNotSent(fn (Request $r) => $r->method() === 'PUT');
        Http::assertNotSent(fn (Request $r) => $r->method() === 'POST' && $r->url() === self::BASE.'/customers.json');
        Http::assertSent(fn (Request $r) => $r->url() === self::BASE.'/orders.json' && (int) $r['order']['customer']['id'] === 4242);
        Http::assertSent(fn (Request $r) => $r->method() === 'GET' && str_contains($r->url(), 'query=0909123456')); // tìm theo SĐT
    }

    public function test_register_links_existing_phone_and_creates_new_phone(): void
    {
        // SĐT đã có trên Sapo -> liên kết, không tạo
        Http::fake([
            self::BASE.'/customers.json?*' => Http::response(['customers' => [['id' => 7001, 'email' => 'khac@x.vn', 'phone' => '0909123456']]]),
            self::BASE.'/customers.json' => Http::response(['customer' => ['id' => 1]], 201),
        ]);
        $this->post(route('register.submit'), $this->registerData(['email' => 'moi@x.vn']))->assertRedirect();
        $this->assertSame(7001, (int) User::where('email', 'moi@x.vn')->value('sapo_customer_id'));
        Http::assertNotSent(fn (Request $r) => $r->method() === 'POST');
    }

    public function test_new_phone_with_taken_email_still_creates_customer_without_email(): void
    {
        $posts = 0;
        Http::fake(function (Request $r) use (&$posts) {
            if ($r->method() === 'GET') {
                return Http::response(['customers' => [['id' => 7001, 'email' => 'an@congty.vn', 'phone' => '0911000000']]]); // khác SĐT
            }
            $posts++;

            return isset($r['customer']['email'])
                ? Http::response(['errors' => [['message' => 'has already been taken', 'code' => 'taken', 'fields' => ['email']]]], 422)
                : Http::response(['customer' => ['id' => 8002]], 201);
        });

        $this->post(route('register.submit'), $this->registerData())->assertRedirect();

        $this->assertSame(8002, (int) User::first()->sapo_customer_id, 'SĐT mới -> tạo khách mới, không gắn vào khách trùng email');
        $this->assertSame(2, $posts);
    }

    public function test_unsupported_province_is_moved_into_address_line_and_retried(): void
    {
        $sent = [];
        Http::fake(function (Request $r) use (&$sent) {
            if ($r->method() === 'GET') {
                return Http::response(['customers' => []]);
            }
            $sent[] = $r['customer'];

            return isset($r['customer']['addresses'][0]['province'])
                ? Http::response(['errors' => [['message' => 'Province is not supported', 'code' => 'unsupported', 'fields' => ['addresses', '0', 'province']]]], 422)
                : Http::response(['customer' => ['id' => 6100]], 201);
        });

        $this->post(route('register.submit'), $this->registerData())->assertRedirect();

        $this->assertSame(6100, (int) User::first()->sapo_customer_id);
        $this->assertCount(2, $sent);
        $this->assertSame('Xã Bình Hưng, TP Hồ Chí Minh', $sent[1]['addresses'][0]['address2']);
        $this->assertArrayNotHasKey('city', $sent[1]['addresses'][0]);
    }

    public function test_profile_update_with_email_taken_retries_without_email(): void
    {
        $bodies = [];
        Http::fake(function (Request $r) use (&$bodies) {
            $bodies[] = $r['customer'];

            return isset($r['customer']['email'])
                ? Http::response(['errors' => [['message' => 'has already been taken', 'code' => 'taken', 'fields' => ['email']]]], 422)
                : Http::response(['customer' => ['id' => 5551]]);
        });
        $user = User::create(['name' => 'An', 'email' => 'an@congty.vn', 'password' => 'matkhau123', 'phone' => '0909123456', 'address' => 'x', 'ward' => 'y', 'province' => 'z']);
        $user->forceFill(['sapo_customer_id' => 5551])->save();

        $this->actingAs($user)->post(route('account.update'), ['name' => 'An', 'email' => 'trung@x.vn', 'phone' => '0909123456', 'address' => 'x', 'ward' => 'y', 'province' => 'z'])
            ->assertSessionHasNoErrors();

        $this->assertCount(2, $bodies);
        $this->assertArrayNotHasKey('email', $bodies[1]);
        $this->assertNotNull($user->fresh()->sapo_synced_at);
    }

    private function registerData(array $over = []): array
    {
        return array_merge([
            'name' => 'Nguyễn Văn An', 'email' => 'an@congty.vn', 'phone' => '0909123456', 'company' => '',
            'tax_code' => '', 'address' => '87 Đường 16', 'ward' => 'Xã Bình Hưng', 'province' => 'TP Hồ Chí Minh',
            'password' => 'matkhau123', 'password_confirmation' => 'matkhau123', 'terms' => '1',
        ], $over);
    }
}
