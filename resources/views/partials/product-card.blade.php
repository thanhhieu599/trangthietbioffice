{{-- Thẻ sản phẩm: chỉ 1 lớp viền, không lồng khung – theo bố cục ảnh / tên / giá / thao tác --}}
<article class="group flex h-full flex-col rounded-lg border border-neutral-200 bg-white p-3 transition hover:border-primary-600 hover:shadow-md">
    <a href="{{ $product->url() }}" class="block aspect-square">
        @if ($product->mainImage())
            <img src="{{ $product->mainImage() }}" alt="{{ $product->name }}" loading="lazy" decoding="async"
                 width="300" height="300" class="h-full w-full object-contain transition group-hover:scale-[1.03]">
        @else
            <span class="flex h-full w-full items-center justify-center bg-neutral-50 text-neutral-300">
                <x-icon name="image" class="h-12 w-12" />
            </span>
        @endif
    </a>

    <h3 class="mt-3 line-clamp-2 min-h-[2.75rem] text-[14px] leading-snug text-ink-800">
        <a href="{{ $product->url() }}" class="hover:text-primary-600">{{ $product->name }}</a>
    </h3>

    <div class="mt-2 flex min-h-5 items-center gap-2 text-[13px]">
        @if ($product->hasDiscount())
            <span class="text-neutral-400 line-through">{{ \App\Models\Product::money($product->compare_price) }}</span>
            <span class="rounded bg-primary-50 px-1.5 py-0.5 font-medium text-primary-600">-{{ $product->discountPercent() }}%</span>
        @endif
    </div>
    <p class="text-lg font-bold text-primary-600">{{ $product->formattedPrice() }}</p>

    <div class="mt-auto flex items-center justify-between pt-2 text-[13px]">
        <span class="hidden text-neutral-500 sm:inline">{{ $product->unit ? 'ĐVT: '.$product->unit : '' }}</span>
        <button type="button" x-data class="ml-auto flex items-center gap-1 font-medium text-primary-600 hover:text-primary-700"
                @click="$store.quote.add(@js($product->sku), {{ $product->min_order_qty }})"
                aria-label="Thêm {{ $product->name }} vào báo giá">
            <span x-show="!$store.quote.has(@js($product->sku))"><x-icon name="plus-circle" class="h-4 w-4" /></span>
            <span x-show="$store.quote.has(@js($product->sku))" x-cloak><x-icon name="check-circle" class="h-4 w-4" /></span>
            <span x-text="$store.quote.has(@js($product->sku)) ? 'Đã thêm' : 'Báo giá'">Báo giá</span>
        </button>
    </div>
</article>
