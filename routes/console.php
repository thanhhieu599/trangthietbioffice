<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
| Lịch chạy tự động. Trên hosting chỉ cần MỘT cron mỗi phút:
|   * * * * * cd /duong-dan-website && php artisan schedule:run >> /dev/null 2>&1
*/

// Hàng đợi (gửi khách hàng / đơn hàng sang Sapo): hosting thường không có Supervisor,
// nên mỗi phút chạy worker xử lý hết việc đang chờ rồi thoát.
Schedule::command('queue:work --stop-when-empty --tries=5 --max-time=50')
    ->everyMinute()
    ->withoutOverlapping(5);

// Ghép SKU web với phiên bản sản phẩm Sapo mỗi đêm (chỉ khi đã cấu hình Sapo).
Schedule::command('sapo:run sync-variants')
    ->dailyAt('02:15')
    ->when(fn () => app(\App\Services\Sapo\SapoClient::class)->enabled())
    ->withoutOverlapping();
