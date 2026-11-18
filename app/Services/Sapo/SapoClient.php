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
 * CHƯA XÁC MINH được trong tài liệu: endpoint draft_orders. Tìm khách: GET /customers.json?query= (đã thử trên cửa hàng thật).
 */
class SapoClient
{
    public function enabled(): bool
    {
        return filled(config('services.sapo.store')) && filled(config('services.sapo.key')) && filled(config('services.sapo.secret'));
    }

    /** Được phép GỬI dữ liệu lên Sapo (SAPO_SYNC_ENABLED, mặc định bật). Đọc dữ liệu không phụ thuộc công tắc này. */
    public function canWrite(): bool
    {
        return $this->enabled() && (bool) config('services.sapo.sync', true);
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
            $r = $this->sendTolerant('put', "/customers/{$knownId}.json", $payload);
            if ($r->status() !== 404) { // 404 = khách đã bị xóa bên Sapo -> tạo lại
                return $this->ok($r, 'Cập nhật khách hàng')->json('customer');
            }
        }

        // Nhận biết bằng SỐ ĐIỆN THOẠI (rồi mới tới email): khách đã có trên Sapo -> chỉ liên kết,
        // KHÔNG tạo mới và KHÔNG ghi đè dữ liệu khách mà cửa hàng đã có bên Sapo.
        if ($found = $this->findCustomer($c['email'] ?? null, $c['phone'] ?? null)) {
            return ['id' => $found, 'linked' => true];
        }

        return $this->ok($this->sendTolerant('post', '/customers.json', $payload), 'Tạo khách hàng')->json('customer');
    }

    /**
     * Gửi lên Sapo; nếu Sapo từ chối (422) vì 1 trường cụ thể thì bỏ / chuyển trường đó rồi gửi lại (tối đa 3 lần):
     *  - "Province is not supported": Sapo chưa nhận tên tỉnh/thành mới (34 tỉnh sau 2025) -> ghi tỉnh vào dòng địa chỉ 2.
     *  - email "has already been taken": email thuộc khách khác -> gửi không kèm email (khách nhận biết bằng SĐT).
     *  - "invalid phone format" ở địa chỉ -> bỏ SĐT trong địa chỉ (SĐT chính của khách vẫn giữ).
     * Không áp dụng cho lỗi khác (vẫn báo lỗi để thử lại / xem trong trang Sapo).
     */
    private function sendTolerant(string $method, string $path, array $payload): Response
    {
        // Chốt chặn cuối: mọi lệnh ghi (tạo/cập nhật khách, tạo đơn) đều đi qua đây
        if (! $this->canWrite()) {
            throw new SapoException('Đang tắt gửi dữ liệu lên Sapo (SAPO_SYNC_ENABLED=false)', 0, '');
        }

        for ($i = 0; ; $i++) {
            $r = $this->http()->{$method}($path, $payload);
            $body = mb_strtolower($r->body());
            if ($r->status() !== 422 || $i >= 3) {
                return $r;
            }
            $before = $payload;
            if (str_contains($body, 'province') && (str_contains($body, 'not supported') || str_contains($body, 'unsupported'))) {
                $payload = self::provinceIntoAddressLine($payload);
            }
            if (str_contains($body, 'taken') && str_contains($body, '"email"')) {
                array_walk($payload, function (&$root) {
                    if (is_array($root)) {
                        unset($root['email']);
                    }
                });
            }
            if (str_contains($body, 'invalid phone') && str_contains($body, 'addresses')) {
                foreach ($payload as &$root) {
                    foreach ($root['addresses'] ?? [] as $k => $a) {
                        unset($root['addresses'][$k]['phone']);
                    }
                }
                unset($root);
            }
            if ($payload === $before) {
                return $r; // không biết sửa trường nào -> trả lỗi gốc
            }
        }
    }

    /** Mọi địa chỉ trong payload: chuyển tỉnh/thành sang address2 ("Phường X, TP Hồ Chí Minh"), bỏ province/city. */
    private static function provinceIntoAddressLine(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::provinceIntoAddressLine($value);
            }
        }
        if (array_key_exists('province', $data) && ! is_array($data['province'])) {
            $data['address2'] = collect([$data['address2'] ?? null, $data['province']])->filter()->unique()->implode(', ');
            unset($data['province'], $data['city'], $data['province_code']);
        }

        return $data;
    }

    /**
     * Tìm khách trên Sapo theo SỐ ĐIỆN THOẠI trước, không thấy mới tìm theo email: GET /customers.json?query=...
     * (đã kiểm tra trên cửa hàng thật; /customers/search.json bị Sapo chặn 403). Kết quả được so khớp lại cho chắc.
     */
    public function findCustomer(?string $email, ?string $phone = null): ?int
    {
        $digits = $phone ? preg_replace('/\D/', '', $phone) : '';
        $tail = strlen($digits) >= 9 ? substr($digits, -9) : null; // 9 số cuối: khớp cả 0909… và +84909…

        // Có SĐT: chỉ nhận biết theo SĐT (SĐT khác = khách khác, dù trùng email). Không có SĐT mới dùng email.
        $term = $tail ? $digits : trim((string) $email);
        if ($term !== '') {
            try {
                $r = $this->http()->get('/customers.json', ['query' => $term, 'limit' => 50]);
            } catch (\Throwable) {
                return null;
            }
            if (! $r->successful()) {
                return null;
            }
            foreach ((array) $r->json('customers', []) as $cust) {
                $match = $tail
                    ? str_ends_with(preg_replace('/\D/', '', (string) ($cust['phone'] ?? '')), $tail)
                    : strcasecmp(trim((string) ($cust['email'] ?? '')), $term) === 0;
                if ($match) {
                    return (int) $cust['id'];
                }
            }
        }

        return null;
    }

    /** Tạo đơn hàng (mặc định) hoặc đơn nháp, tùy SAPO_ORDER_MODE. Trả về bản ghi Sapo. */
    public function createOrder(array $order): array
    {
        $mode = config('services.sapo.order_mode') === 'draft_order' ? 'draft_order' : 'order';
        $path = $mode === 'draft_order' ? '/draft_orders.json' : '/orders.json';

        return $this->ok($this->sendTolerant('post', $path, [$mode => $order]), $mode === 'draft_order' ? 'Tạo đơn nháp' : 'Tạo đơn hàng')->json($mode);
    }

    /** Danh mục (custom collection) trên Sapo Web: [['id','title','alias','products_count'], ...]. */
    public function collections(): array
    {
        $all = [];
        for ($page = 1; $page <= 40; $page++) {
            $list = $this->ok($this->http()->get('/custom_collections.json', ['limit' => 250, 'page' => $page]), 'Đọc danh mục')
                ->json('custom_collections', []);
            $all = array_merge($all, $list);
            if (count($list) < 250) {
                break;
            }
        }

        return $all;
    }

    /** Duyệt sản phẩm (đủ thông tin: ảnh, phiên bản, nội dung) của một danh mục Sapo. */
    public function eachProductInCollection(int $collectionId, callable $fn): int
    {
        $count = 0;
        for ($page = 1; $page <= 100; $page++) {
            $products = $this->ok(
                $this->http()->get('/products.json', ['collection_id' => $collectionId, 'limit' => 250, 'page' => $page]),
                'Đọc sản phẩm của danh mục'
            )->json('products', []);
            foreach ($products as $p) {
                $fn($p);
                $count++;
            }
            if (count($products) < 250) {
                break;
            }
        }

        return $count;
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
