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

@php
    $images = collect($product->images ?? [])->map(fn ($src) => \Illuminate\Support\Str::startsWith($src, ['http://', 'https://', '/']) ? $src : asset('storage/'.$src))->values();
    $shareUrl = urlencode($product->url());
    $shareText = urlencode($product->name);
    $stockTone = ['in_stock' => 'text-green-600', 'out_of_stock' => 'text-red-600', 'preorder' => 'text-amber-600'][$product->stock_status] ?? 'text-neutral-700';
    $minQty = max(1, (int) $product->min_order_qty);
@endphp

@section('content')
<div class="mx-auto max-w-site px-4 py-5">
    @include('partials.breadcrumb', ['items' => array_merge(
        $product->category
            ? $product->category->ancestors()->push($product->category)->map(fn ($c) => ['name' => $c->name, 'url' => $c->url()])->all()
            : [],
        [['name' => $product->name, 'url' => $product->url()]]
    )])

    <div class="grid gap-8 rounded-md bg-white p-4 sm:p-6 md:grid-cols-[minmax(0,1fr)_minmax(0,1.15fr)]"
         x-data="{ img: 0, n: {{ $images->count() }}, qty: {{ $minQty }}, min: {{ $minQty }},
                   go(k) { this.img = (k + this.n) % this.n; this.$refs.thumbs?.children[this.img]?.scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: 'smooth' }) } }">

        {{-- ===== Ảnh sản phẩm ===== --}}
        <div class="min-w-0">
            <div class="group relative aspect-square overflow-hidden rounded-md border border-neutral-200 bg-white">
                @forelse ($images as $i => $src)
                    <img src="{{ $src }}" alt="{{ $product->name }}{{ $i ? ' – ảnh '.($i + 1) : '' }}"
                         width="600" height="600" class="absolute inset-0 h-full w-full object-contain p-3"
                         @if ($i) loading="lazy" x-cloak @else fetchpriority="high" @endif x-show="img === {{ $i }}">
                @empty
                    <div class="flex h-full flex-col items-center justify-center gap-2 bg-neutral-50 text-neutral-400">
                        <x-icon name="image" class="h-14 w-14" />
                        <span class="text-sm">Chưa có ảnh</span>
                    </div>
                @endforelse

                @if ($images->count() > 1)
                    <button type="button" @click="go(img - 1)" aria-label="Ảnh trước"
                            class="absolute top-1/2 left-2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/80 text-ink-800 shadow transition hover:bg-white">
                        <x-icon name="chevron-left" />
                    </button>
                    <button type="button" @click="go(img + 1)" aria-label="Ảnh sau"
                            class="absolute top-1/2 right-2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/80 text-ink-800 shadow transition hover:bg-white">
                        <x-icon name="chevron-right" />
                    </button>
                @endif
            </div>

            @if ($images->count() > 1)
                <div class="no-scrollbar mt-3 flex gap-2 overflow-x-auto" x-ref="thumbs">
                    @foreach ($images as $i => $src)
                        <button type="button" @click="go({{ $i }})" aria-label="Xem ảnh {{ $i + 1 }}"
                                class="h-[72px] w-[72px] shrink-0 overflow-hidden rounded border-2 bg-white p-0.5 transition"
                                :class="img === {{ $i }} ? 'border-primary-600' : 'border-neutral-200 hover:border-neutral-400'">
                            <img src="{{ $src }}" alt="" loading="lazy" width="72" height="72" class="h-full w-full object-contain">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ===== Thông tin mua hàng ===== --}}
        @php
            // Thuộc tính + phiên bản cho bộ chọn (màu sắc = chấm màu, thuộc tính khác = nút)
            $attrs = collect($product->attrList())->map(fn ($a) => $a + ['color' => \App\Models\Product::isColorAttr($a['name'])])->all();
            $variants = collect($product->variantList())->map(fn ($v) => [
                'sku' => $v['sku'], 'options' => (object) ($v['options'] ?? []), 'price' => $v['price'] ?? null,
                'stock' => $v['stock_status'] ?? $product->stock_status,
            ])->all();
        @endphp
        <div class="min-w-0" x-data="productOptions(@js([
            'sku' => $product->sku, 'attrs' => $attrs, 'variants' => $variants,
            'colors' => \App\Support\Colors::all(), 'stock' => $product->stock_status,
            'stocks' => collect(\App\Models\Product::STOCK)->map(fn ($s) => $s['label']),
        ]))">
            <h1 class="text-xl font-bold leading-snug text-ink-900 md:text-2xl">{{ $product->name }}</h1>

            <dl class="mt-3 space-y-1 text-sm">
                <div><dt class="inline text-neutral-500">Mã sản phẩm:</dt> <dd class="inline font-semibold text-ink-900" x-text="currentSku">{{ $product->sku }}</dd></div>
                <div><dt class="inline text-neutral-500">Tình trạng:</dt> <dd class="inline font-semibold {{ $stockTone }}" :class="stockClass" x-text="stockText">{{ $product->stockLabel() }}</dd></div>
                @if ($unitLabel = $product->unitLabel())
                    <div><dt class="inline text-neutral-500">ĐVT:</dt> <dd class="inline font-semibold text-ink-900">{{ $unitLabel }}</dd></div>
                @endif
                @if ($product->brand)
                    <div><dt class="inline text-neutral-500">Thương hiệu:</dt>
                        <dd class="inline"><a href="{{ route('brands.show', \Illuminate\Support\Str::slug($product->brand)) }}" class="font-semibold text-primary-600 hover:underline">{{ $product->brand }}</a></dd></div>
                @endif
            </dl>

            {{-- Thuộc tính: "Màu sắc" hiện chấm màu, thuộc tính khác hiện nút. Có phiên bản Sapo thì mã / tình trạng đổi theo lựa chọn. --}}
            @foreach ($attrs as $a)
                <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2">
                    <span class="min-w-16 text-sm font-semibold text-ink-900">{{ $a['name'] }}</span>
                    <ul class="flex flex-wrap items-center gap-2.5" role="radiogroup" aria-label="{{ $a['name'] }}">
                        @foreach ($a['values'] as $val)
                            <li>
                                @if ($a['color'])
                                    @php($hex = \App\Support\Colors::hex($val) ?? '#d1d5db')
                                    <button type="button" role="radio" @click="pick(@js($a['name']), @js($val))"
                                            :aria-checked="sel[@js($a['name'])] === @js($val)" title="{{ $val }}" aria-label="{{ $a['name'] }} {{ $val }}"
                                            :class="sel[@js($a['name'])] === @js($val) ? 'ring-2 ring-primary-600' : 'hover:ring-2 hover:ring-neutral-300'"
                                            class="relative block h-9 w-9 rounded-full border border-black/10 ring-offset-2 transition" @style(['background-color: '.$hex])>
                                        <span x-show="!available(@js($a['name']), @js($val))" x-cloak class="absolute inset-0 flex items-center justify-center" aria-hidden="true"><span class="h-px w-8 rotate-45 bg-neutral-600/80"></span></span>
                                    </button>
                                @else
                                    <button type="button" role="radio" @click="pick(@js($a['name']), @js($val))"
                                            :aria-checked="sel[@js($a['name'])] === @js($val)"
                                            :class="sel[@js($a['name'])] === @js($val) ? 'border-primary-600 bg-primary-50 font-semibold text-primary-700' : 'border-neutral-300 text-ink-800 hover:border-neutral-400'"
                                            class="rounded-md border px-3 py-1.5 text-sm transition" :style="available(@js($a['name']), @js($val)) ? '' : 'opacity:.45;text-decoration:line-through'">{{ $val }}</button>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                    @if ($a['color'])<span class="text-sm text-neutral-600" x-text="sel[@js($a['name'])]">{{ $a['values'][0] }}</span>@endif
                </div>
            @endforeach

            @if ($flashItem = $product->flashItem())
                {{-- Đang Flash Sale: dải đếm ngược + giá flash --}}
                <div class="mt-4 overflow-hidden rounded-md ring-1 ring-primary-100" x-data="countdown(@js($flashItem->flashSale->ends_at->toIso8601String()), {{ $flashItem->flashSale->cycleSeconds() }})">
                    <a href="{{ route('flash-sale') }}" class="flex flex-wrap items-center justify-between gap-2 bg-[linear-gradient(110deg,#9a1f25,#d9573a)] px-4 py-2 text-white">
                        <span class="inline-flex items-center gap-1.5 text-lg font-black italic tracking-tight">
                            FLASH <svg class="h-5 w-3.5" viewBox="0 0 20 30" aria-hidden="true"><path d="M12.5 0 1 17h7l-2.5 13L19 11h-7.5L12.5 0z" fill="#ffd400"/></svg> SALE
                        </span>
                        <span class="flex items-center gap-2 text-xs font-medium">
                            <span x-text="done ? 'Đã kết thúc' : 'Kết thúc trong'">Kết thúc trong</span>
                            <span x-show="!done">@include('partials.flash.countdown', ['dark' => true])</span>
                        </span>
                    </a>
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-2 bg-[#fff4f2] px-4 py-4">
                        <span class="w-16 text-sm text-neutral-600">Giá:</span>
                        <span class="text-2xl font-bold text-primary-600 md:text-3xl">{{ \App\Models\Product::money((int) $flashItem->sale_price) }}</span>
                        <span class="text-neutral-400 line-through">{{ $product->formattedPrice() }}</span>
                        <span class="rounded bg-[#ffd400] px-1.5 py-0.5 text-xs font-extrabold text-primary-700">-{{ $flashItem->discountPercent() }}%</span>
                        @if ($product->unit)<span class="text-sm text-neutral-500">/ {{ $product->unit }}</span>@endif
                    </div>
                </div>
            @elseif ($product->price)
                {{-- Chưa có giá: không hiện khung giá (nút "Liên hệ báo giá" bên dưới đã thay thế) --}}
                <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 rounded-md bg-neutral-50 px-4 py-4">
                    <span class="w-16 text-sm text-neutral-600">Giá:</span>
                    <span class="text-2xl font-bold text-primary-600 md:text-3xl">{{ $product->formattedPrice() }}</span>
                    @if ($product->hasDiscount())
                        <span class="text-neutral-400 line-through">{{ \App\Models\Product::money($product->compare_price) }}</span>
                        <span class="rounded border border-primary-600 px-1.5 py-0.5 text-xs font-semibold text-primary-600">-{{ $product->discountPercent() }}%</span>
                    @endif
                    @if ($product->unit)<span class="text-sm text-neutral-500">/ {{ $product->unit }}</span>@endif
                </div>
            @endif
            @if ($product->price)
                <p class="mt-1.5 text-xs text-neutral-500">Giá tham khảo chưa VAT.</p>
            @endif

            <div class="mt-5 flex items-center gap-4">
                <span class="w-16 text-sm text-neutral-600">Số lượng:</span>
                <div class="flex items-center rounded border border-neutral-300">
                    <button type="button" @click="qty = Math.max(min, qty - 1)" class="flex h-10 w-10 items-center justify-center text-lg text-neutral-600 hover:bg-neutral-100" aria-label="Giảm số lượng">−</button>
                    <input type="number" x-model.number="qty" :min="min" @blur="qty = Math.max(min, parseInt(qty) || min)"
                           class="h-10 w-14 border-x border-neutral-300 text-center [appearance:textfield] focus:outline-none [&::-webkit-inner-spin-button]:appearance-none" aria-label="Số lượng">
                    <button type="button" @click="qty++" class="flex h-10 w-10 items-center justify-center text-lg text-neutral-600 hover:bg-neutral-100" aria-label="Tăng số lượng">+</button>
                </div>
                @if ($minQty > 1)<span class="text-xs text-neutral-500">Tối thiểu {{ $minQty }} {{ $product->unit }}</span>@endif
            </div>

            <div class="mt-6 grid grid-cols-2 gap-3">
                <button type="button" @click="addToCart(qty)"
                        class="rounded border border-primary-600 px-4 py-3 text-sm font-semibold uppercase tracking-wide text-primary-600 transition hover:bg-primary-50">
                    Thêm vào giỏ
                </button>
                @if ($product->price || $flashItem)
                    <button type="button" @click="addToCart(qty); window.location = @js(route('quote.create'))"
                            class="rounded bg-primary-600 px-4 py-3 text-sm font-semibold uppercase tracking-wide text-white transition hover:bg-primary-700">
                        Mua ngay
                    </button>
                @else
                    {{-- Chưa có giá: chuyển sang form trang Liên hệ, điền sẵn tên + mã + số lượng sản phẩm --}}
                    <a href="{{ route('contact', ['san-pham' => $product->sku]) }}#gui-tin-nhan"
                       :href="@js(route('contact')) + '?san-pham=' + encodeURIComponent(currentSku) + '&so-luong=' + qty + (optsLabel ? '&tuy-chon=' + encodeURIComponent(optsLabel) : '') + '#gui-tin-nhan'"
                       class="flex items-center justify-center rounded bg-primary-600 px-4 py-3 text-center text-sm font-semibold uppercase tracking-wide text-white transition hover:bg-primary-700">
                        Liên hệ báo giá
                    </a>
                @endif
            </div>
            <p class="mt-2 text-sm text-green-700" x-cloak x-show="$store.quote.has(currentSku)">
                ✓ Đã có trong giỏ hàng. <a href="{{ route('quote.create') }}" class="font-semibold underline">Xem giỏ hàng</a>
            </p>

            {{-- Chia sẻ --}}
            <div class="mt-5 flex flex-wrap items-center gap-2 text-sm" x-data="{ copied: false }">
                <span class="mr-1 text-neutral-600">Chia sẻ:</span>
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}" target="_blank" rel="noopener nofollow" aria-label="Chia sẻ lên Facebook"
                   class="flex h-8 w-8 items-center justify-center rounded-full bg-[#1877F2] text-white hover:opacity-90">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M14 8h3V4h-3c-2.8 0-4.5 1.8-4.5 4.6V11H7v4h2.5v9h4v-9H17l.5-4h-4V8.8c0-.5.3-.8.8-.8z"/></svg>
                </a>
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}" target="_blank" rel="noopener nofollow" aria-label="Gửi qua Messenger"
                   @click="if (/Android|iPhone|iPad/i.test(navigator.userAgent)) { $event.preventDefault(); location.href = 'fb-messenger://share/?link={{ $shareUrl }}' }"
                   class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-[#00B2FF] to-[#006AFF] text-white hover:opacity-90">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2C6.4 2 2 6.1 2 11.3c0 2.9 1.4 5.5 3.7 7.2V22l3.4-1.9c.9.3 1.9.4 2.9.4 5.6 0 10-4.1 10-9.2S17.6 2 12 2zm1 12.4-2.5-2.7-5 2.7 5.5-5.8 2.6 2.7 4.9-2.7L13 14.4z"/></svg>
                </a>
                <a href="https://twitter.com/intent/tweet?url={{ $shareUrl }}&text={{ $shareText }}" target="_blank" rel="noopener nofollow" aria-label="Chia sẻ lên X (Twitter)"
                   class="flex h-8 w-8 items-center justify-center rounded-full bg-[#1DA1F2] text-white hover:opacity-90">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M22 5.9c-.7.3-1.5.5-2.4.6.9-.5 1.5-1.3 1.8-2.3-.8.5-1.7.8-2.6 1a4.1 4.1 0 0 0-7 3.7A11.6 11.6 0 0 1 3.4 4.6a4.1 4.1 0 0 0 1.3 5.5c-.7 0-1.3-.2-1.9-.5 0 2 1.4 3.7 3.3 4.1-.6.2-1.2.2-1.9.1.5 1.6 2 2.8 3.8 2.9A8.3 8.3 0 0 1 2 18.4 11.7 11.7 0 0 0 8.3 20c7.5 0 11.7-6.3 11.7-11.7v-.5c.8-.6 1.5-1.3 2-2z"/></svg>
                </a>
                <a href="https://pinterest.com/pin/create/button/?url={{ $shareUrl }}&description={{ $shareText }}{{ $product->mainImage() ? '&media='.urlencode(\App\Support\JsonLd::absolute($product->mainImage())) : '' }}"
                   target="_blank" rel="noopener nofollow" aria-label="Lưu lên Pinterest"
                   class="flex h-8 w-8 items-center justify-center rounded-full bg-[#E60023] text-white hover:opacity-90">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.2 2C6.7 2 4 5.9 4 9.2c0 2 .8 3.7 2.4 4.4.3.1.5 0 .6-.3l.2-.9c.1-.3 0-.4-.2-.7-.5-.6-.8-1.3-.8-2.4 0-3.1 2.3-5.8 6-5.8 3.3 0 5.1 2 5.1 4.7 0 3.5-1.6 6.5-3.9 6.5-1.3 0-2.2-1-1.9-2.3.4-1.5 1.1-3.2 1.1-4.3 0-1-.5-1.8-1.6-1.8-1.3 0-2.3 1.3-2.3 3.1 0 1.1.4 1.9.4 1.9l-1.5 6.3c-.4 1.9-.1 4.1 0 4.3 0 .1.2.2.3.1.1-.1 1.4-1.8 1.9-3.4l.7-2.8c.4.7 1.4 1.3 2.6 1.3 3.4 0 5.7-3.1 5.7-7.3C20 4.9 16.7 2 12.2 2z"/></svg>
                </a>
                <button type="button" aria-label="Sao chép liên kết"
                        @click="navigator.clipboard?.writeText(@js($product->url())).then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                        class="flex h-8 w-8 items-center justify-center rounded-full bg-neutral-400 text-white hover:bg-neutral-500">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/></svg>
                </button>
                <span x-show="copied" x-cloak x-transition.opacity class="text-xs text-green-700">Đã sao chép liên kết</span>
            </div>
        </div>
    </div>

    {{-- ===== Mô tả sản phẩm (soạn trong trang quản trị: viết chữ, chèn ảnh, bảng…) ===== --}}
    <section class="mt-6 rounded-md bg-white p-4 sm:p-6">
        <h2 class="inline-block border-b-2 border-primary-600 pb-2 text-base font-bold uppercase tracking-wide text-ink-900">Mô tả sản phẩm</h2>
        <div class="-mt-px border-t border-neutral-200 pt-5">
            @if ($product->short_description)
                <p class="mb-4 font-medium text-ink-800">{{ $product->short_description }}</p>
            @endif

            @if ($product->description)
                <div class="prose-content">{!! $product->description !!}</div>
            @elseif (! $product->short_description)
                <p class="text-neutral-500">Mô tả sản phẩm đang được cập nhật. Cần thông tin chi tiết, vui lòng <a href="{{ route('quote.create') }}" class="text-primary-600 underline">gửi yêu cầu báo giá</a>.</p>
            @endif

            @if (! empty($product->specs))
                <h3 class="mt-8 mb-3 text-lg font-semibold text-ink-900">Thông số kỹ thuật</h3>
                <table class="w-full max-w-2xl text-sm">
                    @foreach ($product->specs as $k => $v)
                        <tr class="border-b border-neutral-100"><th class="w-2/5 py-2 pr-3 text-left font-normal text-neutral-500">{{ $k }}</th><td class="py-2">{{ $v }}</td></tr>
                    @endforeach
                </table>
            @endif
        </div>
    </section>

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
