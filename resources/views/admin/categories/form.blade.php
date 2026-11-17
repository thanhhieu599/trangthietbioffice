@extends('admin.layout')

@section('title', $category->exists ? 'Sửa danh mục' : 'Thêm danh mục')
@section('width', 'max-w-7xl')

@php
    $input = 'adm-input';
    $config = [
        'type' => 'category',
        'brand' => config('site.brand'),
        'baseUrl' => url('/'),
        'prefix' => '/danh-muc/',
        'uploadUrl' => route('admin.upload'),
        'exists' => $category->exists,
        'f' => [
            'title' => old('name', $category->name),
            'slug' => old('slug', $category->slug),
            'summary' => old('intro', $category->intro),
            'content' => old('content', $category->content),
            'meta_title' => old('meta_title', $category->meta_title),
            'meta_description' => old('meta_description', $category->meta_description),
            'focus_keyword' => old('focus_keyword', $category->focus_keyword),
        ],
    ];
@endphp

@section('content')
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <div>
        <a href="{{ route('admin.categories.index') }}" class="text-sm text-slate-500 hover:text-primary-600">← Danh mục sản phẩm</a>
        <h1 class="adm-h1">{{ $category->exists ? 'Sửa danh mục' : 'Thêm danh mục' }}</h1>
    </div>
    @if ($category->exists && $category->is_active)
        <a href="{{ $category->url() }}" target="_blank" class="text-sm text-primary-600 hover:underline">Xem trên website ↗</a>
    @endif
</div>

<form method="post" enctype="multipart/form-data"
      action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}"
      x-data="adminForm(@js($config))" class="grid gap-5 lg:grid-cols-[1fr_380px]">
    @csrf

    <div class="min-w-0 space-y-4">
        <section class="grid gap-4 adm-card p-5 sm:grid-cols-2">
            <label class="block sm:col-span-2">
                <span class="mb-1 block text-sm font-medium">Tên danh mục (H1) *</span>
                <input type="text" name="name" x-model="f.title" required maxlength="255" class="{{ $input }} text-base font-semibold" placeholder="VD: Giấy in, giấy photo">
            </label>
            <div class="sm:col-span-2">@include('admin.partials.slug-field')</div>
            <label class="block">
                <span class="mb-1 block text-sm font-medium">Danh mục cha</span>
                <select name="parent_id" class="{{ $input }}">
                    <option value="">— Không (danh mục gốc) —</option>
                    @foreach ($parentOptions as $id => $label)
                        <option value="{{ $id }}" @selected(old('parent_id', $category->parent_id) == $id)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="mb-1 block text-sm font-medium">Thứ tự (số nhỏ hiện trước)</span>
                <input type="number" name="sort_order" min="0" max="9999" value="{{ old('sort_order', $category->sort_order) }}" class="{{ $input }}">
            </label>
            <label class="block sm:col-span-2">
                <span class="mb-1 block text-sm font-medium">Đoạn giới thiệu đầu trang</span>
                <textarea name="intro" x-model="f.summary" rows="3" maxlength="2000" class="{{ $input }}"
                          placeholder="2–3 câu giới thiệu, hiện ngay dưới tên danh mục, trên danh sách sản phẩm."></textarea>
            </label>
        </section>

        <section class="adm-card p-5">
            @include('admin.partials.editor', [
                'name' => 'content',
                'label' => 'Nội dung SEO cuối trang',
                'hint' => 'Hiện bên dưới danh sách sản phẩm. Gợi ý: cách chọn, bảng so sánh, câu hỏi thường gặp, link tới danh mục/bài viết liên quan.',
            ])
        </section>
    </div>

    <aside class="space-y-4 lg:sticky lg:top-4 lg:self-start">
        <section class="adm-card p-5">
            <h2 class="font-semibold text-adm-ink">Hiển thị</h2>
            <label class="mt-3 flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active)) class="h-4 w-4 accent-primary-600"> Hiện trên website
            </label>
            <label class="mt-2 flex items-center gap-2 text-sm">
                <input type="checkbox" name="show_on_home" value="1" @checked(old('show_on_home', $category->show_on_home)) class="h-4 w-4 accent-primary-600"> Hiện thành khối sản phẩm ở trang chủ
            </label>
            @if ($category->exists)
                <p class="mt-3 text-xs text-slate-500">Đang có {{ $category->products_count }} sản phẩm.
                    <a href="{{ route('admin.products.index', ['category' => $category->id]) }}" class="text-primary-600 hover:underline">Xem</a></p>
            @endif
            <button class="mt-4 w-full adm-btn adm-btn-primary">{{ $category->exists ? 'Lưu danh mục' : 'Tạo danh mục' }}</button>
        </section>

        <section class="adm-card p-5" x-data="{ preview: @js($category->exists ? $category->imageUrl() : null) }">
            <h2 class="font-semibold text-adm-ink">Ảnh / biểu tượng</h2>
            <p class="text-xs text-slate-500">Hiện ở lưới danh mục trang chủ. Ảnh vuông, nền trong suốt hoặc trắng, khoảng 200 × 200 px.</p>
            <template x-if="preview">
                <img :src="preview" alt="" class="mt-2 h-24 w-24 rounded border border-slate-200 object-contain p-1">
            </template>
            <input type="file" name="image" accept="image/jpeg,image/png,image/webp"
                   @change="preview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : preview"
                   class="mt-2 adm-file">
            @if ($category->image)
                <label class="mt-2 flex items-center gap-2 text-sm text-primary-600">
                    <input type="checkbox" name="remove_image" value="1" class="accent-primary-600"> Xóa ảnh đã tải lên (dùng lại ảnh mặc định)
                </label>
            @endif
        </section>

        @include('admin.partials.seo-panel')
    </aside>
</form>

@if ($category->exists)
    <form method="post" action="{{ route('admin.categories.destroy', $category) }}" class="mt-6"
          onsubmit="return confirm('Xóa danh mục này?')">
        @csrf
        <button class="text-sm text-primary-600 hover:underline">Xóa danh mục</button>
    </form>
@endif
@endsection
