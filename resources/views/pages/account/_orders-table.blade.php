@php
    $badge = ['new' => 'bg-blue-50 text-blue-700', 'quoted' => 'bg-amber-50 text-amber-700', 'won' => 'bg-green-50 text-green-700', 'lost' => 'bg-neutral-100 text-neutral-600'];
@endphp
@if ($orders->isEmpty())
    <div class="mt-4 rounded-lg border border-dashed border-neutral-300 p-6 text-center text-sm text-neutral-600">
        Anh/chị chưa có đơn hàng nào.
        <a href="{{ route('home') }}" class="font-semibold text-primary-600 hover:underline">Bắt đầu chọn sản phẩm</a>
    </div>
@else
    <div class="mt-4 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="border-b border-neutral-200 text-left text-xs uppercase text-neutral-500">
                <tr><th class="py-2 pr-3">Mã đơn</th><th class="py-2 pr-3">Ngày đặt</th><th class="py-2 pr-3">Sản phẩm</th><th class="py-2 pr-3">Giao đến</th><th class="py-2">Trạng thái</th></tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @foreach ($orders as $o)
                    <tr>
                        <td class="py-3 pr-3"><a href="{{ route('account.order', $o) }}" class="font-semibold text-primary-600 hover:underline">{{ $o->code() }}</a></td>
                        <td class="py-3 pr-3 whitespace-nowrap">{{ $o->created_at->format('d/m/Y H:i') }}</td>
                        <td class="py-3 pr-3">{{ $o->items_count }} mã</td>
                        <td class="max-w-xs truncate py-3 pr-3 text-neutral-600">{{ $o->fullAddress() }}</td>
                        <td class="py-3 whitespace-nowrap"><span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badge[$o->status] ?? 'bg-neutral-100' }}">{{ $o->statusLabel() }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
