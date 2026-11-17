@extends('pages.account._layout')

@section('title', 'Đơn '.$order->code().' | '.config('site.brand'))

@section('account')
<section class="rounded-md bg-white p-6">
    <a href="{{ route('account.orders') }}" class="text-sm text-neutral-500 hover:text-primary-600">← Đơn hàng của tôi</a>
    <div class="mt-2 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl font-bold text-ink-900">Đơn {{ $order->code() }}</h1>
        <span class="rounded-full bg-neutral-100 px-3 py-1 text-sm font-medium">{{ $order->statusLabel() }}</span>
    </div>
    <p class="mt-1 text-sm text-neutral-500">Đặt lúc {{ $order->created_at->format('H:i d/m/Y') }}</p>

    <div class="mt-6 grid gap-6 md:grid-cols-2">
        <div>
            <h2 class="font-semibold text-ink-900">Người nhận</h2>
            <dl class="mt-2 space-y-1 text-sm">
                <div><dt class="inline text-neutral-500">Họ tên:</dt> <dd class="inline">{{ $order->contact_name }}</dd></div>
                <div><dt class="inline text-neutral-500">Điện thoại:</dt> <dd class="inline">{{ $order->phone }}</dd></div>
                <div><dt class="inline text-neutral-500">Email:</dt> <dd class="inline">{{ $order->email }}</dd></div>
                @if ($order->company)<div><dt class="inline text-neutral-500">Công ty:</dt> <dd class="inline">{{ $order->company }}@if ($order->tax_code) – MST {{ $order->tax_code }}@endif</dd></div>@endif
            </dl>
        </div>
        <div>
            <h2 class="font-semibold text-ink-900">Giao đến</h2>
            <p class="mt-2 text-sm">{{ $order->fullAddress() }}</p>
            @if ($order->note)
                <h2 class="mt-4 font-semibold text-ink-900">Ghi chú</h2>
                <p class="mt-1 text-sm whitespace-pre-line">{{ $order->note }}</p>
            @endif
        </div>
    </div>

    <h2 class="mt-8 font-semibold text-ink-900">Sản phẩm ({{ $order->items->count() }})</h2>
    @if ($order->items->isEmpty())
        <p class="mt-2 text-sm text-neutral-500">Đơn gửi bằng danh sách trong ô ghi chú.</p>
    @else
        <table class="mt-2 w-full text-sm">
            <thead class="border-b border-neutral-200 text-left text-xs uppercase text-neutral-500">
                <tr><th class="py-2 pr-3">Sản phẩm</th><th class="py-2 pr-3">Mã</th><th class="py-2 text-right">Số lượng</th></tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @foreach ($order->items as $item)
                    <tr>
                        <td class="py-2 pr-3">
                            @if ($item->product && $item->product->is_active)
                                <a href="{{ $item->product->url() }}" class="text-ink-900 hover:text-primary-600">{{ $item->name }}</a>
                            @else
                                {{ $item->name }}
                            @endif
                        </td>
                        <td class="py-2 pr-3 text-neutral-500">{{ $item->sku }}</td>
                        <td class="py-2 text-right">{{ number_format($item->quantity, 0, ',', '.') }}{{ $item->product?->unit ? ' '.$item->product->unit : '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</section>
@endsection
