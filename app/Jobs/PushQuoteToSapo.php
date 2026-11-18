<?php

namespace App\Jobs;

use App\Models\QuoteRequest;
use App\Services\Sapo\SapoClient;
use App\Services\Sapo\SapoPayload;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Đơn đặt hàng / yêu cầu báo giá trên web -> khách hàng + đơn hàng bên Sapo (chạy ngầm, tự thử lại).
 * Đơn đã có sapo_order_id thì không tạo lại (chống trùng khi thử lại).
 */
class PushQuoteToSapo implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public array $backoff = [60, 300, 900, 3600];

    public function __construct(public QuoteRequest $quote) {}

    public function handle(SapoClient $sapo): void
    {
        $q = $this->quote->fresh(['items.product', 'user']);
        if (! $q || ! $sapo->canWrite()) { // đang tắt gửi (SAPO_SYNC_ENABLED=false): để nguyên, không gửi
            return;
        }
        if ($q->sapo_order_id) {
            $q->forceFill(['sapo_status' => 'synced', 'sapo_error' => null])->saveQuietly();

            return;
        }

        // 1. Khách hàng: dùng mã đã biết (tài khoản / lần thử trước), không có thì tạo hoặc cập nhật theo thông tin đơn.
        $customerId = $q->sapo_customer_id ?: $q->user?->sapo_customer_id;
        if (! $customerId) {
            $customer = $sapo->upsertCustomer(SapoPayload::customerFromQuote($q));
            $customerId = (int) $customer['id'];
            $q->forceFill(['sapo_customer_id' => $customerId])->saveQuietly();
            if ($q->user && ! $q->user->sapo_customer_id && strcasecmp($q->user->email, $q->email) === 0) {
                $q->user->forceFill(['sapo_customer_id' => $customerId, 'sapo_synced_at' => now()])->saveQuietly();
            }
        }

        // 2. Đơn hàng.
        $payload = SapoPayload::order($q, $customerId);
        $order = $sapo->createOrder($payload['order']);

        $q->forceFill([
            'sapo_customer_id' => $customerId,
            'sapo_order_id' => $order['id'],
            'sapo_status' => 'synced',
            'sapo_error' => $payload['unmatched'] ? 'Đã đồng bộ. Mã chưa ghép với Sapo: '.implode(', ', $payload['unmatched']) : null,
            'sapo_synced_at' => now(),
        ])->saveQuietly();
    }

    /** Hết số lần thử: đánh dấu lỗi để xem và gửi lại trong trang quản trị. */
    public function failed(\Throwable $e): void
    {
        $this->quote->forceFill(['sapo_status' => 'failed', 'sapo_error' => mb_substr($e->getMessage(), 0, 2000)])->saveQuietly();
    }
}
