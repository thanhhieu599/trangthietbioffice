@extends('admin.layout')

@section('title', $sale->exists ? 'Sửa Flash Sale' : 'Tạo Flash Sale')
@section('width', 'max-w-7xl')

@php
    $oldRows = old('items');
    if (is_array($oldRows)) {
        // Sau lỗi kiểm tra: dựng lại hàng từ dữ liệu vừa nhập.
        $pp = \App\Models\Product::whereIn('id', collect($oldRows)->pluck('product_id'))->get()->keyBy('id');
        $rows = collect($oldRows)->filter(fn ($r) => isset($pp[$r['product_id'] ?? 0]))->map(fn ($r) => [
            'product_id' => (int) $r['product_id'], 'name' => $pp[$r['product_id']]->name, 'sku' => $pp[$r['product_id']]->sku,
            'price' => (int) $pp[$r['product_id']]->price, 'image' => $pp[$r['product_id']]->mainImage(),
            'sale_price' => $r['sale_price'] ?? null, 'quantity_limit' => $r['quantity_limit'] ?? null,
        ])->values()->all();
    }
    $fmt = fn ($d) => $d?->format('Y-m-d\TH:i');
@endphp

@section('content')
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <div>
        <a href="{{ route('admin.flash-sales.index') }}" class="text-sm text-slate-500 hover:text-primary-600">← Danh sách Flash Sale</a>
        <h1 class="adm-h1">{{ $sale->exists ? 'Sửa Flash Sale' : 'Tạo Flash Sale' }}</h1>
    </div>
    @if ($sale->exists)
        <span class="adm-badge {{ $sale->status() === 'running' ? 'adm-badge-green' : 'adm-badge-gray' }}">{{ $sale->statusLabel() }}</span>
    @endif
</div>

<form method="post" enctype="multipart/form-data"
      action="{{ $sale->exists ? route('admin.flash-sales.update', $sale) : route('admin.flash-sales.store') }}"
      x-data="flashItems(@js($rows), @js(route('admin.flash-sales.products')))" class="grid gap-5 lg:grid-cols-[1fr_360px]">
    @csrf

    {{-- ===== Sản phẩm ===== --}}
    <section class="adm-card min-w-0 p-5">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-semibold text-adm-ink">Sản phẩm trong chương trình (<span x-text="rows.length"></span>)</h2>
            <div class="flex items-center gap-1.5 text-xs" x-show="rows.length">
                <span class="text-adm-muted">Giảm nhanh tất cả:</span>
                <template x-for="p in [10, 20, 30, 50]" :key="p">
                    <button type="button" class="rounded-md border border-adm-line px-2 py-1 font-semibold hover:border-primary-600 hover:text-primary-600" @click="applyAll(p)" x-text="'-' + p + '%'"></button>
                </template>
            </div>
        </div>

        {{-- Tìm & thêm --}}
        <div class="relative mt-4" @click.outside="open = false">
            <input type="search" x-model="q" @input.debounce.300ms="search()" @focus="search(); open = true"
                   placeholder="Tìm sản phẩm theo tên hoặc mã SKU để thêm… (chỉ sản phẩm đang bán, có giá)" class="adm-input py-2.5" autocomplete="off">
            <div x-show="open && results.length" x-cloak class="absolute z-20 mt-1 max-h-80 w-full overflow-y-auto rounded-lg border border-adm-line bg-white py-1 shadow-xl">
                <template x-for="r in results" :key="r.product_id">
                    <button type="button" @click="add(r)" :disabled="has(r.product_id)"
                            class="flex w-full items-center gap-3 px-3 py-2 text-left text-sm hover:bg-slate-50 disabled:cursor-default disabled:opacity-50">
                        <span class="h-9 w-9 shrink-0 overflow-hidden rounded bg-slate-100"><img x-show="r.image" :src="r.image" alt="" class="h-full w-full object-contain"></span>
                        <span class="min-w-0 flex-1"><span class="block truncate font-medium text-adm-ink" x-text="r.name"></span><span class="text-xs text-adm-muted" x-text="r.sku"></span></span>
                        <span class="shrink-0 text-xs font-semibold" x-text="has(r.product_id) ? 'Đã thêm' : money(r.price)"></span>
                    </button>
                </template>
            </div>
            <p x-show="open && q && !loading && !results.length" x-cloak class="mt-1 text-xs text-adm-muted">Không thấy sản phẩm phù hợp (sản phẩm cần đang bán và có giá).</p>
        </div>

        {{-- Bảng sản phẩm --}}
        <div class="mt-4 overflow-x-auto" x-show="rows.length">
            <table class="w-full text-sm">
                <thead class="adm-thead">
                    <tr><th class="px-3 py-2">Sản phẩm</th><th class="px-3 py-2 text-right">Giá gốc</th><th class="px-3 py-2">Giá flash *</th><th class="px-3 py-2">Số suất</th><th class="px-3 py-2"></th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="(r, i) in rows" :key="r.product_id">
                        <tr>
                            <td class="px-3 py-2">
                                <div class="flex items-center gap-3">
                                    <span class="h-10 w-10 shrink-0 overflow-hidden rounded bg-slate-100"><img x-show="r.image" :src="r.image" alt="" class="h-full w-full object-contain"></span>
                                    <span class="min-w-0"><span class="block max-w-xs truncate font-medium text-adm-ink" x-text="r.name"></span><span class="text-xs text-adm-muted" x-text="r.sku"></span></span>
                                </div>
                                <input type="hidden" :name="`items[${i}][product_id]`" :value="r.product_id">
                            </td>
                            <td class="px-3 py-2 text-right whitespace-nowrap text-adm-muted" x-text="money(r.price)"></td>
                            <td class="px-3 py-2">
                                <div class="flex items-center gap-2">
                                    <input type="number" min="1" step="100" required :name="`items[${i}][sale_price]`" x-model.number="r.sale_price"
                                           class="adm-input w-32 py-1.5" :class="(r.sale_price && r.sale_price >= r.price) && '!border-red-400 !bg-red-50'">
                                    <span class="w-12 text-xs font-bold" :class="pct(r) > 0 ? 'text-primary-600' : 'text-red-500'" x-text="r.sale_price ? (pct(r) > 0 ? '-' + pct(r) + '%' : 'Sai') : ''"></span>
                                </div>
                            </td>
                            <td class="px-3 py-2"><input type="number" min="1" :name="`items[${i}][quantity_limit]`" x-model.number="r.quantity_limit" placeholder="Không giới hạn" class="adm-input w-32 py-1.5"></td>
                            <td class="px-3 py-2 whitespace-nowrap text-right">
                                <button type="button" @click="move(i, -1)" class="px-1 text-slate-500 hover:text-adm-ink" title="Lên">↑</button>
                                <button type="button" @click="move(i, 1)" class="px-1 text-slate-500 hover:text-adm-ink" title="Xuống">↓</button>
                                <button type="button" @click="rows.splice(i, 1)" class="ml-1 px-1 text-primary-600" title="Bỏ">✕</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <div x-show="!rows.length" class="mt-4 rounded-lg border border-dashed border-slate-300 p-8 text-center text-sm text-adm-muted">
            Chưa có sản phẩm. Gõ tên hoặc mã sản phẩm vào ô tìm kiếm phía trên để thêm.
        </div>
    </section>

    {{-- ===== Thiết lập ===== --}}
    <aside class="space-y-5 lg:sticky lg:top-20 lg:self-start">
        <section class="adm-card p-5">
            <h2 class="font-semibold text-adm-ink">Thiết lập</h2>
            <label class="mt-3 block">
                <span class="mb-1 block text-sm font-medium">Tên chương trình *</span>
                <input type="text" name="name" value="{{ old('name', $sale->name) }}" required maxlength="255" class="adm-input" placeholder="VD: Mừng ngày Nhà giáo 20/11">
            </label>
            <label class="mt-3 block">
                <span class="mb-1 block text-sm font-medium">Bắt đầu *</span>
                <input type="datetime-local" name="starts_at" value="{{ old('starts_at', $fmt($sale->starts_at)) }}" required class="adm-input">
            </label>
            <label class="mt-3 block">
                <span class="mb-1 block text-sm font-medium">Kết thúc *</span>
                <input type="datetime-local" name="ends_at" value="{{ old('ends_at', $fmt($sale->ends_at)) }}" required class="adm-input">
            </label>
            <label class="mt-3 flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $sale->is_active)) class="h-4 w-4 accent-primary-600"> Bật chương trình
            </label>
            <label class="mt-2 flex items-start gap-2 text-sm">
                <input type="checkbox" name="auto_renew" value="1" @checked(old('auto_renew', $sale->auto_renew)) class="mt-0.5 h-4 w-4 accent-primary-600">
                <span>Tự lặp lại khi hết giờ</span>
            </label>
            <button class="adm-btn adm-btn-primary mt-4 w-full">{{ $sale->exists ? 'Lưu chương trình' : 'Tạo chương trình' }}</button>
        </section>

        <section class="adm-card p-5">
            <h2 class="font-semibold text-adm-ink">Banner trang Flash Sale</h2>
            @foreach (['banner' => 'Máy tính – 1200 × 300 px', 'banner_mobile' => 'Điện thoại – 800 × 400 px'] as $field => $label)
                <div class="mt-3">
                    <span class="mb-1 block text-sm font-medium">{{ $label }}</span>
                    @if ($sale->{$field})
                        <img src="{{ $sale->bannerUrl($sale->{$field}) }}" alt="" class="mb-2 w-full rounded border border-adm-line object-cover">
                        <label class="mb-2 flex items-center gap-2 text-xs text-primary-600"><input type="checkbox" name="remove_{{ $field }}" value="1" class="accent-primary-600"> Xóa ảnh này</label>
                    @endif
                    <input type="file" name="{{ $field }}" accept="image/jpeg,image/png,image/webp" class="adm-file">
                </div>
            @endforeach
        </section>
    </aside>
</form>

@if ($sale->exists)
    <form method="post" action="{{ route('admin.flash-sales.destroy', $sale) }}" class="mt-6" onsubmit="return confirm('Xóa chương trình Flash Sale này?')">
        @csrf
        <button class="text-sm text-primary-600 hover:underline">Xóa chương trình</button>
    </form>
@endif
@endsection
