<?php

namespace App\Services\Sapo;

use App\Jobs\PushQuoteToSapo;
use App\Jobs\SyncCustomerToSapo;
use App\Models\QuoteRequest;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Điểm gọi duy nhất từ controller. Chưa cấu hình Sapo -> không làm gì.
 * Job chạy sau khi phản hồi đã gửi cho khách (hàng đợi), nên Sapo chậm/lỗi không ảnh hưởng khách.
 */
class SapoSync
{
    public static function customer(User $user): void
    {
        if (app(SapoClient::class)->canWrite()) { // tắt gửi (SAPO_SYNC_ENABLED=false) -> không đưa việc vào hàng đợi
            self::safely(fn () => SyncCustomerToSapo::dispatch($user)->afterCommit(), ['user' => $user->id]);
        }
    }

    public static function quote(QuoteRequest $quote): void
    {
        if (app(SapoClient::class)->canWrite()) { // tắt gửi (SAPO_SYNC_ENABLED=false) -> không đưa việc vào hàng đợi
            $quote->forceFill(['sapo_status' => 'pending', 'sapo_error' => null])->saveQuietly();
            self::safely(fn () => PushQuoteToSapo::dispatch($quote)->afterCommit(), ['quote' => $quote->id], $quote);
        }
    }

    /**
     * Khi hàng đợi chạy đồng bộ (QUEUE_CONNECTION=sync) job chạy ngay trong request: lỗi Sapo không được
     * làm hỏng việc đăng ký / đặt hàng của khách -> ghi log, đánh dấu lỗi để gửi lại sau.
     */
    private static function safely(\Closure $dispatch, array $context, ?QuoteRequest $quote = null): void
    {
        try {
            $dispatch();
        } catch (\Throwable $e) {
            Log::warning('Đồng bộ Sapo thất bại', $context + ['error' => $e->getMessage()]);
            $quote?->forceFill(['sapo_status' => 'failed', 'sapo_error' => mb_substr($e->getMessage(), 0, 2000)])->saveQuietly();
        }
    }
}
