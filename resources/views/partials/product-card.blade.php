<article class="flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white transition hover:shadow-md">
    <a href="{{ $product->url() }}" class="block aspect-square bg-slate-100">
        @if ($product->mainImage())
            <img src="{{ $product->mainImage() }}" alt="{{ $product->name }}" loading="lazy" decoding="async"
                 width="400" height="400" class="h-full w-full object-contain p-3">
        @else
            <div class="flex h-full items-center justify-center text-xs text-slate-400">Chưa có ảnh</div>
        @endif
    </a>
    <div class="flex flex-1 flex-col p-3">
        <p class="text-xs text-slate-500">{{ $product->sku }}@if ($product->brand) · {{ $product->brand }}@endif</p>
        <h3 class="mt-1 line-clamp-2 text-sm font-medium text-slate-900">
            <a href="{{ $product->url() }}" class="hover:text-brand-600">{{ $product->name }}</a>
        </h3>
        <p class="mt-auto pt-2 text-sm font-semibold text-brand-700">{{ $product->formattedPrice() }}</p>
        <button type="button" class="mt-2 rounded-lg border border-brand-600 px-3 py-1.5 text-xs font-semibold text-brand-700 hover:bg-brand-50"
                x-data @click="$store.quote.add(@js($product->sku), {{ $product->min_order_qty }})"
                x-text="$store.quote.has(@js($product->sku)) ? '✓ Đã thêm – thêm nữa' : '+ Thêm vào báo giá'">
            + Thêm vào báo giá
        </button>
    </div>
</article>
