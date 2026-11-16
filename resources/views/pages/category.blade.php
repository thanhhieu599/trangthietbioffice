@extends('layouts.app')

@section('title', ($category->meta_title ?: $category->name.' cho doanh nghiệp – báo giá sỉ').' | '.config('site.brand'))
@section('description', $category->meta_description ?: ($category->intro ?: 'Cung cấp '.mb_strtolower($category->name).' cho văn phòng, nhà máy. Báo giá theo số lượng, giao định kỳ, hóa đơn VAT.'))
@if ($products->currentPage() > 1)
    @section('canonical', $category->url().'?page='.$products->currentPage())
@else
    @section('canonical', $category->url())
@endif

@section('content')
<div class="mx-auto max-w-site px-4 py-5">
    @include('partials.breadcrumb', ['items' => array_merge(
        $category->ancestors()->map(fn ($c) => ['name' => $c->name, 'url' => $c->url()])->all(),
        [['name' => $category->name, 'url' => $category->url()]]
    )])

    <section class="overflow-hidden rounded-md bg-white">
        <div class="border-b border-neutral-200 bg-neutral-100 px-4 py-3">
            <h1 class="text-xl font-bold uppercase text-ink-900">{{ $category->name }}</h1>
            @if ($category->intro && $products->currentPage() === 1)
                <p class="mt-1 text-sm text-neutral-600">{{ $category->intro }}</p>
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-x-4 gap-y-3 px-4 py-3 text-sm">
            @if ($category->children->isNotEmpty())
                <ul class="flex flex-wrap gap-2">
                    @foreach ($category->children as $child)
                        <li><a href="{{ $child->url() }}" class="inline-block rounded border border-neutral-300 px-3 py-1 hover:border-primary-600 hover:text-primary-600">{{ $child->name }}</a></li>
                    @endforeach
                </ul>
            @endif

            <form method="get" class="ml-auto flex flex-wrap items-center gap-2">
                <span class="text-neutral-500">{{ $products->total() }} sản phẩm</span>
                @if ($brands->isNotEmpty())
                    <label for="brand" class="sr-only">Thương hiệu</label>
                    <select id="brand" name="brand" onchange="this.form.submit()" class="rounded border border-neutral-300 px-2 py-1.5">
                        <option value="">Tất cả thương hiệu</option>
                        @foreach ($brands as $b)
                            <option value="{{ $b }}" @selected(request('brand') === $b)>{{ $b }}</option>
                        @endforeach
                    </select>
                @endif
                <label for="sort" class="sr-only">Sắp xếp</label>
                <select id="sort" name="sort" onchange="this.form.submit()" class="rounded border border-neutral-300 px-2 py-1.5">
                    <option value="">Nổi bật</option>
                    <option value="price_asc" @selected(request('sort') === 'price_asc')>Giá tăng dần</option>
                    <option value="price_desc" @selected(request('sort') === 'price_desc')>Giá giảm dần</option>
                    <option value="name" @selected(request('sort') === 'name')>Tên A – Z</option>
                </select>
            </form>
        </div>

        @if ($products->isEmpty())
            <p class="px-4 pb-6">Danh mục đang cập nhật. Vui lòng <a href="{{ route('quote.create') }}" class="text-primary-600 underline">gửi yêu cầu báo giá</a> với mã hàng anh/chị cần.</p>
        @else
            <div class="grid grid-cols-2 gap-3 px-3 pb-4 md:grid-cols-3 lg:grid-cols-5">
                @foreach ($products as $product)
                    @include('partials.product-card')
                @endforeach
            </div>
        @endif
    </section>

    @if ($products->hasPages())
        <div class="mt-6">{{ $products->links() }}</div>
    @endif

    @if ($category->content && $products->currentPage() === 1)
        <section class="prose-content mt-6 rounded-md bg-white p-6">
            {!! $category->content !!}
        </section>
    @endif
</div>
@endsection
