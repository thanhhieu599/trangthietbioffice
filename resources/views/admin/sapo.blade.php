@extends('admin.layout')

@section('title', 'Đồng bộ Sapo')
@section('width', 'max-w-7xl')

@section('content')
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="adm-h1">Đồng bộ Sapo</h1>
        <p class="adm-sub">Khách đăng ký và đơn đặt hàng trên website tự chuyển sang Sapo (chạy ngầm, tự thử lại khi lỗi).</p>
    </div>
    @if ($enabled)
        <div class="flex flex-wrap gap-2">
            <form method="post" action="{{ route('admin.sapo.check') }}">@csrf <button class="adm-btn adm-btn-ghost">Kiểm tra kết nối</button></form>
            <form method="post" action="{{ route('admin.sapo.variants') }}">@csrf <button class="adm-btn adm-btn-dark">Ghép SKU với Sapo ngay</button></form>
        </div>
    @endif
</div>

@if (! $enabled)
    <section class="adm-card mt-6 p-5">
        <h2 class="font-semibold text-adm-ink">Chưa kết nối Sapo</h2>
        <p class="mt-1 text-sm text-adm-muted">Website vẫn nhận đơn bình thường; chỉ chưa gửi sang Sapo. Để bật:</p>
        <ol class="mt-3 list-decimal space-y-1.5 pl-5 text-sm">
            <li>Trong trang quản trị Sapo: <strong>Ứng dụng → Ứng dụng riêng → Tạo ứng dụng riêng</strong>. Cấp quyền: Khách hàng (Đọc và ghi), Đơn hàng (Đọc và ghi), Sản phẩm (Chỉ đọc), và Đơn hàng nháp (Đọc và ghi) nếu dùng đơn nháp.</li>
            <li>Trên máy chủ website, mở file <code>.env</code> và điền:
                <pre class="mt-2 overflow-x-auto rounded-lg bg-slate-950 px-4 py-3 text-xs text-slate-100">SAPO_STORE=ten-cua-hang      # phần trước .mysapo.net
SAPO_API_KEY=...
SAPO_API_SECRET=...
SAPO_ORDER_MODE=order        # hoặc draft_order (đơn nháp)</pre>
            </li>
            <li>Chạy <code>php artisan config:clear</code> (hoặc <code>config:cache</code>), rồi <code>php artisan sapo:run check</code>.</li>
            <li>Bật cron mỗi phút: <code>php artisan schedule:run</code> (chạy hàng đợi gửi Sapo và ghép SKU hằng đêm).</li>
        </ol>
        <p class="mt-3 text-xs text-adm-muted">Không gửi khóa API qua chat / email, không đưa file .env lên git.</p>
    </section>
@else
    @php
        $tiles = [
            ['Đã sang Sapo', $counts['synced'] ?? 0, 'synced', 'text-emerald-600'],
            ['Đang chờ gửi', $counts['pending'] ?? 0, 'pending', 'text-amber-600'],
            ['Lỗi, cần xem', $counts['failed'] ?? 0, 'failed', 'text-primary-600'],
        ];
    @endphp
    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($tiles as [$label, $n, $key, $tone])
            <a href="{{ route('admin.sapo.index', ['status' => $key]) }}" class="adm-card block p-5 hover:shadow-md">
                <p class="text-sm text-adm-muted">{{ $label }}</p>
                <p class="mt-1 text-3xl font-semibold {{ $tone }}">{{ $n }}</p>
            </a>
        @endforeach
        <div class="adm-card p-5">
            <p class="text-sm text-adm-muted">SKU ghép được với Sapo</p>
            <p class="mt-1 text-3xl font-semibold text-adm-ink">{{ $matched }}<span class="text-base font-normal text-adm-muted">/{{ $webSkus }}</span></p>
            <p class="mt-1 text-xs text-adm-muted">{{ $variantsAt ? 'Cập nhật '.\Illuminate\Support\Carbon::parse($variantsAt)->diffForHumans() : 'Chưa ghép lần nào' }}</p>
        </div>
    </div>
    <p class="mt-3 text-xs text-adm-muted">Cửa hàng: {{ $store }} · Tạo trên Sapo dạng: {{ $mode === 'draft_order' ? 'đơn nháp' : 'đơn hàng' }} · Việc trong hàng đợi: {{ $jobsWaiting }}
        @if ($jobsWaiting > 0 && ($counts['pending'] ?? 0) > 0) – nếu số này không giảm, kiểm tra cron <code>schedule:run</code>.@endif</p>
@endif

<div class="mt-6 adm-card overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="adm-thead">
            <tr><th class="px-4 py-3">Đơn web</th><th class="px-4 py-3">Khách</th><th class="px-4 py-3">Ngày</th><th class="px-4 py-3">Sapo</th><th class="px-4 py-3">Ghi chú</th><th class="px-4 py-3"></th></tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($orders as $o)
                <tr class="align-top">
                    <td class="px-4 py-3 font-semibold text-adm-ink">{{ $o->code() }}<span class="block text-xs font-normal text-adm-muted">{{ $o->items_count }} mã</span></td>
                    <td class="px-4 py-3">{{ $o->contact_name }}<span class="block text-xs text-adm-muted">{{ $o->company ?: ($o->user_id ? 'Có tài khoản' : 'Khách vãng lai') }}</span></td>
                    <td class="px-4 py-3 whitespace-nowrap text-adm-muted">{{ $o->created_at->format('d/m H:i') }}</td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        @switch($o->sapo_status)
                            @case('synced')<span class="adm-badge adm-badge-green">Đã gửi #{{ $o->sapo_order_id }}</span>@break
                            @case('pending')<span class="adm-badge adm-badge-amber">Đang chờ</span>@break
                            @case('failed')<span class="adm-badge adm-badge-red">Lỗi</span>@break
                            @default<span class="adm-badge adm-badge-gray">Chưa gửi</span>
                        @endswitch
                    </td>
                    <td class="max-w-md px-4 py-3 text-xs text-adm-muted">{{ \Illuminate\Support\Str::limit($o->sapo_error, 220) }}</td>
                    <td class="px-4 py-3 text-right">
                        @if ($enabled && ! $o->sapo_order_id)
                            <form method="post" action="{{ route('admin.sapo.retry', $o) }}">@csrf <button class="text-xs font-semibold text-primary-600 hover:underline">{{ $o->sapo_status ? 'Gửi lại' : 'Gửi sang Sapo' }}</button></form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-adm-muted">Chưa có đơn nào.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $orders->links() }}</div>
@endsection
