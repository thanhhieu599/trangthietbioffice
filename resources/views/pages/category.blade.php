@extends('layouts.app')

@section('title', ($category->meta_title ?: $category->name.' cho doanh nghiệp – báo giá sỉ').($products->currentPage() > 1 ? ' – Trang '.$products->currentPage() : '').' | '.config('site.brand'))
@section('description', $category->meta_description ?: ($category->intro ?: 'Cung cấp '.mb_strtolower($category->name).' cho văn phòng, nhà máy. Báo giá theo số lượng, giao định kỳ, hóa đơn VAT.'))
@unless ($indexable)
    {{-- Danh mục chưa có sản phẩm / nội dung: cho khách xem nhưng không cho Google index (trang mỏng) --}}
    @section('robots', 'noindex, follow')
@endunless
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

    {{-- ===== Banner ngang (chỉ ảnh – Quản trị → Danh mục), ở trang 1. Chưa có ảnh: ô chờ ảnh, không chữ. ===== --}}
    @if ($products->currentPage() === 1)
        <div class="mb-4 aspect-[1200/380]">
            @include('partials.category-banner', ['banner' => $category->pageBannerUrl(), 'wide' => true])
        </div>
    @endif

    <section class="overflow-hidden rounded-xl bg-white shadow-sm">
        {{-- Tiêu đề + mô tả --}}
        <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-2 px-5 pt-5">
            <div class="min-w-0">
                <h1 class="text-xl font-bold uppercase tracking-wide text-ink-900 md:text-2xl">{{ $category->name }}</h1>
                @if ($category->intro && $products->currentPage() === 1)
                    <p class="mt-1 max-w-3xl text-sm text-neutral-600">{{ $category->intro }}</p>
                @endif
            </div>
            <span class="shrink-0 rounded-full bg-primary-50 px-3 py-1 text-sm font-semibold text-primary-600">{{ $products->total() }} sản phẩm</span>
        </div>

        {{-- Danh mục con + lọc / sắp xếp --}}
        <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-3 border-y border-neutral-100 bg-neutral-50/70 px-5 py-3 text-sm">
            @if ($category->children->isNotEmpty())
                <ul class="no-scrollbar -mx-1 flex max-w-full gap-2 overflow-x-auto px-1">
                    @foreach ($category->children as $child)
                        <li class="shrink-0"><a href="{{ $child->url() }}" class="inline-block rounded-full border border-neutral-300 bg-white px-3.5 py-1.5 transition hover:border-primary-600 hover:text-primary-600">{{ $child->name }}</a></li>
                    @endforeach
                </ul>
            @endif

            <form method="get" class="ml-auto flex flex-wrap items-center gap-2">
                @if ($brands->isNotEmpty())
                    <label for="brand" class="sr-only">Thương hiệu</label>
                    <select id="brand" name="brand" onchange="this.form.submit()" class="rounded-lg border border-neutral-300 bg-white px-3 py-1.5">
                        <option value="">Tất cả thương hiệu</option>
                        @foreach ($brands as $b)
                            <option value="{{ $b }}" @selected(request('brand') === $b)>{{ $b }}</option>
                        @endforeach
                    </select>
                @endif
                <label for="sort" class="sr-only">Sắp xếp</label>
                <select id="sort" name="sort" onchange="this.form.submit()" class="rounded-lg border border-neutral-300 bg-white px-3 py-1.5">
                    <option value="">Sắp xếp: Nổi bật</option>
                    <option value="price_asc" @selected(request('sort') === 'price_asc')>Giá tăng dần</option>
                    <option value="price_desc" @selected(request('sort') === 'price_desc')>Giá giảm dần</option>
                    <option value="name" @selected(request('sort') === 'name')>Tên A – Z</option>
                </select>
            </form>
        </div>

        {{-- Lưới sản phẩm: 2 / 3 / 5 cột (30 sản phẩm mỗi trang chia hết cho cả 3 → hàng cuối không lẻ) --}}
        @if ($products->isEmpty())
            <p class="px-5 py-8 text-center text-neutral-600">Danh mục đang cập nhật. Vui lòng <a href="{{ route('quote.create') }}" class="text-primary-600 underline">gửi yêu cầu báo giá</a> với mã hàng anh/chị cần.</p>
        @else
            <div class="grid grid-cols-2 gap-3 p-4 md:grid-cols-3 lg:grid-cols-5">
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
