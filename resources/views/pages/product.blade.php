@extends('layouts.app')

@section('title', ($product->meta_title ?: $product->name.($product->brand ? ' – '.$product->brand : '')).' | '.config('site.brand'))
@section('description', $product->meta_description ?: ($product->short_description ?: $product->name.'. Báo giá sỉ cho doanh nghiệp, giao định kỳ, hóa đơn VAT.'))
@section('og_type', 'product')
@if ($product->mainImage())
    @section('og_image', $product->mainImage())
@endif

@push('schema')
{!! \App\Support\JsonLd::script(\App\Support\JsonLd::product($product)) !!}
@endpush

@section('content')
<div class="mx-auto max-w-site px-4 py-5">
    @include('partials.breadcrumb', ['items' => array_merge(
        $product->category
            ? $product->category->ancestors()->push($product->category)->map(fn ($c) => ['name' => $c->name, 'url' => $c->url()])->all()
            : [],
        [['name' => $product->name, 'url' => $product->url()]]
    )])

    <div class="grid gap-8 rounded-md bg-white p-6 md:grid-cols-2" x-data="{ img: 0, qty: {{ $product->min_order_qty }} }">
        <div>
            @php($images = collect($product->images ?? []))
            <div class="aspect-square rounded-lg bg-neutral-100">
                @if ($images->isNotEmpty())
                    @foreach ($images as $i => $src)
                        @php($srcUrl = \Illuminate\Support\Str::startsWith($src, ['http://', 'https://', '/']) ? $src : asset('storage/'.$src))
                        <img src="{{ $srcUrl }}" alt="{{ $product->name }}{{ $i ? ' – ảnh '.($i + 1) : '' }}"
                             width="600" height="600" class="h-full w-full object-contain p-4"
                             @if ($i) loading="lazy" x-cloak @endif x-show="img === {{ $i }}">
                    @endforeach
                @else
                    <div class="flex h-full items-center justify-center text-neutral-400">Chưa có ảnh</div>
                @endif
            </div>
            @if ($images->count() > 1)
                <div class="mt-3 flex gap-2">
                    @foreach ($images as $i => $src)
                        <button type="button" @click="img = {{ $i }}" class="h-16 w-16 rounded border" :class="img === {{ $i }} ? 'border-primary-600' : 'border-neutral-200'">
                            <img src="{{ \Illuminate\Support\Str::startsWith($src, ['http://', 'https://', '/']) ? $src : asset('storage/'.$src) }}" alt="" loading="lazy" class="h-full w-full object-contain">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <div>
            <h1 class="text-2xl font-bold text-ink-900">{{ $product->name }}</h1>
            <p class="mt-2 text-sm text-neutral-500">Mã: {{ $product->sku }}@if ($product->brand) · Thương hiệu: {{ $product->brand }}@endif</p>
            <div class="mt-4 flex flex-wrap items-baseline gap-3 rounded bg-neutral-50 px-4 py-3">
                <span class="text-3xl font-bold text-primary-600">{{ $product->price ? $product->formattedPrice() : 'Liên hệ báo giá' }}</span>
                @if ($product->price && $product->unit)<span class="text-neutral-500">/ {{ $product->unit }}</span>@endif
                @if ($product->hasDiscount())
                    <span class="text-neutral-400 line-through">{{ \App\Models\Product::money($product->compare_price) }}</span>
                    <span class="rounded bg-primary-50 px-1.5 py-0.5 text-sm font-medium text-primary-600">-{{ $product->discountPercent() }}%</span>
                @endif
            </div>
            @if ($product->price)<p class="text-xs text-neutral-500">Giá tham khảo chưa VAT. Đơn số lượng lớn / hợp đồng định kỳ có giá riêng.</p>@endif

            @if ($product->short_description)
                <p class="mt-4">{{ $product->short_description }}</p>
            @endif

            <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
                @if ($product->unit)<dt class="text-neutral-500">Đơn vị tính</dt><dd>{{ $product->unit }}</dd>@endif
                @if ($product->pack_size)<dt class="text-neutral-500">Quy cách</dt><dd>{{ $product->pack_size }}</dd>@endif
                @if ($product->min_order_qty > 1)<dt class="text-neutral-500">Đặt tối thiểu</dt><dd>{{ $product->min_order_qty }} {{ $product->unit }}</dd>@endif
            </dl>

            <div class="mt-6 flex items-center gap-3">
                <label for="qty" class="text-sm">Số lượng</label>
                <input id="qty" type="number" min="1" x-model.number="qty" class="w-24 rounded-lg border border-neutral-300 px-3 py-2">
                <button type="button" @click="$store.quote.add(@js($product->sku), qty)"
                        class="rounded-lg bg-primary-600 px-5 py-2.5 font-semibold text-white hover:bg-primary-700">
                    Thêm vào danh sách báo giá
                </button>
            </div>
            <p class="mt-2 text-sm" x-cloak x-show="$store.quote.has(@js($product->sku))">
                ✓ Đã có trong danh sách. <a href="{{ route('quote.create') }}" class="text-primary-600 underline">Gửi yêu cầu báo giá</a>
            </p>

            <ul class="mt-6 space-y-2 border-t border-neutral-200 pt-4 text-sm">
                <li class="flex items-center gap-2"><x-icon name="clipboard" class="h-5 w-5 text-primary-600" />Báo giá trong 4 giờ làm việc</li>
                <li class="flex items-center gap-2"><x-icon name="calendar" class="h-5 w-5 text-primary-600" />Giao định kỳ tại văn phòng, kho, khu công nghiệp</li>
                <li class="flex items-center gap-2"><x-icon name="receipt" class="h-5 w-5 text-primary-600" />Công nợ theo hợp đồng, hóa đơn VAT đầy đủ</li>
            </ul>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            @if ($product->description)
                <section class="prose-content rounded-md bg-white p-6">
                    <h2>Mô tả sản phẩm</h2>
                    {!! $product->description !!}
                </section>
            @endif
        </div>
        @if (! empty($product->specs))
            <section class="rounded-md bg-white p-6">
                <h2 class="mb-3 text-lg font-semibold text-ink-900">Thông số kỹ thuật</h2>
                <table class="w-full text-sm">
                    @foreach ($product->specs as $k => $v)
                        <tr class="border-b border-neutral-100"><th class="py-2 pr-3 text-left font-normal text-neutral-500">{{ $k }}</th><td class="py-2">{{ $v }}</td></tr>
                    @endforeach
                </table>
            </section>
        @endif
    </div>

    @if ($related->isNotEmpty())
        <section class="mt-8 overflow-hidden rounded-md bg-white">
            <h2 class="border-b border-neutral-200 bg-neutral-100 px-4 py-3 text-[17px] font-bold uppercase text-ink-900">Sản phẩm cùng danh mục</h2>
            <div class="grid grid-cols-2 gap-3 p-3 md:grid-cols-3 lg:grid-cols-5">
                @foreach ($related as $item)
                    @include('partials.product-card', ['product' => $item])
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
