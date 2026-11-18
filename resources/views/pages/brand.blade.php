@extends('layouts.app')

@section('title', 'Văn phòng phẩm '.$brand['name'].' chính hãng – giá sỉ cho doanh nghiệp | '.config('site.brand'))
@section('description', 'Các sản phẩm '.$brand['name'].' cho văn phòng, nhà máy: báo giá theo số lượng, giao định kỳ, hóa đơn VAT.')
@section('canonical', route('brands.show', $brand['slug']))

@section('content')
<div class="mx-auto max-w-site px-4 py-5">
    @include('partials.breadcrumb', ['items' => [
        ['name' => 'Thương hiệu', 'url' => route('brands.index')],
        ['name' => $brand['name'], 'url' => route('brands.show', $brand['slug'])],
    ]])

    <h1 class="sr-only">Thương hiệu {{ $brand['name'] }}</h1>

    {{-- ===== Banner thương hiệu: chỉ ảnh (tải lên tại Quản trị > Hình ảnh > Thương hiệu) ===== --}}
    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-black/5">
        @if ($banner)
            <img src="{{ $banner }}" alt="Thương hiệu {{ $brand['name'] }}" width="1200" height="300" fetchpriority="high"
                 class="aspect-[1200/300] w-full object-cover max-md:aspect-[2/1]">
        @else
            {{-- Chưa có banner: ô chờ ảnh (nền + logo nếu có, không chữ) --}}
            <div class="flex aspect-[1200/300] w-full items-center justify-center bg-[radial-gradient(circle_at_30%_20%,#fff_0%,transparent_55%),linear-gradient(160deg,#f6f7f9_0%,#eceef2_100%)] max-md:aspect-[2/1]">
                @if ($logo)<img src="{{ $logo }}" alt="{{ $brand['name'] }}" class="max-h-24 max-w-[40%] object-contain">@endif
            </div>
        @endif
    </div>

    {{-- ===== Thanh công cụ ===== --}}
    <form method="get" class="mt-5 flex flex-wrap items-center justify-between gap-3 text-sm">
        <p class="text-neutral-600"><strong class="text-ink-900">{{ $total }}</strong> sản phẩm · {{ $groups->count() }} danh mục</p>
        <label class="flex items-center gap-2">
            <span class="text-neutral-500">Sắp xếp</span>
            <select name="sort" onchange="this.form.submit()" class="rounded-md border border-neutral-300 bg-white px-2 py-1.5">
                <option value="">Nổi bật</option>
                <option value="price_asc" @selected(request('sort') === 'price_asc')>Giá tăng dần</option>
                <option value="price_desc" @selected(request('sort') === 'price_desc')>Giá giảm dần</option>
                <option value="name" @selected(request('sort') === 'name')>Tên A – Z</option>
            </select>
        </label>
    </form>

    {{-- Lối tắt tới từng danh mục khi có nhiều nhóm --}}
    @if ($groups->count() > 1)
        <nav class="no-scrollbar mt-3 flex gap-2 overflow-x-auto" aria-label="Danh mục của thương hiệu">
            @foreach ($groups as $g)
                <a href="#nhom-{{ $g['category']->slug ?? 'khac' }}" class="shrink-0 rounded-full border border-neutral-300 bg-white px-3.5 py-1.5 text-sm text-ink-800 hover:border-primary-600 hover:text-primary-600">
                    {{ $g['category']->name ?? 'Sản phẩm khác' }} <span class="text-neutral-400">({{ $g['items']->count() }})</span>
                </a>
            @endforeach
        </nav>
    @endif

    {{-- ===== Sản phẩm theo danh mục ===== --}}
    <div class="mt-4 space-y-5">
        @forelse ($groups as $g)
            <section id="nhom-{{ $g['category']->slug ?? 'khac' }}" class="scroll-mt-24 overflow-hidden rounded-md bg-white">
                <div class="flex items-center justify-between gap-4 border-b border-neutral-200 bg-neutral-100 px-4 py-3">
                    <h2 class="text-[17px] font-bold uppercase text-ink-900">{{ $g['category']->name ?? 'Sản phẩm khác' }}
                        <span class="text-sm font-normal normal-case text-neutral-500">({{ $g['items']->count() }})</span></h2>
                    @if ($g['category'])
                        <a href="{{ $g['category']->url() }}" class="flex shrink-0 items-center gap-1 text-sm text-neutral-600 hover:text-primary-600">Xem danh mục <x-icon name="chevron-right" class="h-4 w-4" /></a>
                    @endif
                </div>
                <div class="grid grid-cols-2 gap-3 p-3 md:grid-cols-3 lg:grid-cols-5">
                    @foreach ($g['items'] as $product)
                        @include('partials.product-card')
                    @endforeach
                </div>
            </section>
        @empty
            <p class="rounded-md bg-white p-6 text-center text-neutral-500">Thương hiệu này chưa có sản phẩm đang bán.</p>
        @endforelse
    </div>
</div>
@endsection
