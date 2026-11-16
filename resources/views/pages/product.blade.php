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
<div class="mx-auto max-w-7xl px-4 py-8">
    @include('partials.breadcrumb', ['items' => array_merge(
        $product->category
            ? $product->category->ancestors()->push($product->category)->map(fn ($c) => ['name' => $c->name, 'url' => $c->url()])->all()
            : [],
        [['name' => $product->name, 'url' => $product->url()]]
    )])

    <div class="grid gap-8 rounded-xl bg-white p-6 md:grid-cols-2" x-data="{ img: 0, qty: {{ $product->min_order_qty }} }">
        <div>
            @php($images = collect($product->images ?? []))
            <div class="aspect-square rounded-lg bg-slate-100">
                @if ($images->isNotEmpty())
                    @foreach ($images as $i => $src)
                        @php($srcUrl = \Illuminate\Support\Str::startsWith($src, ['http://', 'https://', '/']) ? $src : asset('storage/'.$src))
                        <img src="{{ $srcUrl }}" alt="{{ $product->name }}{{ $i ? ' – ảnh '.($i + 1) : '' }}"
                             width="600" height="600" class="h-full w-full object-contain p-4"
                             @if ($i) loading="lazy" x-cloak @endif x-show="img === {{ $i }}">
                    @endforeach
                @else
                    <div class="flex h-full items-center justify-center text-slate-400">Chưa có ảnh</div>
                @endif
            </div>
            @if ($images->count() > 1)
                <div class="mt-3 flex gap-2">
                    @foreach ($images as $i => $src)
                        <button type="button" @click="img = {{ $i }}" class="h-16 w-16 rounded border" :class="img === {{ $i }} ? 'border-brand-600' : 'border-slate-200'">
                            <img src="{{ \Illuminate\Support\Str::startsWith($src, ['http://', 'https://', '/']) ? $src : asset('storage/'.$src) }}" alt="" loading="lazy" class="h-full w-full object-contain">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <div>
            <h1 class="text-2xl font-bold text-slate-900">{{ $product->name }}</h1>
            <p class="mt-2 text-sm text-slate-500">Mã: {{ $product->sku }}@if ($product->brand) · Thương hiệu: {{ $product->brand }}@endif</p>
            <p class="mt-4 text-2xl font-bold text-brand-700">{{ $product->formattedPrice() }}@if ($product->price && $product->unit)<span class="text-base font-normal text-slate-500"> / {{ $product->unit }}</span>@endif</p>
            @if ($product->price)<p class="text-xs text-slate-500">Giá tham khảo chưa VAT. Đơn số lượng lớn / hợp đồng định kỳ có giá riêng.</p>@endif

            @if ($product->short_description)
                <p class="mt-4">{{ $product->short_description }}</p>
            @endif

            <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
                @if ($product->unit)<dt class="text-slate-500">Đơn vị tính</dt><dd>{{ $product->unit }}</dd>@endif
                @if ($product->pack_size)<dt class="text-slate-500">Quy cách</dt><dd>{{ $product->pack_size }}</dd>@endif
                @if ($product->min_order_qty > 1)<dt class="text-slate-500">Đặt tối thiểu</dt><dd>{{ $product->min_order_qty }} {{ $product->unit }}</dd>@endif
            </dl>

            <div class="mt-6 flex items-center gap-3">
                <label for="qty" class="text-sm">Số lượng</label>
                <input id="qty" type="number" min="1" x-model.number="qty" class="w-24 rounded-lg border border-slate-300 px-3 py-2">
                <button type="button" @click="$store.quote.add(@js($product->sku), qty)"
                        class="rounded-lg bg-accent-500 px-5 py-2.5 font-semibold text-slate-900 hover:bg-accent-600">
                    Thêm vào danh sách báo giá
                </button>
            </div>
            <p class="mt-2 text-sm" x-cloak x-show="$store.quote.has(@js($product->sku))">
                ✓ Đã có trong danh sách. <a href="{{ route('quote.create') }}" class="text-brand-600 underline">Gửi yêu cầu báo giá</a>
            </p>

            <ul class="mt-6 space-y-1 rounded-lg bg-brand-50 p-4 text-sm">
                <li>✔ Báo giá trong 4 giờ làm việc</li>
                <li>✔ Giao định kỳ tại các khu công nghiệp</li>
                <li>✔ Công nợ theo hợp đồng, hóa đơn VAT đầy đủ</li>
            </ul>
        </div>
    </div>

    <div class="mt-8 grid gap-8 lg:grid-cols-3">
        <div class="lg:col-span-2">
            @if ($product->description)
                <section class="prose-content rounded-xl bg-white p-6">
                    <h2>Mô tả sản phẩm</h2>
                    {!! $product->description !!}
                </section>
            @endif
        </div>
        @if (! empty($product->specs))
            <section class="rounded-xl bg-white p-6">
                <h2 class="mb-3 text-lg font-semibold text-slate-900">Thông số kỹ thuật</h2>
                <table class="w-full text-sm">
                    @foreach ($product->specs as $k => $v)
                        <tr class="border-b border-slate-100"><th class="py-2 pr-3 text-left font-normal text-slate-500">{{ $k }}</th><td class="py-2">{{ $v }}</td></tr>
                    @endforeach
                </table>
            </section>
        @endif
    </div>

    @if ($related->isNotEmpty())
        <section class="mt-12">
            <h2 class="mb-4 text-xl font-bold text-slate-900">Sản phẩm cùng danh mục</h2>
            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                @foreach ($related as $product)
                    @include('partials.product-card')
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
