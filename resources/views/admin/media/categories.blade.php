@extends('admin.layout')

@section('title', 'Hình ảnh – Danh mục')
@section('width', 'max-w-6xl')

@php($slots = \App\Http\Controllers\Admin\MediaController::CATEGORY_SLOTS)

@section('content')
@include('admin.media.tabs')

<div class="mt-4 space-y-3">
    @foreach ($rows as ['category' => $c, 'depth' => $depth])
        <section class="adm-card grid items-start gap-5 p-4 md:grid-cols-[220px_minmax(0,1fr)_120px] {{ $c->is_active ? '' : 'opacity-60' }}">
            <div @style(['padding-left: '.($depth * 16).'px'])>
                <p class="font-semibold text-adm-ink">{{ $depth ? '└ ' : '' }}{{ $c->name }}</p>
                <p class="mt-1 flex flex-wrap gap-1 text-xs">
                    @if (! $c->is_active)<span class="adm-badge adm-badge-gray">Đang ẩn</span>@endif
                    @if ($c->show_on_home)<span class="adm-badge adm-badge-blue">Trên trang chủ</span>@endif
                </p>
                <p class="mt-2 flex gap-3 text-xs">
                    <a href="{{ $c->url() }}" target="_blank" class="text-primary-600 hover:underline">Xem trang ↗</a>
                    <a href="{{ route('admin.categories.edit', $c) }}" class="text-slate-500 hover:underline">Sửa danh mục</a>
                </p>
            </div>
            @php([$label, $size, $ratio, , $hint] = $slots['page_banner'])
            @include('admin.media.slot', ['action' => route('admin.media.category', [$c, 'page_banner']), 'url' => $c->page_banner, 'label' => $label, 'size' => $size, 'ratio' => $ratio, 'hint' => $hint])
            @php([$label, $size, $ratio, , $hint] = $slots['banner'])
            @include('admin.media.slot', ['action' => route('admin.media.category', [$c, 'banner']), 'url' => $c->banner, 'label' => $label, 'size' => $size, 'ratio' => $ratio, 'hint' => 'Trang chủ', 'class' => 'max-w-[120px]'])
        </section>
    @endforeach
</div>
@endsection
