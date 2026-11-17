@extends('admin.layout')

@section('title', 'Flash Sale')
@section('width', 'max-w-6xl')

@section('content')
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="adm-h1">Flash Sale</h1>
        <p class="adm-sub">Chương trình đang diễn ra hiện ở trang chủ (giữa lưới danh mục và các khối sản phẩm) và trang <a href="{{ route('flash-sale') }}" target="_blank" class="text-primary-600 hover:underline">/flash-sale</a>.</p>
    </div>
    <a href="{{ route('admin.flash-sales.create') }}" class="adm-btn adm-btn-primary">+ Tạo chương trình</a>
</div>

<div class="mt-6 adm-card overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="adm-thead">
            <tr><th class="px-4 py-3">Chương trình</th><th class="px-4 py-3">Thời gian</th><th class="px-4 py-3 text-right">Sản phẩm</th><th class="px-4 py-3">Trạng thái</th></tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($sales as $s)
                @php($badge = ['running' => 'adm-badge-green', 'upcoming' => 'adm-badge-blue', 'ended' => 'adm-badge-gray', 'off' => 'adm-badge-gray'][$s->status()])
                <tr class="hover:bg-slate-50/80">
                    <td class="px-4 py-3"><a href="{{ route('admin.flash-sales.edit', $s) }}" class="font-semibold text-adm-ink hover:text-primary-600">{{ $s->name }}</a></td>
                    <td class="px-4 py-3 whitespace-nowrap text-adm-muted">{{ $s->starts_at->format('H:i d/m/Y') }} → {{ $s->ends_at->format('H:i d/m/Y') }}</td>
                    <td class="px-4 py-3 text-right">{{ $s->items_count }}</td>
                    <td class="px-4 py-3 whitespace-nowrap"><span class="adm-badge {{ $badge }}">{{ $s->statusLabel() }}</span>
                        @if ($s->auto_renew)<span class="adm-badge adm-badge-blue ml-1">Tự lặp {{ round($s->cycleSeconds() / 3600, 1) + 0 }}h</span>@endif</td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-4 py-10 text-center text-adm-muted">Chưa có chương trình nào. Bấm “Tạo chương trình” để bắt đầu.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
