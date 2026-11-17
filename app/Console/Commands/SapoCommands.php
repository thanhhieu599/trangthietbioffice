<?php

namespace App\Console\Commands;

use App\Jobs\PushQuoteToSapo;
use App\Models\QuoteRequest;
use App\Models\SapoVariant;
use App\Services\Sapo\SapoClient;
use Illuminate\Console\Command;

/**
 * Lệnh quản lý đồng bộ Sapo:
 *   php artisan sapo:run check            Kiểm tra khóa API (đọc số khách hàng, sản phẩm)
 *   php artisan sapo:run sync-variants    Ghép SKU web với phiên bản Sapo (chạy tự động mỗi ngày)
 *   php artisan sapo:run retry [--id=]    Gửi lại đơn lỗi / chưa gửi sang Sapo
 */
class SapoCommands extends Command
{
    protected $signature = 'sapo:run {action : check | sync-variants | retry} {--id= : Mã đơn trên web (cho retry)}';

    protected $description = 'Đồng bộ Sapo: kiểm tra khóa, ghép SKU, gửi lại đơn lỗi';

    public function handle(SapoClient $sapo): int
    {
        if (! $sapo->enabled()) {
            $this->error('Chưa cấu hình Sapo: điền SAPO_STORE, SAPO_API_KEY, SAPO_API_SECRET trong .env rồi chạy php artisan config:clear.');

            return self::FAILURE;
        }

        return match ($this->argument('action')) {
            'check' => $this->check($sapo),
            'sync-variants' => $this->syncVariants($sapo),
            'retry' => $this->retry(),
            default => tap(self::INVALID, fn () => $this->error('Hành động không hợp lệ.')),
        };
    }

    private function check(SapoClient $sapo): int
    {
        $this->line('Cửa hàng: '.$sapo->baseUrl());
        try {
            $r = $sapo->ping();
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
        $this->info("Kết nối OK. Khách hàng trên Sapo: {$r['customers']}. Sản phẩm: ".($r['products'] ?? 'không đọc được (thiếu quyền Sản phẩm?)'));
        $this->line('Chế độ tạo đơn: '.config('services.sapo.order_mode'));

        return self::SUCCESS;
    }

    private function syncVariants(SapoClient $sapo): int
    {
        $start = now()->subSecond();
        $seen = [];
        $dupes = [];
        $rows = [];
        $flush = function () use (&$rows) {
            if ($rows) {
                SapoVariant::upsert($rows, ['sku'], ['variant_id', 'product_id', 'title', 'price', 'updated_at']);
                $rows = [];
            }
        };
        $count = $sapo->eachVariant(function (array $v, array $p) use (&$seen, &$dupes, &$rows, $flush) {
            $sku = trim((string) ($v['sku'] ?? ''));
            if ($sku === '') {
                return;
            }
            if (isset($seen[$sku])) {
                $dupes[] = $sku;

                return;
            }
            $seen[$sku] = true;
            $rows[] = [
                'sku' => $sku, 'variant_id' => $v['id'], 'product_id' => $v['product_id'] ?? $p['id'],
                'title' => mb_substr(trim(($p['name'] ?? '').' '.($v['title'] ?? '')), 0, 255), 'price' => $v['price'] ?? null,
                'created_at' => now(), 'updated_at' => now(),
            ];
            if (count($rows) >= 200) {
                $flush();
            }
        });
        $flush();
        // Bỏ SKU không còn trên Sapo (không được cập nhật trong lượt này).
        SapoVariant::where('updated_at', '<', $start)->delete();

        $webSkus = \App\Models\Product::pluck('sku');
        $missing = $webSkus->reject(fn ($s) => isset($seen[$s]))->values();

        $this->info("Đã đọc {$count} phiên bản, ghép được ".count($seen).' SKU.');
        if ($dupes) {
            $this->warn('SKU trùng trên Sapo (dùng bản đầu tiên): '.implode(', ', array_unique($dupes)));
        }
        $this->line("SKU trên web chưa có trên Sapo ({$missing->count()}/{$webSkus->count()}): ".$missing->take(50)->implode(', '));

        return self::SUCCESS;
    }

    private function retry(): int
    {
        $query = QuoteRequest::query()->whereNull('sapo_order_id');
        $query = $this->option('id')
            ? $query->whereKey($this->option('id'))
            : $query->whereIn('sapo_status', ['failed', 'pending']);

        $n = 0;
        foreach ($query->get() as $q) {
            $q->forceFill(['sapo_status' => 'pending', 'sapo_error' => null])->saveQuietly();
            PushQuoteToSapo::dispatch($q);
            $n++;
        }
        $this->info("Đã đưa {$n} đơn vào hàng đợi gửi lại.");

        return self::SUCCESS;
    }
}
