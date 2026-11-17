@extends('admin.layout')

@section('title', 'Sản phẩm')
@section('width', 'max-w-7xl')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3">
    <h1 class="adm-h1">Sản phẩm <span class="text-base font-normal text-slate-500">({{ $products->total() }})</span></h1>
    <a href="{{ route('admin.products.create') }}" class="adm-btn adm-btn-primary">+ Thêm sản phẩm</a>
</div>
<p class="mt-1 text-sm text-slate-500">Nhập hàng loạt từ file CSV: <code>php artisan products:import</code> (mẫu: docs/mau-nhap-san-pham.csv).</p>

<form class="mt-4 flex flex-wrap gap-2 text-sm">
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Tên, mã SKU, thương hiệu…" class="w-60 adm-input w-auto py-1.5">
    <select name="category" class="adm-input w-auto py-1.5">
        <option value="">Tất cả danh mục</option>
        @foreach ($categoryOptions as $id => $label)
            <option value="{{ $id }}" @selected(request('category') == $id)>{{ $label }}</option>
        @endforeach
    </select>
    <select name="status" class="adm-input w-auto py-1.5">
        <option value="">Mọi trạng thái</option>
        <option value="active" @selected(request('status') === 'active')>Đang bán</option>
        <option value="hidden" @selected(request('status') === 'hidden')>Đang ẩn</option>
        <option value="featured" @selected(request('status') === 'featured')>Nổi bật</option>
    </select>
    <button class="adm-btn adm-btn-dark">Lọc</button>
    @if (request()->hasAny(['q', 'category', 'status']))
        <a href="{{ route('admin.products.index') }}" class="px-2 py-1.5 text-slate-500 hover:text-primary-600">Bỏ lọc</a>
    @endif
</form>

<div class="mt-4 adm-card overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="adm-thead">
            <tr>
                <th class="px-4 py-3">Sản phẩm</th>
                <th class="px-4 py-3">Danh mục</th>
                <th class="px-4 py-3 text-right">Giá</th>
                <th class="px-4 py-3">Trạng thái</th>
                <th class="px-4 py-3">SEO</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($products as $product)
                @php($issues = \App\Support\SeoAudit::product($product))
                <tr class="align-top transition hover:bg-slate-50/80">
                    <td class="px-4 py-3">
                        <div class="flex gap-3">
                            @if ($img = $product->mainImage())
                                <img src="{{ $img }}" alt="" class="h-12 w-12 shrink-0 rounded border border-slate-100 object-contain" loading="lazy">
                            @else
                                <div class="h-12 w-12 shrink-0 rounded bg-slate-100"></div>
                            @endif
                            <div class="min-w-0">
                                <a href="{{ route('admin.products.edit', $product) }}" class="font-semibold text-adm-ink hover:text-primary-600">{{ $product->name }}</a>
                                <p class="text-xs text-slate-500">SKU: {{ $product->sku }}@if ($product->brand) · {{ $product->brand }}@endif</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-slate-600">{{ $product->category?->name ?? '—' }}</td>
                    <td class="px-4 py-3 whitespace-nowrap text-right">{{ $product->formattedPrice() }}</td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="adm-badge {{ $product->is_active ? 'adm-badge-green' : 'adm-badge-gray' }}">{{ $product->is_active ? 'Đang bán' : 'Ẩn' }}</span>
                        @if ($product->is_featured)<span class="ml-1 adm-badge adm-badge-amber">Nổi bật</span>@endif
                    </td>
                    <td class="px-4 py-3">
                        @if ($issues)
                            <details class="text-xs">
                                <summary class="cursor-pointer list-none"><span class="adm-badge adm-badge-amber">{{ count($issues) }} việc cần sửa ▾</span></summary>
                                <ul class="mt-1 list-disc pl-4 text-slate-600">@foreach ($issues as $i)<li>{{ $i }}</li>@endforeach</ul>
                            </details>
                        @else
                            <span class="adm-badge adm-badge-green">✓ Tốt</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">Không có sản phẩm nào.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $products->links() }}</div>
@endsection
