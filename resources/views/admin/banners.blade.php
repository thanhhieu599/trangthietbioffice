@extends('admin.layout')

@section('title', 'Banner trang chủ')
@section('width', 'max-w-6xl')

@php
    $positions = \App\Models\Banner::POSITIONS;
    // Lưới ảnh theo tỉ lệ khung của từng vị trí: banner chính rộng -> 2 cột, ảnh nhỏ -> 3–4 cột
    $grid = ['slider' => 'sm:grid-cols-2', 'side' => 'grid-cols-2 sm:grid-cols-3', 'promo' => 'grid-cols-2 sm:grid-cols-4'];
@endphp

@section('content')
@include('admin.media.tabs')

<div class="mt-5 space-y-5">
    @foreach ($positions as $key => $pinfo)
        @php
            $list = $banners[$key] ?? collect();
            $shown = $list->where('is_active', true)->count();
            $full = $pinfo['max'] && $list->count() >= $pinfo['max'];
        @endphp
        <section class="adm-card p-5" id="vi-tri-{{ $key }}">
            <header class="flex flex-wrap items-baseline justify-between gap-2">
                <h2 class="font-semibold text-adm-ink">{{ $pinfo['label'] }}</h2>
                <p class="text-xs text-adm-muted">{{ $pinfo['size'] }} px · đang hiện {{ $shown }}{{ $pinfo['max'] ? '/'.$pinfo['max'] : '' }}</p>
            </header>

            <ul class="mt-4 grid gap-4 {{ $grid[$key] ?? 'sm:grid-cols-3' }}">
                @foreach ($list as $banner)
                    <li x-data="{ edit: false }" class="overflow-hidden rounded-lg border border-adm-line bg-white {{ $banner->is_active ? '' : 'opacity-55' }}">
                        <div class="relative bg-slate-100" @style(['aspect-ratio: '.$pinfo['ratio']])>
                            <img src="{{ $banner->imageUrl() }}" alt="" class="absolute inset-0 h-full w-full object-cover" loading="lazy">
                            @unless ($banner->is_active)<span class="adm-badge adm-badge-gray absolute top-2 left-2">Đang ẩn</span>@endunless
                        </div>

                        <form method="post" action="{{ route('admin.banners.update', $banner) }}" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="position" value="{{ $banner->position }}">
                            {{-- Hàng thao tác: Hiện/Ẩn (lưu ngay) · Sửa · Xóa --}}
                            <div class="flex items-center gap-2 px-3 py-2">
                                <label class="flex cursor-pointer items-center gap-2 text-sm">
                                    <input type="checkbox" name="is_active" value="1" @checked($banner->is_active) class="peer sr-only" onchange="this.form.submit()">
                                    <span class="relative h-5 w-9 rounded-full bg-slate-300 transition peer-checked:bg-emerald-500 after:absolute after:top-0.5 after:left-0.5 after:h-4 after:w-4 after:rounded-full after:bg-white after:transition peer-checked:after:translate-x-4"></span>
                                    <span class="text-slate-600">{{ $banner->is_active ? 'Hiện' : 'Ẩn' }}</span>
                                </label>
                                <span class="min-w-0 flex-1 truncate text-xs text-adm-muted" title="{{ $banner->link }}">{{ $banner->link }}</span>
                                <button type="button" @click="edit = !edit" class="rounded px-2 py-1 text-sm text-slate-600 hover:bg-slate-100" :class="edit && 'bg-slate-100'">Sửa</button>
                                <button type="submit" form="xoa-{{ $banner->id }}" class="rounded px-2 py-1 text-sm text-primary-600 hover:bg-primary-50">Xóa</button>
                            </div>

                            <div x-show="edit" x-cloak class="grid gap-3 border-t border-adm-line bg-slate-50 p-3 sm:grid-cols-[1fr_6rem]">
                                <label class="block sm:col-span-2">
                                    <span class="mb-1 block text-xs font-medium text-slate-600">Link khi bấm ảnh</span>
                                    <input type="text" name="link" value="{{ $banner->link }}" class="adm-input" placeholder="/danh-muc/giay-in-giay-photo">
                                </label>
                                <label class="block">
                                    <span class="mb-1 block text-xs font-medium text-slate-600">Mô tả ảnh</span>
                                    <input type="text" name="title" value="{{ $banner->title }}" class="adm-input">
                                </label>
                                <label class="block">
                                    <span class="mb-1 block text-xs font-medium text-slate-600">Thứ tự</span>
                                    <input type="number" name="sort_order" min="0" max="999" value="{{ $banner->sort_order }}" class="adm-input">
                                </label>
                                <label class="block sm:col-span-2">
                                    <span class="mb-1 block text-xs font-medium text-slate-600">Thay ảnh</span>
                                    <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="adm-file">
                                </label>
                                @if ($key === 'slider')
                                    <label class="block sm:col-span-2">
                                        <span class="mb-1 block text-xs font-medium text-slate-600">Ảnh cho điện thoại (800 × 450){{ $banner->image_mobile ? ' · đã có' : '' }}</span>
                                        <input type="file" name="image_mobile" accept="image/jpeg,image/png,image/webp" class="adm-file">
                                    </label>
                                @endif
                                <div class="sm:col-span-2"><button class="adm-btn adm-btn-dark py-1.5">Lưu</button></div>
                            </div>
                        </form>
                        <form id="xoa-{{ $banner->id }}" method="post" action="{{ route('admin.banners.destroy', $banner) }}" onsubmit="return confirm('Xóa ảnh này?')">@csrf</form>
                    </li>
                @endforeach

                {{-- Ô thêm ảnh: chọn file là tải lên ngay --}}
                @unless ($full)
                    <li>
                        <form method="post" action="{{ route('admin.banners.store') }}" enctype="multipart/form-data" class="h-full">
                            @csrf
                            <input type="hidden" name="position" value="{{ $key }}">
                            <label class="flex h-full min-h-28 cursor-pointer flex-col items-center justify-center gap-1 rounded-lg border-2 border-dashed border-slate-300 bg-slate-50/60 text-sm text-slate-500 transition hover:border-primary-500 hover:text-primary-600"
                                   @style(['aspect-ratio: '.$pinfo['ratio']])>
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                                Thêm ảnh
                                <input type="file" name="image" accept="image/jpeg,image/png,image/webp" required class="sr-only" onchange="this.form.submit()">
                            </label>
                        </form>
                    </li>
                @endunless
            </ul>

            @if ($key === 'slider' && $list->isEmpty() && $folderBanners->isNotEmpty())
                <p class="mt-3 text-xs text-adm-muted">Trang chủ đang dùng {{ $folderBanners->count() }} ảnh demo cho tới khi bạn thêm ảnh.</p>
            @endif
        </section>
    @endforeach
</div>
@endsection
