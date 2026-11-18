<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\Sapo\SapoClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/** Khách đăng ký / sửa thông tin trên web -> tạo hoặc cập nhật khách hàng bên Sapo (chạy ngầm). */
class SyncCustomerToSapo implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /** Thử lại sau 1 phút, 5 phút, 15 phút, 1 giờ. */
    public array $backoff = [60, 300, 900, 3600];

    public function __construct(public User $user) {}

    public function handle(SapoClient $sapo): void
    {
        if (! $sapo->canWrite()) { // chưa cấu hình hoặc đang tắt gửi (SAPO_SYNC_ENABLED=false)
            return;
        }
        $u = $this->user->fresh();
        if (! $u) {
            return;
        }

        $customer = $sapo->upsertCustomer($u->only(['name', 'email', 'phone', 'company', 'tax_code', 'address', 'ward', 'province']), $u->sapo_customer_id);

        $u->forceFill(['sapo_customer_id' => $customer['id'], 'sapo_synced_at' => now()])->saveQuietly();
    }

    public function failed(\Throwable $e): void
    {
        Log::warning('Đồng bộ khách hàng sang Sapo thất bại', ['user' => $this->user->id, 'error' => $e->getMessage()]);
    }
}
