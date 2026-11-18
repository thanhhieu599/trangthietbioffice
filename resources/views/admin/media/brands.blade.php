@extends('admin.layout')

@section('title', 'Hình ảnh – Thương hiệu')
@section('width', 'max-w-6xl')

@section('content')
@include('admin.media.tabs')

<div class="mt-4 grid gap-4 md:grid-cols-2">
    @forelse ($brands as $b)
        <section class="adm-card p-4">
            <div class="mb-3 flex items-baseline justify-between gap-2">
                <p class="font-semibold text-adm-ink">{{ $b['name'] }}</p>
                <p class="text-xs text-adm-muted">
                    {{ $b['active'] }}/{{ $b['total'] }} sản phẩm đang bán ·
                    <a href="{{ route('brands.show', $b['slug']) }}" target="_blank" class="text-primary-600 hover:underline">Xem trang ↗</a>
                </p>
            </div>
            @include('admin.media.slot', ['action' => route('admin.media.brand', $b['slug']), 'url' => $b['banner'], 'label' => 'Banner', 'size' => '1200 × 300', 'ratio' => '1200/300'])
        </section>
    @empty
        <div class="adm-card p-10 text-center text-sm text-adm-muted md:col-span-2">Chưa có sản phẩm nào có thương hiệu.</div>
    @endforelse
</div>
@endsection
