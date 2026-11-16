@extends('layouts.app')

@section('title', 'Văn phòng phẩm '.$brand['name'].' chính hãng – giá sỉ cho doanh nghiệp | '.config('site.brand'))
@section('description', 'Các sản phẩm '.$brand['name'].' cho văn phòng, nhà máy: báo giá theo số lượng, giao định kỳ, hóa đơn VAT.')
@section('canonical', route('brands.show', $brand['slug']).($products->currentPage() > 1 ? '?page='.$products->currentPage() : ''))

@section('content')
<div class="mx-auto max-w-site px-4 py-5">
    @include('partials.breadcrumb', ['items' => [
        ['name' => 'Thương hiệu', 'url' => route('brands.index')],
        ['name' => $brand['name'], 'url' => route('brands.show', $brand['slug'])],
    ]])

    <section class="overflow-hidden rounded-md bg-white">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-neutral-200 bg-neutral-100 px-4 py-3">
            <h1 class="text-xl font-bold uppercase text-ink-900">Thương hiệu {{ $brand['name'] }}</h1>
            <form method="get" class="flex items-center gap-2 text-sm">
                <span class="text-neutral-500">{{ $products->total() }} sản phẩm</span>
                <label for="sort" class="sr-only">Sắp xếp</label>
                <select id="sort" name="sort" onchange="this.form.submit()" class="rounded border border-neutral-300 bg-white px-2 py-1.5">
                    <option value="">Nổi bật</option>
                    <option value="price_asc" @selected(request('sort') === 'price_asc')>Giá tăng dần</option>
                    <option value="price_desc" @selected(request('sort') === 'price_desc')>Giá giảm dần</option>
                    <option value="name" @selected(request('sort') === 'name')>Tên A – Z</option>
                </select>
            </form>
        </div>
        <div class="grid grid-cols-2 gap-3 p-3 md:grid-cols-3 lg:grid-cols-5">
            @foreach ($products as $product)
                @include('partials.product-card')
            @endforeach
        </div>
    </section>

    @if ($products->hasPages())
        <div class="mt-6">{{ $products->links() }}</div>
    @endif
</div>
@endsection
