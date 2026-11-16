@extends('layouts.app')

@section('title', ($category->meta_title ?: $category->name.' cho doanh nghiệp – báo giá sỉ').' | '.config('site.brand'))
@section('description', $category->meta_description ?: ($category->intro ?: 'Cung cấp '.mb_strtolower($category->name).' cho văn phòng, nhà máy. Báo giá theo số lượng, giao định kỳ, hóa đơn VAT.'))
@if ($products->currentPage() > 1)
    @section('canonical', $category->url().'?page='.$products->currentPage())
@else
    @section('canonical', $category->url())
@endif

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8">
    @include('partials.breadcrumb', ['items' => array_merge(
        $category->ancestors()->map(fn ($c) => ['name' => $c->name, 'url' => $c->url()])->all(),
        [['name' => $category->name, 'url' => $category->url()]]
    )])

    <h1 class="text-2xl font-bold text-slate-900 md:text-3xl">{{ $category->name }}</h1>
    @if ($category->intro)
        <p class="mt-3 max-w-3xl">{{ $category->intro }}</p>
    @endif

    @if ($category->children->isNotEmpty())
        <ul class="mt-5 flex flex-wrap gap-2">
            @foreach ($category->children as $child)
                <li><a href="{{ $child->url() }}" class="inline-block rounded-full border border-slate-300 bg-white px-4 py-1.5 text-sm hover:border-brand-600">{{ $child->name }}</a></li>
            @endforeach
        </ul>
    @endif

    <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-500">{{ $products->total() }} sản phẩm</p>
        @if ($brands->isNotEmpty())
            <form method="get" class="flex items-center gap-2 text-sm">
                <label for="brand">Thương hiệu</label>
                <select id="brand" name="brand" onchange="this.form.submit()" class="rounded-lg border border-slate-300 px-3 py-1.5">
                    <option value="">Tất cả</option>
                    @foreach ($brands as $b)
                        <option value="{{ $b }}" @selected(request('brand') === $b)>{{ $b }}</option>
                    @endforeach
                </select>
            </form>
        @endif
    </div>

    @if ($products->isEmpty())
        <p class="mt-8 rounded-xl bg-white p-6">Danh mục đang cập nhật. Vui lòng <a href="{{ route('quote.create') }}" class="text-brand-600 underline">gửi yêu cầu báo giá</a> với mã hàng anh/chị cần.</p>
    @else
        <div class="mt-4 grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
            @foreach ($products as $product)
                @include('partials.product-card')
            @endforeach
        </div>
        <div class="mt-8">{{ $products->links() }}</div>
    @endif

    @if ($category->content && $products->currentPage() === 1)
        <section class="prose-content mt-12 max-w-3xl rounded-xl bg-white p-6">
            {!! $category->content !!}
        </section>
    @endif
</div>
@endsection
