@extends('admin.layout')

@section('title', 'Banner trang chủ')
@section('width', 'max-w-6xl')

@php
    $input = 'adm-input';
    $positions = \App\Models\Banner::POSITIONS;
    $count = fn ($p) => ($banners[$p] ?? collect())->where('is_active', true)->count();
    $startPos = old('position', request('vi-tri', 'slider'));
@endphp

@section('content')
<h1 class="adm-h1">Banner trang chủ</h1>
<p class="adm-sub">Khu ảnh đầu trang chủ gồm 3 vị trí. Bấm vào một ô trong sơ đồ để tải ảnh cho vị trí đó.</p>

<div x-data="{ pos: @js($startPos), positions: @js($positions), preview: null }" class="mt-5 grid gap-6 lg:grid-cols-[1fr_1.1fr]">

    {{-- ===== Sơ đồ khu banner (giống bố cục trên trang chủ) ===== --}}
    <section class="adm-card p-5">
        <h2 class="font-semibold text-adm-ink">Sơ đồ khu banner trang chủ</h2>
        <div class="mt-4 space-y-2">
            <div class="grid grid-cols-[2fr_1fr] gap-2">
                <button type="button" @click="pos = 'slider'; preview = null" class="flex aspect-[1200/460] flex-col items-center justify-center rounded-lg border-2 text-center transition"
                        :class="pos === 'slider' ? 'border-primary-600 bg-primary-50 text-primary-700' : 'border-dashed border-slate-300 bg-slate-50 text-slate-500 hover:border-slate-400'">
                    <span class="text-sm font-semibold">Banner chính</span>
                    <span class="text-xs">{{ $positions['slider']['size'] }} · {{ $count('slider') }} ảnh</span>
                </button>
                <div class="grid grid-rows-2 gap-2">
                    @foreach ([1, 2] as $i)
                        <button type="button" @click="pos = 'side'; preview = null" class="flex flex-col items-center justify-center rounded-lg border-2 text-center transition"
                                :class="pos === 'side' ? 'border-primary-600 bg-primary-50 text-primary-700' : 'border-dashed border-slate-300 bg-slate-50 text-slate-500 hover:border-slate-400'">
                            <span class="text-xs font-semibold">Bên phải {{ $i }}</span>
                            @if ($i === 1)<span class="text-[11px]">{{ $positions['side']['size'] }}</span>@endif
                        </button>
                    @endforeach
                </div>
            </div>
            <div class="grid grid-cols-3 gap-2">
                @foreach ([1, 2, 3] as $i)
                    <button type="button" @click="pos = 'promo'; preview = null" class="flex aspect-[600/400] flex-col items-center justify-center rounded-lg border-2 text-center transition"
                            :class="pos === 'promo' ? 'border-primary-600 bg-primary-50 text-primary-700' : 'border-dashed border-slate-300 bg-slate-50 text-slate-500 hover:border-slate-400'">
                        <span class="text-xs font-semibold">Dưới {{ $i }}</span>
                        @if ($i === 1)<span class="text-[11px]">{{ $positions['promo']['size'] }}</span>@endif
                    </button>
                @endforeach
            </div>
        </div>
        <ul class="mt-4 space-y-1 text-xs text-adm-muted">
            <li>Đang hiện: banner chính {{ $count('slider') }} ảnh · bên phải {{ min(2, $count('side')) }}/2 · hàng dưới {{ min(4, $count('promo')) }} ảnh (3 ảnh = 3 cột đều, tối đa 4).</li>
            <li>Vị trí nào chưa có ảnh sẽ tự ẩn; banner chính rộng ra hết khung khi chưa có banner bên phải.</li>
            <li>JPG / PNG / WebP, tối đa 2 MB (nén tại tinypng.com nếu nặng hơn). Đặt chữ và sản phẩm chính ở giữa ảnh.</li>
        </ul>
    </section>

    {{-- ===== Thêm banner ===== --}}
    <section class="adm-card p-5">
        <h2 class="font-semibold text-adm-ink">Thêm ảnh vào: <span class="text-primary-600" x-text="positions[pos].label"></span></h2>
        <p class="mt-1 text-sm text-adm-muted"><span x-text="positions[pos].hint"></span> Kích thước khuyến nghị <strong x-text="positions[pos].size + ' px'"></strong>.</p>

        <form method="post" action="{{ route('admin.banners.store') }}" enctype="multipart/form-data" class="mt-4 grid gap-4 sm:grid-cols-2">
            @csrf
            <input type="hidden" name="position" :value="pos">
            <label class="block sm:col-span-2">
                <span class="mb-1 block text-sm font-medium">Ảnh banner *</span>
                <input type="file" name="image" accept="image/jpeg,image/png,image/webp" required
                       @change="preview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null" class="adm-file">
            </label>
            <template x-if="preview">
                <div class="sm:col-span-2">
                    <p class="mb-1 text-xs text-slate-500">Xem trước (đúng tỉ lệ khung trên trang chủ – phần bị cắt sẽ không hiện):</p>
                    <div class="overflow-hidden rounded-md bg-slate-100" :style="`aspect-ratio: ${positions[pos].ratio}`">
                        <img :src="preview" alt="" class="h-full w-full object-cover">
                    </div>
                </div>
            </template>
            <label class="block sm:col-span-2">
                <span class="mb-1 block text-sm font-medium">Tiêu đề (mô tả ảnh, tốt cho SEO)</span>
                <input type="text" name="title" value="{{ old('title') }}" placeholder="VD: Ưu đãi giấy in tháng 11 – giảm đến 25%" class="{{ $input }}">
            </label>
            <label class="block">
                <span class="mb-1 block text-sm font-medium">Link khi bấm vào ảnh</span>
                <input type="text" name="link" value="{{ old('link') }}" placeholder="/danh-muc/giay-in-giay-photo" class="{{ $input }}">
            </label>
            <label class="block">
                <span class="mb-1 block text-sm font-medium">Thứ tự (số nhỏ hiện trước)</span>
                <input type="number" name="sort_order" min="0" max="999" value="{{ old('sort_order') }}" placeholder="Để trống = cuối" class="{{ $input }}">
            </label>
            <label class="block sm:col-span-2" x-show="pos === 'slider'">
                <span class="mb-1 block text-sm font-medium">Ảnh riêng cho điện thoại (tùy chọn, 800 × 450 px)</span>
                <input type="file" name="image_mobile" accept="image/jpeg,image/png,image/webp" class="adm-file">
            </label>
            <div class="sm:col-span-2">
                <button class="adm-btn adm-btn-primary">Tải lên</button>
            </div>
        </form>
    </section>
</div>

{{-- ===== Danh sách theo vị trí ===== --}}
@foreach ($positions as $key => $pinfo)
    @php($list = $banners[$key] ?? collect())
    <section class="mt-8" id="vi-tri-{{ $key }}">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h2 class="text-lg font-semibold text-adm-ink">{{ $pinfo['label'] }} <span class="text-sm font-normal text-adm-muted">({{ $list->count() }} ảnh{{ $pinfo['max'] ? ' · trang chủ hiện '.$pinfo['max'].' ảnh đầu đang bật' : '' }})</span></h2>
            <span class="text-xs text-adm-muted">Khuyến nghị {{ $pinfo['size'] }} px</span>
        </div>

        @if ($list->isEmpty())
            <div class="mt-3 rounded-xl border border-dashed border-slate-300 bg-white p-5 text-sm text-adm-muted">
                Chưa có ảnh.
                @if ($key === 'slider')
                    Trang chủ đang dùng <strong>{{ $folderBanners->count() }} ảnh</strong> trong thư mục <code>public/images/banners</code>@if ($folderBanners->isNotEmpty()) (ảnh demo)@endif.
                @else
                    Vị trí này đang ẩn trên trang chủ.
                @endif
            </div>
        @else
            <ul class="mt-3 grid gap-3 {{ $key === 'slider' ? '' : 'md:grid-cols-2' }}">
                @foreach ($list as $banner)
                    <li class="adm-card grid gap-4 p-4 {{ $key === 'slider' ? 'md:grid-cols-[300px_1fr]' : 'sm:grid-cols-[200px_1fr]' }} {{ $banner->is_active ? '' : 'opacity-60' }}">
                        <div>
                            <div class="overflow-hidden rounded bg-slate-100" @style(['aspect-ratio: '.$pinfo['ratio']])>
                                <img src="{{ $banner->imageUrl() }}" alt="" class="h-full w-full object-cover" loading="lazy">
                            </div>
                            @if ($banner->image_mobile)<p class="mt-1 text-xs text-slate-500">✓ Có ảnh riêng cho điện thoại</p>@endif
                        </div>
                        <div>
                            <form method="post" action="{{ route('admin.banners.update', $banner) }}" enctype="multipart/form-data" class="grid gap-3 sm:grid-cols-2">
                                @csrf
                                <label class="block sm:col-span-2">
                                    <span class="mb-1 block text-xs font-medium text-slate-600">Tiêu đề</span>
                                    <input type="text" name="title" value="{{ $banner->title }}" class="{{ $input }}">
                                </label>
                                <label class="block sm:col-span-2">
                                    <span class="mb-1 block text-xs font-medium text-slate-600">Link</span>
                                    <input type="text" name="link" value="{{ $banner->link }}" class="{{ $input }}">
                                </label>
                                <label class="block">
                                    <span class="mb-1 block text-xs font-medium text-slate-600">Vị trí</span>
                                    <select name="position" class="{{ $input }}">
                                        @foreach ($positions as $pk => $pv)
                                            <option value="{{ $pk }}" @selected($banner->position === $pk)>{{ $pv['label'] }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label class="block">
                                    <span class="mb-1 block text-xs font-medium text-slate-600">Thứ tự</span>
                                    <input type="number" name="sort_order" min="0" max="999" value="{{ $banner->sort_order }}" class="{{ $input }}">
                                </label>
                                <label class="block sm:col-span-2">
                                    <span class="mb-1 block text-xs font-medium text-slate-600">Thay ảnh (tùy chọn)</span>
                                    <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="adm-file">
                                </label>
                                @if ($key === 'slider')
                                    <label class="block sm:col-span-2">
                                        <span class="mb-1 block text-xs font-medium text-slate-600">{{ $banner->image_mobile ? 'Thay ảnh điện thoại' : 'Thêm ảnh điện thoại' }} (tùy chọn)</span>
                                        <input type="file" name="image_mobile" accept="image/jpeg,image/png,image/webp" class="adm-file">
                                    </label>
                                @endif
                                <div class="flex flex-wrap items-center gap-4 sm:col-span-2">
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="is_active" value="1" @checked($banner->is_active) class="h-4 w-4 accent-primary-600"> Hiển thị
                                    </label>
                                    <button class="adm-btn adm-btn-dark py-1.5">Lưu</button>
                                </div>
                            </form>
                            <form method="post" action="{{ route('admin.banners.destroy', $banner) }}" class="mt-2" onsubmit="return confirm('Xóa banner này? Ảnh sẽ bị xóa khỏi máy chủ.')">
                                @csrf
                                <button class="text-sm text-primary-600 hover:underline">Xóa</button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
@endforeach
@endsection
