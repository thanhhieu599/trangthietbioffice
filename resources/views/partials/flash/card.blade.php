{{-- Thẻ sản phẩm Flash Sale. Biến: $item (FlashSaleItem, đã có ->product và ->sold) --}}
@php
$p = $item->product;
$pct = $item->soldPercent();
$hot = $item->quantity_limit && $pct >= 80;
@endphp
<article
    class="group relative flex h-full flex-col rounded-xl bg-white p-2.5 shadow-[0_1px_3px_rgba(0,0,0,.08)]">
    <a href="{{ $p->url() }}" class="relative block aspect-square overflow-hidden rounded-lg bg-neutral-50"
        tabindex="-1" aria-label="{{ $p->name }}">
        @if ($img = $p->mainImage())
        <img src="{{ $img }}" alt="{{ $p->name }}" width="300" height="300" loading="lazy" decoding="async"
            class="h-full w-full object-contain p-2">
        @else
        <span class="flex h-full items-center justify-center text-neutral-300">
            <x-icon name="image" class="h-12 w-12" />
        </span>
        @endif
        <span
            class="absolute top-2 left-2 inline-flex items-center gap-0.5 rounded-md bg-[#ffd400] px-1.5 py-0.5 text-xs font-extrabold text-primary-700 shadow-sm">
            <svg class="h-3 w-2.5" viewBox="0 0 20 30" aria-hidden="true">
                <path d="M12.5 0 1 17h7l-2.5 13L19 11h-7.5L12.5 0z" fill="currentColor" />
            </svg>-{{ $item->discountPercent() }}%
        </span>
    </a>

    <h3 class="mt-2.5 line-clamp-2 min-h-[2.6em] text-[14px] leading-[1.3] font-semibold text-ink-900">
        <a href="{{ $p->url() }}">{{ $p->name }}</a>
    </h3>

    <div class="mt-1.5 flex items-center gap-2" x-data="{ added: false }">
        <div class="flex min-w-0 flex-1 flex-wrap items-baseline gap-x-2">
            <span class="text-[17px] leading-tight font-bold text-primary-600">{{ \App\Models\Product::money((int) $item->sale_price) }}</span>
            <span class="text-[12.5px] text-neutral-400 line-through">{{ \App\Models\Product::money((int) $p->price) }}</span>
        </div>
        @if ($p->attrList())
            {{-- Có màu sắc / phân loại: mở trang sản phẩm để chọn --}}
            <a href="{{ $p->url() }}" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-primary-600 text-white transition hover:bg-primary-700"
               title="Chọn phân loại" aria-label="Chọn phân loại {{ $p->name }}"><x-icon name="cart" class="h-[18px] w-[18px]" /></a>
        @else
            {{-- Icon thêm vào giỏ hàng (số lượng tối thiểu) --}}
            <button type="button" @click="$store.quote.add(@js($p->sku), {{ max(1, (int) $p->min_order_qty) }}); added = true; setTimeout(() => added = false, 1500)"
                    :class="added ? 'bg-green-600' : 'bg-primary-600 hover:bg-primary-700'"
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-primary-600 text-white transition"
                    title="Thêm vào giỏ hàng" aria-label="Thêm {{ $p->name }} vào giỏ hàng">
                <x-icon name="cart" class="h-[18px] w-[18px]" x-show="!added" />
                <x-icon name="check-circle" class="h-[18px] w-[18px]" x-show="added" x-cloak />
            </button>
        @endif
    </div>

    <div class="relative mt-2.5 h-[18px] overflow-hidden rounded-full bg-[#ffe1db]" title="{{ $item->soldLabel() }}">
        {{-- Dùng @style (không viết style="...{{ }}..."): tránh VS Code báo lỗi CSS giả và formatter làm hỏng --}}
        <div class="absolute inset-y-0 left-0 rounded-full bg-gradient-to-r from-[#ff9a3c] to-primary-600"
            @style(['width: '.$pct.'%'])></div>
        {{-- Chữ đổi màu theo độ đầy của thanh để luôn dễ đọc --}}
        <span
            class="relative flex h-full items-center justify-center gap-1 text-[10.5px] font-bold uppercase tracking-wide {{ $pct >= 50 ? 'text-white [text-shadow:0_1px_1px_rgba(0,0,0,.3)]' : 'text-primary-800' }}">
            @if ($hot)<svg class="h-3 w-3" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path
                    d="M13.5 2s.5 3-2 6-4 4.5-4 8a6.5 6.5 0 0 0 13 0c0-3-1.5-5-3-6.5 0 2-1 3-2 3.5.5-3.5-1-7.5-2-11z" />
            </svg>@endif
            {{ $item->soldLabel() }}
        </span>
    </div>
</article>