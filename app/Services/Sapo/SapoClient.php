<?php

namespace App\Services\Sapo;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Gọi Sapo Admin API bằng "Ứng dụng riêng" (Basic Auth: API Key + API Secret).
 *
 * Theo tài liệu chính thức (support.sapo.vn): /ung-dung-rieng-private-apps, /customer, /gioi-thieu-order-api,
 * /phuong-thuc-post-cua-order-phan-2, /phuong-thuc-get-cua-product.
 * CHƯA XÁC MINH được trong tài liệu: endpoint draft_orders, tìm khách theo email (customers/search.json).
 */
class SapoClient
{
    public function enabled(): bool
    {
        return filled(config('services.sapo.store')) && filled(config('services.sapo.key')) && filled(config('services.sapo.secret'));
    }

    public function baseUrl(): string
    {
        $store = trim((string) config('services.sapo.store'));
        $store = preg_replace('#^https?://#', '', rtrim($store, '/'));
        $host = str_contains($store, '.') ? $store : $store.'.mysapo.net';

        return 'https://'.$host.'/admin';
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl())
            ->withBasicAuth((string) config('services.sapo.key'), (string) config('services.sapo.secret'))
            ->acceptJson()
            ->asJson()
            ->timeout(20)
            // Chỉ tự thử lại ngay khi Sapo báo quá giới hạn gọi (429: chắc chắn chưa xử lý). Lỗi mạng / 5xx với
            // lệnh tạo có thể đã tạo xong bên Sapo -> không thử lại tại đây (tránh trùng đơn); hàng đợi xử lý sau.
            ->retry(3, fn (int $attempt) => $attempt * 2000, function ($e) {
                return $e instanceof RequestException && $e->response->status() === 429;
            }, throw: false);
    }

    private function ok(Response $r, string $what): Response
    {
        if ($r->failed()) {
            throw new SapoException($what, $r->status(), $r->body());
        }

        return $r;
    }

    /** Kiểm tra khóa API: đếm khách hàng (cần quyền đọc Khách hàng). */
    public function ping(): array
    {
        $customers = $this->ok($this->http()->get('/customers/count.json'), 'Kiểm tra khóa API')->json('count');
        $products = $this->http()->get('/products/count.json');

        return ['customers' => $customers, 'products' => $products->successful() ? $products->json('count') : null];
    }

    /**
     * Tạo hoặc cập nhật khách hàng. Tài liệu Sapo không có chức năng tìm khách theo email, nên:
     * đã biết mã khách (lưu từ lần trước) -> PUT; chưa biết -> thử tìm (không chính thức) -> POST.
     *
     * @param  array{name:string,email:string,phone:?string,company:?string,tax_code:?string,address:?string,ward:?string,province:?string}  $c
     */
    public function upsertCustomer(array $c, ?int $knownId = null): array
    {
        $payload = ['customer' => SapoPayload::customer($c)];

        if ($knownId) {
            $r = $this->http()->put("/customers/{$knownId}.json", $payload);
            if ($r->status() !== 404) { // 404 = khách đã bị xóa bên Sapo -> tạo lại
                return $this->ok($r, 'Cập nhật khách hàng')->json('customer');
            }
        }

        if ($found = $this->findCustomerByEmail($c['email'])) {
            return $this->ok($this->http()->put("/customers/{$found}.json", $payload), 'Cập nhật khách hàng')->json('customer');
        }

        return $this->ok($this->http()->post('/customers.json', $payload), 'Tạo khách hàng')->json('customer');
    }

    /** Tìm khách theo email qua customers/search.json (KHÔNG có trong tài liệu Sapo: lỗi thì bỏ qua). */
    public function findCustomerByEmail(string $email): ?int
    {
        try {
            $r = $this->http()->get('/customers/search.json', ['query' => 'email:'.$email]);
        } catch (\Throwable) {
            return null;
        }
        if (! $r->successful()) {
            return null;
        }
        foreach ((array) $r->json('customers', []) as $cust) {
            if (strcasecmp((string) ($cust['email'] ?? ''), $email) === 0) {
                return (int) $cust['id'];
            }
        }

        return null;
    }

    /** Tạo đơn hàng (mặc định) hoặc đơn nháp, tùy SAPO_ORDER_MODE. Trả về bản ghi Sapo. */
    public function createOrder(array $order): array
    {
        $mode = config('services.sapo.order_mode') === 'draft_order' ? 'draft_order' : 'order';
        $path = $mode === 'draft_order' ? '/draft_orders.json' : '/orders.json';

        return $this->ok($this->http()->post($path, [$mode => $order]), $mode === 'draft_order' ? 'Tạo đơn nháp' : 'Tạo đơn hàng')->json($mode);
    }

    /** Duyệt toàn bộ sản phẩm (250/trang) để lấy SKU của từng phiên bản. */
    public function eachVariant(callable $fn): int
    {
        $count = 0;
        for ($page = 1; $page <= 400; $page++) {
            $products = $this->ok(
                $this->http()->get('/products.json', ['limit' => 250, 'page' => $page, 'fields' => 'id,name,variants']),
                'Đọc danh sách sản phẩm'
            )->json('products', []);

            foreach ($products as $p) {
                foreach ($p['variants'] ?? [] as $v) {
                    $fn($v, $p);
                    $count++;
                }
            }
            if (count($products) < 250) {
                break;
            }
        }

        return $count;
    }
}
