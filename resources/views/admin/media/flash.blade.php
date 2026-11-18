@extends('admin.layout')

@section('title', 'Hình ảnh – Flash Sale')
@section('width', 'max-w-6xl')

@php($slots = \App\Http\Controllers\Admin\MediaController::FLASH_SLOTS)

@section('content')
@include('admin.media.tabs')

<div class="mt-4 space-y-3">
    @forelse ($sales as $sale)
        <section class="adm-card grid items-start gap-5 p-4 md:grid-cols-[220px_minmax(0,1fr)_200px]">
            <div>
                <p class="font-semibold text-adm-ink">{{ $sale->name }}</p>
                <p class="mt-1 text-xs text-adm-muted">{{ $sale->starts_at->format('d/m H:i') }} – {{ $sale->ends_at->format('d/m H:i') }}</p>
                <span class="adm-badge mt-2 {{ $sale->status() === 'running' ? 'adm-badge-green' : 'adm-badge-gray' }}">{{ $sale->statusLabel() }}</span>
                <p class="mt-2 text-xs"><a href="{{ route('admin.flash-sales.edit', $sale) }}" class="text-slate-500 hover:underline">Sửa chương trình</a></p>
            </div>
            @foreach ($slots as $field => [$label, $size, $ratio])
                @include('admin.media.slot', ['action' => route('admin.media.flash.update', [$sale, $field]), 'url' => $sale->{$field} ? $sale->bannerUrl($sale->{$field}) : null, 'label' => $label, 'size' => $size, 'ratio' => $ratio])
            @endforeach
        </section>
    @empty
        <div class="adm-card p-10 text-center text-sm text-adm-muted">
            Chưa có chương trình Flash Sale. <a href="{{ route('admin.flash-sales.create') }}" class="font-semibold text-primary-600 hover:underline">Tạo chương trình</a>
        </div>
    @endforelse
</div>
@endsection
