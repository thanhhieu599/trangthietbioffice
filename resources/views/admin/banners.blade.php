@extends('admin.layout')

@section('title', 'Banner trang chủ')

@php($input = 'w-full rounded border border-neutral-300 px-3 py-2 text-sm focus:border-primary-600 focus:outline-none')

@section('content')
<h1 class="text-xl font-bold text-ink-900">Banner trang chủ</h1>
<p class="mt-1 text-sm text-neutral-600">Ảnh hiển thị ở khung trình chiếu đầu trang chủ, bên phải menu danh mục. Tự chuyển ảnh mỗi 5 giây.</p>

{{-- ===== Thêm banner ===== --}}
<section class="mt-5 rounded-md bg-white p-5">
    <h2 class="font-semibold text-ink-900">Thêm banner mới</h2>
    <ul class="mt-2 list-disc space-y-0.5 pl-5 text-sm text-neutral-600">
        <li>Kích thước khuyến nghị <strong>1170 × 500 px</strong>, định dạng JPG / PNG / WebP, tối đa 2 MB (nén ảnh tại tinypng.com nếu nặng hơn).</li>
        <li>Đặt chữ và sản phẩm chính ở <strong>giữa ảnh</strong>: màn hình hẹp sẽ cắt bớt hai bên.</li>
        <li>Ảnh cho điện thoại (tùy chọn): 800 × 450 px. Không có thì điện thoại dùng ảnh chính.</li>
    </ul>

    <form method="post" action="{{ route('admin.banners.store') }}" enctype="multipart/form-data"
          class="mt-4 grid gap-4 md:grid-cols-2" x-data="{ preview: null }">
        @csrf
        <label class="block md:col-span-2">
            <span class="mb-1 block text-sm font-medium">Ảnh banner *</span>
            <input type="file" name="image" accept="image/jpeg,image/png,image/webp" required
                   @change="preview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null"
                   class="block w-full text-sm file:mr-3 file:rounded file:border-0 file:bg-primary-600 file:px-4 file:py-2 file:font-semibold file:text-white hover:file:bg-primary-700">
        </label>
        <template x-if="preview">
            <div class="md:col-span-2">
                <p class="mb-1 text-xs text-neutral-500">Xem trước (khung tỉ lệ giống trang chủ):</p>
                <div class="aspect-[1170/500] overflow-hidden rounded-md bg-neutral-100">
                    <img :src="preview" alt="" class="h-full w-full object-cover">
                </div>
            </div>
        </template>
        <label class="block">
            <span class="mb-1 block text-sm font-medium">Ảnh cho điện thoại (tùy chọn)</span>
            <input type="file" name="image_mobile" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm file:mr-3 file:rounded file:border-0 file:bg-ink-900 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-white hover:file:bg-black">
        </label>
        <label class="block">
            <span class="mb-1 block text-sm font-medium">Tiêu đề (mô tả ảnh, tốt cho SEO)</span>
            <input type="text" name="title" value="{{ old('title') }}" placeholder="VD: Khuyến mãi giấy in tháng 11" class="{{ $input }}">
        </label>
        <label class="block">
            <span class="mb-1 block text-sm font-medium">Link khi bấm vào ảnh</span>
            <input type="text" name="link" value="{{ old('link') }}" placeholder="VD: /danh-muc/giay-in-giay-photo hoặc /bao-gia" class="{{ $input }}">
        </label>
        <label class="block">
            <span class="mb-1 block text-sm font-medium">Thứ tự (số nhỏ hiện trước)</span>
            <input type="number" name="sort_order" min="0" max="999" value="{{ old('sort_order') }}" placeholder="Để trống = cuối danh sách" class="{{ $input }}">
        </label>
        <div class="md:col-span-2">
            <button class="rounded bg-primary-600 px-6 py-2.5 font-semibold text-white hover:bg-primary-700">Tải lên banner</button>
        </div>
    </form>
</section>

{{-- ===== Danh sách banner ===== --}}
<section class="mt-6">
    <h2 class="font-semibold text-ink-900">Banner đã tải lên ({{ $banners->count() }})</h2>

    @if ($banners->isEmpty())
        <div class="mt-3 rounded-md border border-dashed border-neutral-300 bg-white p-5 text-sm">
            Chưa có banner nào được tải lên. Trang chủ đang dùng
            <strong>{{ $folderBanners->count() }} ảnh</strong> trong thư mục <code>public/images/banners</code>@if ($folderBanners->isNotEmpty()) (ảnh demo)@endif.
            Tải lên ít nhất 1 banner thì trang chủ sẽ chuyển sang dùng các banner tại đây.
        </div>
    @else
        @if ($banners->where('is_active', true)->isEmpty())
            <p class="mt-2 rounded border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">Tất cả banner đang ẩn → trang chủ tạm dùng ảnh trong thư mục <code>public/images/banners</code>.</p>
        @endif
        <ul class="mt-3 space-y-3">
            @foreach ($banners as $banner)
                <li class="grid gap-4 rounded-md bg-white p-4 md:grid-cols-[280px_1fr] {{ $banner->is_active ? '' : 'opacity-60' }}">
                    <div>
                        <div class="aspect-[1170/500] overflow-hidden rounded bg-neutral-100">
                            <img src="{{ $banner->imageUrl() }}" alt="" class="h-full w-full object-cover" loading="lazy">
                        </div>
                        @if ($banner->image_mobile)
                            <p class="mt-1 text-xs text-neutral-500">✓ Có ảnh riêng cho điện thoại</p>
                        @endif
                    </div>
                    <div>
                        <form method="post" action="{{ route('admin.banners.update', $banner) }}" enctype="multipart/form-data" class="grid gap-3 sm:grid-cols-2">
                            @csrf
                            <label class="block sm:col-span-2">
                                <span class="mb-1 block text-xs font-medium text-neutral-600">Tiêu đề</span>
                                <input type="text" name="title" value="{{ $banner->title }}" class="{{ $input }}">
                            </label>
                            <label class="block">
                                <span class="mb-1 block text-xs font-medium text-neutral-600">Link</span>
                                <input type="text" name="link" value="{{ $banner->link }}" class="{{ $input }}">
                            </label>
                            <label class="block">
                                <span class="mb-1 block text-xs font-medium text-neutral-600">Thứ tự</span>
                                <input type="number" name="sort_order" min="0" max="999" value="{{ $banner->sort_order }}" class="{{ $input }}">
                            </label>
                            <label class="block sm:col-span-2">
                                <span class="mb-1 block text-xs font-medium text-neutral-600">{{ $banner->image_mobile ? 'Thay ảnh điện thoại' : 'Thêm ảnh điện thoại' }} (tùy chọn)</span>
                                <input type="file" name="image_mobile" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm file:mr-3 file:rounded file:border-0 file:bg-ink-900 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-white hover:file:bg-black">
                            </label>
                            <div class="flex flex-wrap items-center gap-4 sm:col-span-2">
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="is_active" value="1" @checked($banner->is_active) class="h-4 w-4 accent-primary-600">
                                    Hiển thị trên trang chủ
                                </label>
                                <button class="rounded bg-ink-900 px-4 py-2 text-sm font-semibold text-white hover:bg-black">Lưu</button>
                            </div>
                        </form>
                        <form method="post" action="{{ route('admin.banners.destroy', $banner) }}" class="mt-2"
                              onsubmit="return confirm('Xóa banner này? Ảnh sẽ bị xóa khỏi máy chủ.')">
                            @csrf
                            <button class="text-sm text-primary-600 hover:underline">Xóa banner</button>
                        </form>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</section>
@endsection
