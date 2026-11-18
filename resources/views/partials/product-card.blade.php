{{-- Thẻ sản phẩm: ảnh (nhãn % giảm góc trái) / tên đậm / giá bán + giá gốc cùng hàng / số lượng + icon giỏ hàng --}}
@php
    $cardFlash = $product->flashItem();
    $salePrice = $cardFlash ? (int) $cardFlash->sale_price : ($product->price ? (int) $product->price : null);
    $oldPrice = $cardFlash ? (int) $product->price : ($product->hasDiscount() ? (int) $product->compare_price : null);
    $percent = $cardFlash ? $cardFlash->discountPercent() : $product->discountPercent();
    $cardAttrs = $product->attrList();
    $cardColors = collect($cardAttrs)->first(fn ($a) => \App\Models\Product::isColorAttr($a['name']))['values'] ?? [];
@endphp
<article class="flex h-full flex-col rounded-lg border border-neutral-200 bg-white p-2.5 sm:p-3">
    <a href="{{ $product->url() }}" class="relative block aspect-square overflow-hidden rounded-md" aria-label="{{ $product->name }}" tabindex="-1">
        @if ($product->mainImage())
            <img src="{{ $product->mainImage() }}" alt="{{ $product->name }}" loading="lazy" decoding="async"
                 width="300" height="300" class="h-full w-full object-contain">
        @else
            <span class="flex h-full w-full items-center justify-center bg-neutral-50 text-neutral-300">
                <x-icon name="image" class="h-12 w-12" />
            </span>
        @endif

        @if ($percent > 0)
            @if ($cardFlash)
                <span class="absolute top-1.5 left-1.5 inline-flex items-center gap-0.5 rounded bg-[#ffd400] px-1.5 py-0.5 text-xs font-extrabold text-primary-700 shadow-sm">
                    <svg class="h-2.5 w-2" viewBox="0 0 20 30" aria-hidden="true"><path d="M12.5 0 1 17h7l-2.5 13L19 11h-7.5L12.5 0z" fill="currentColor"/></svg>-{{ $percent }}%
                </span>
            @else
                <span class="absolute top-1.5 left-1.5 rounded bg-primary-600 px-1.5 py-0.5 text-xs font-bold text-white shadow-sm">-{{ $percent }}%</span>
            @endif
        @endif
    </a>

    <h3 class="mt-2.5 line-clamp-2 min-h-[2.6em] text-[14px] leading-[1.3] font-semibold text-ink-900">
        <a href="{{ $product->url() }}">{{ $product->name }}</a>
    </h3>

    <div class="mt-1.5 flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
        @if ($salePrice)
            <span class="text-[17px] leading-tight font-bold text-primary-600">{{ \App\Models\Product::money($salePrice) }}</span>
            @if ($oldPrice)<span class="text-[12.5px] text-neutral-400 line-through">{{ \App\Models\Product::money($oldPrice) }}</span>@endif
        @else
            <span class="text-[16px] leading-tight font-bold text-primary-600">Liên hệ</span>
        @endif
    </div>

    @if ($cardColors)
        {{-- Gợi ý màu có sẵn (chọn màu ở trang sản phẩm) --}}
        <div class="mt-1.5 flex items-center gap-1" title="{{ implode(', ', $cardColors) }}">
            @foreach (array_slice($cardColors, 0, 6) as $c)
                <span class="h-3.5 w-3.5 rounded-full border border-black/10" @style(['background-color: '.(\App\Support\Colors::hex($c) ?? '#d1d5db')])></span>
            @endforeach
            @if (count($cardColors) > 6)<span class="text-xs text-neutral-500">+{{ count($cardColors) - 6 }}</span>@endif
        </div>
    @endif

    @if ($salePrice)
        {{-- Có giá: chọn số lượng (−/+ hoặc tự nhập) + icon thêm vào giỏ --}}
        @php($min = max(1, (int) $product->min_order_qty))
        <div class="mt-auto flex items-stretch gap-1.5 pt-3" x-data="{ qty: {{ $min }}, min: {{ $min }}, added: false }">
            <div class="flex h-9 min-w-0 flex-1 items-stretch overflow-hidden rounded-md border border-neutral-300">
                <button type="button" @click="qty = Math.max(min, qty - 1)" class="w-7 shrink-0 text-neutral-600 hover:bg-neutral-100" aria-label="Giảm số lượng">−</button>
                <input type="number" inputmode="numeric" x-model.number="qty" :min="min" @blur="qty = Math.max(min, parseInt(qty) || min)"
                       class="w-full min-w-0 border-x border-neutral-300 text-center text-sm [appearance:textfield] focus:outline-none [&::-webkit-inner-spin-button]:appearance-none"
                       aria-label="Số lượng {{ $product->name }}">
                <button type="button" @click="qty++" class="w-7 shrink-0 text-neutral-600 hover:bg-neutral-100" aria-label="Tăng số lượng">+</button>
            </div>
            @if ($cardAttrs)
                {{-- Có màu sắc / phân loại: mở trang sản phẩm để khách chọn trước khi thêm vào giỏ --}}
                <a href="{{ $product->url() }}" class="flex h-9 w-10 shrink-0 items-center justify-center rounded-md bg-primary-600 text-white transition hover:bg-primary-700"
                   title="Chọn {{ mb_strtolower(implode(', ', array_column($cardAttrs, 'name'))) }}" aria-label="Chọn phân loại {{ $product->name }}">
                    <x-icon name="cart" class="h-5 w-5" />
                </a>
            @else
                <button type="button"
                        @click="$store.quote.add(@js($product->sku), Math.max(min, parseInt(qty) || min)); added = true; setTimeout(() => added = false, 1500)"
                        :class="added ? 'bg-green-600' : 'bg-primary-600 hover:bg-primary-700'"
                        class="flex h-9 w-10 shrink-0 items-center justify-center rounded-md bg-primary-600 text-white transition"
                        title="Thêm vào giỏ hàng" aria-label="Thêm {{ $product->name }} vào giỏ hàng">
                    <x-icon name="cart" class="h-5 w-5" x-show="!added" />
                    <x-icon name="check-circle" class="h-5 w-5" x-show="added" x-cloak />
                </button>
            @endif
        </div>
    @else
        {{-- Chưa có giá: nút báo giá thay cho ô số lượng + giỏ hàng --}}
        <div class="mt-auto pt-3 @container">
            <a href="{{ route('contact', ['san-pham' => $product->sku]) }}#gui-tin-nhan"
               class="flex h-9 items-center justify-center gap-1.5 rounded-md border border-primary-600 px-2 text-[13px] font-semibold whitespace-nowrap text-primary-600 transition hover:bg-primary-600 hover:text-white"
               aria-label="Liên hệ báo giá {{ $product->name }}">
                <x-icon name="clipboard" class="h-4 w-4 shrink-0" />
                <span class="hidden @[170px]:inline">Liên hệ báo giá</span><span class="@[170px]:hidden">Báo giá</span>
            </a>
        </div>
    @endif
</article>
