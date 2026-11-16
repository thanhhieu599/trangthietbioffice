{{--
    Một khối sản phẩm theo danh mục trên trang chủ: chỉ 1 hàng, trượt ngang bằng nút hai bên
    (máy tính 5 sản phẩm / điện thoại 2 sản phẩm), cuối hàng có ô "Xem thêm".
--}}
<section class="overflow-hidden rounded-md bg-white" aria-labelledby="sec-{{ $category->slug }}" x-data="carousel">
    <div class="flex items-center justify-between gap-4 border-b border-neutral-200 bg-neutral-100 px-4 py-3">
        <h2 id="sec-{{ $category->slug }}" class="text-[17px] font-bold uppercase text-ink-900">
            <a href="{{ $category->url() }}" class="hover:text-primary-600">{{ $category->name }}</a>
        </h2>
        <a href="{{ $category->url() }}" class="flex shrink-0 items-center gap-1 text-sm text-neutral-600 hover:text-primary-600">
            Xem tất cả @if ($total > $products->count())({{ $total }})@endif <x-icon name="chevron-right" class="h-4 w-4" />
        </a>
    </div>

    <div class="relative p-3">
        <div x-ref="track" @scroll.debounce.50ms="onScroll()"
             class="no-scrollbar flex snap-x snap-mandatory gap-3 overflow-x-auto scroll-smooth py-0.5">
            @foreach ($products as $product)
                <div class="w-[calc((100%-0.75rem)/2)] shrink-0 snap-start md:w-[calc((100%-1.5rem)/3)] lg:w-[calc((100%-3rem)/5)]">
                    @include('partials.product-card')
                </div>
            @endforeach

            @if ($total > $products->count())
                <div class="w-[calc((100%-0.75rem)/2)] shrink-0 snap-start md:w-[calc((100%-1.5rem)/3)] lg:w-[calc((100%-3rem)/5)]">
                    <a href="{{ $category->url() }}"
                       class="flex h-full min-h-60 flex-col items-center justify-center gap-3 rounded-lg border border-dashed border-primary-600 bg-primary-50 p-4 text-center text-primary-600 hover:bg-primary-100">
                        <x-icon name="grid" class="h-10 w-10" />
                        <span class="font-semibold">Xem thêm {{ $total - $products->count() }} sản phẩm</span>
                        <span class="text-sm">{{ $category->name }}</span>
                    </a>
                </div>
            @endif
        </div>

        {{-- Nút trượt hai bên, cùng kiểu nút của banner --}}
        <button type="button" x-show="pages > 1" x-cloak @click="go(page - 1)" aria-label="Sản phẩm trước"
                class="absolute top-1/2 left-0 z-10 -translate-y-1/2 bg-black/40 max-md:hidden px-1.5 py-5 text-white transition hover:bg-black/60">
            <x-icon name="chevron-left" />
        </button>
        <button type="button" x-show="pages > 1" x-cloak @click="go(page + 1)" aria-label="Sản phẩm tiếp"
                class="absolute top-1/2 right-0 z-10 -translate-y-1/2 bg-black/40 max-md:hidden px-1.5 py-5 text-white transition hover:bg-black/60">
            <x-icon name="chevron-right" />
        </button>
    </div>
</section>
