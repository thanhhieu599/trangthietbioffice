@extends('admin.layout')

@section('title', $product->exists ? 'Sửa sản phẩm' : 'Thêm sản phẩm')
@section('width', 'max-w-7xl')

@php
    $input = 'adm-input';
    $specs = old('specs', collect($product->specs ?? [])->map(fn ($v, $k) => ['k' => $k, 'v' => $v])->values()->all());
    $config = [
        'type' => 'product',
        'brand' => config('site.brand'),
        'baseUrl' => url('/'),
        'prefix' => '/san-pham/',
        'uploadUrl' => route('admin.upload'),
        'exists' => $product->exists,
        'images' => array_values(old('keep_images', $product->images ?? [])),
        'rows' => array_values(array_map(fn ($s) => ['k' => $s['k'] ?? '', 'v' => $s['v'] ?? ''], $specs)),
        'f' => [
            'title' => old('name', $product->name),
            'slug' => old('slug', $product->slug),
            'brand' => old('brand', $product->brand),
            'summary' => old('short_description', $product->short_description),
            'content' => old('description', $product->description),
            'meta_title' => old('meta_title', $product->meta_title),
            'meta_description' => old('meta_description', $product->meta_description),
            'focus_keyword' => old('focus_keyword', $product->focus_keyword),
        ],
    ];
    $imgSrc = fn ($p) => \Illuminate\Support\Str::startsWith($p, ['http://', 'https://', '/']) ? $p : asset('storage/'.$p);
@endphp

@section('content')
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <div>
        <a href="{{ route('admin.products.index') }}" class="text-sm text-slate-500 hover:text-primary-600">← Danh sách sản phẩm</a>
        <h1 class="adm-h1">{{ $product->exists ? 'Sửa sản phẩm' : 'Thêm sản phẩm' }}</h1>
    </div>
    @if ($product->exists && $product->is_active)
        <a href="{{ $product->url() }}" target="_blank" class="text-sm text-primary-600 hover:underline">Xem trên website ↗</a>
    @endif
</div>

<form method="post" enctype="multipart/form-data"
      action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}"
      x-data="adminForm(@js($config))" class="grid gap-5 lg:grid-cols-[1fr_380px]">
    @csrf

    <div class="min-w-0 space-y-4">
        <section class="grid gap-4 adm-card p-5 sm:grid-cols-2">
            <label class="block sm:col-span-2">
                <span class="mb-1 block text-sm font-medium">Tên sản phẩm (H1) *</span>
                <input type="text" name="name" x-model="f.title" required maxlength="255" class="{{ $input }} text-base font-semibold"
                       placeholder="VD: Giấy in A4 Double A 80gsm">
            </label>
            <div class="sm:col-span-2">@include('admin.partials.slug-field')</div>
            <label class="block">
                <span class="mb-1 block text-sm font-medium">Mã SKU *</span>
                <input type="text" name="sku" value="{{ old('sku', $product->sku) }}" required maxlength="100" class="{{ $input }}">
            </label>
            <label class="block">
                <span class="mb-1 block text-sm font-medium">Danh mục</span>
                <select name="category_id" class="{{ $input }}">
                    <option value="">— Chưa chọn —</option>
                    @foreach ($categoryOptions as $id => $label)
                        <option value="{{ $id }}" @selected(old('category_id', $product->category_id) == $id)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="mb-1 block text-sm font-medium">Thương hiệu</span>
                <input type="text" name="brand" x-model="f.brand" maxlength="255" class="{{ $input }}" placeholder="VD: Double A">
            </label>
            <div class="grid grid-cols-2 gap-3">
                <label class="block">
                    <span class="mb-1 block text-sm font-medium">Đơn vị tính</span>
                    <input type="text" name="unit" value="{{ old('unit', $product->unit) }}" maxlength="50" class="{{ $input }}" placeholder="ram, hộp…">
                </label>
                <label class="block">
                    <span class="mb-1 block text-sm font-medium">Đặt tối thiểu</span>
                    <input type="number" name="min_order_qty" min="1" value="{{ old('min_order_qty', $product->min_order_qty ?? 1) }}" class="{{ $input }}">
                </label>
            </div>
            <label class="block">
                <span class="mb-1 block text-sm font-medium">Giá bán (đ)</span>
                <input type="number" name="price" min="0" step="1" value="{{ old('price', $product->price) }}" class="{{ $input }}" placeholder="Trống = “Liên hệ”">
            </label>
            <label class="block">
                <span class="mb-1 block text-sm font-medium">Giá gốc (đ) – hiện gạch ngang</span>
                <input type="number" name="compare_price" min="0" step="1" value="{{ old('compare_price', $product->compare_price) }}" class="{{ $input }}" placeholder="Tùy chọn">
            </label>
            <label class="block sm:col-span-2">
                <span class="mb-1 block text-sm font-medium">Quy cách đóng gói</span>
                <input type="text" name="pack_size" value="{{ old('pack_size', $product->pack_size) }}" maxlength="255" class="{{ $input }}" placeholder="VD: 5 ram/thùng">
            </label>
            <label class="block sm:col-span-2">
                <span class="mb-1 block text-sm font-medium">Mô tả ngắn</span>
                <textarea name="short_description" x-model="f.summary" rows="3" maxlength="1000" class="{{ $input }}"
                          placeholder="2–3 câu nêu điểm nổi bật, hiện cạnh ảnh sản phẩm."></textarea>
            </label>
        </section>

        {{-- Ảnh sản phẩm --}}
        <section class="adm-card p-5">
            <h2 class="font-semibold text-adm-ink">Ảnh sản phẩm</h2>
            <p class="text-xs text-slate-500">Ảnh đầu tiên là ảnh chính. Ảnh vuông, nền trắng, tối thiểu 800 × 800 px, tối đa 3 MB/ảnh. Tên file ảnh mới được đặt theo tên sản phẩm (tốt cho Google Hình ảnh).</p>
            <div class="mt-3 flex flex-wrap gap-3">
                <template x-for="(img, i) in images" :key="img">
                    <div class="w-28">
                        <div class="relative aspect-square overflow-hidden rounded border" :class="i === 0 ? 'border-primary-600' : 'border-slate-200'">
                            <img :src="/^(https?:)?\//.test(img) ? img : '/storage/' + img" alt="" class="h-full w-full object-contain">
                            <span x-show="i === 0" class="absolute left-0 top-0 rounded-br bg-primary-600 px-1.5 text-[10px] font-semibold text-white">Ảnh chính</span>
                        </div>
                        <input type="hidden" name="keep_images[]" :value="img">
                        <div class="mt-1 flex justify-between text-sm">
                            <button type="button" @click="moveImage(i, -1)" class="px-1 hover:text-primary-600" title="Lên trước">←</button>
                            <button type="button" @click="images.splice(i, 1); dirty = true" class="px-1 text-primary-600" title="Bỏ ảnh">✕</button>
                            <button type="button" @click="moveImage(i, 1)" class="px-1 hover:text-primary-600" title="Ra sau">→</button>
                        </div>
                    </div>
                </template>
                <template x-for="src in newImages" :key="src">
                    <div class="w-28">
                        <div class="aspect-square overflow-hidden rounded border border-dashed border-green-500"><img :src="src" alt="" class="h-full w-full object-contain"></div>
                        <p class="mt-1 text-center text-xs text-green-700">Ảnh mới</p>
                    </div>
                </template>
            </div>
            <input type="file" name="new_images[]" multiple accept="image/jpeg,image/png,image/webp" @change="pickNewImages($event)"
                   class="mt-3 adm-file">
        </section>

        <section class="adm-card p-5">
            @include('admin.partials.editor', [
                'name' => 'description',
                'label' => 'Mô tả chi tiết',
                'hint' => 'Nêu công dụng, ưu điểm, đối tượng phù hợp, cách bảo quản… Viết riêng, không chép nguyên mô tả của nhà sản xuất (Google ưu tiên nội dung độc nhất).',
            ])
        </section>

        {{-- Thông số kỹ thuật -> bảng thông số + additionalProperty trong schema Product --}}
        <section class="adm-card p-5">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-adm-ink">Thông số kỹ thuật</h2>
                    <p class="text-xs text-slate-500">VD: Định lượng – 80gsm; Khổ – A4 (210 × 297 mm).</p>
                </div>
                <button type="button" @click="rows.push({ k: '', v: '' })" class="adm-btn adm-btn-ghost">+ Thêm dòng</button>
            </div>
            <div class="mt-3 space-y-2">
                <template x-for="(row, i) in rows" :key="i">
                    <div class="flex gap-2">
                        <input type="text" :name="`specs[${i}][k]`" x-model="row.k" placeholder="Tên thông số" class="{{ $input }} w-2/5">
                        <input type="text" :name="`specs[${i}][v]`" x-model="row.v" placeholder="Giá trị" class="{{ $input }}">
                        <button type="button" @click="rows.splice(i, 1)" class="shrink-0 px-2 text-primary-600" title="Xóa">✕</button>
                    </div>
                </template>
                <p x-show="!rows.length" class="text-sm text-slate-500">Chưa có thông số.</p>
            </div>
        </section>
    </div>

    <aside class="space-y-4 lg:sticky lg:top-4 lg:self-start">
        <section class="adm-card p-5">
            <h2 class="font-semibold text-adm-ink">Hiển thị</h2>
            <label class="mt-3 flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active)) class="h-4 w-4 accent-primary-600"> Đang bán (hiện trên website)
            </label>
            <label class="mt-2 flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $product->is_featured)) class="h-4 w-4 accent-primary-600"> Sản phẩm nổi bật
            </label>
            <button class="mt-4 w-full adm-btn adm-btn-primary">{{ $product->exists ? 'Lưu sản phẩm' : 'Thêm sản phẩm' }}</button>
        </section>

        @include('admin.partials.seo-panel')
    </aside>
</form>

@if ($product->exists)
    <form method="post" action="{{ route('admin.products.destroy', $product) }}" class="mt-6"
          onsubmit="return confirm('Xóa vĩnh viễn sản phẩm này? Nếu chỉ tạm ngừng bán, hãy bỏ chọn “Đang bán”.')">
        @csrf
        <button class="text-sm text-primary-600 hover:underline">Xóa sản phẩm</button>
    </form>
@endif
@endsection
