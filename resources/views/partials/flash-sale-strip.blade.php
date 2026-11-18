{{--
    Khối Flash Sale trên trang chủ (giữa lưới danh mục và các khối sản phẩm).
    Biến: $flash = ['sale' => FlashSale, 'items' => Collection<FlashSaleItem>]. Hết giờ thì tự ẩn.
--}}
@php($items = $flash['items']->take(15))
<section aria-labelledby="flash-sale-title"
         class="relative overflow-hidden rounded-xl bg-[linear-gradient(120deg,#8f1d22_0%,#b3262d_50%,#d9573a_100%)] p-3 shadow-[0_8px_30px_-12px_rgba(179,38,45,.55)] sm:p-4"
         x-data="countdown(@js($flash['sale']->ends_at->toIso8601String()), {{ $flash['sale']->cycleSeconds() }})" x-show="!done" x-transition.opacity>

    {{-- Họa tiết tia sét mờ + quầng sáng (trang trí) --}}
    <svg class="pointer-events-none absolute -top-10 -left-6 h-72 w-48 text-white/[0.07]" viewBox="0 0 20 30" aria-hidden="true"><path d="M12.5 0 1 17h7l-2.5 13L19 11h-7.5L12.5 0z" fill="currentColor"/></svg>
    <svg class="pointer-events-none absolute -right-8 -bottom-16 h-80 w-56 rotate-12 text-white/[0.07]" viewBox="0 0 20 30" aria-hidden="true"><path d="M12.5 0 1 17h7l-2.5 13L19 11h-7.5L12.5 0z" fill="currentColor"/></svg>
    <div class="pointer-events-none absolute top-0 right-1/3 h-40 w-80 rounded-full bg-white/10 blur-3xl"></div>

    {{-- Thanh tiêu đề --}}
    <div class="relative flex flex-wrap items-center gap-x-5 gap-y-2 rounded-xl bg-white px-4 py-3 shadow-sm sm:px-5">
        <h2 id="flash-sale-title" class="leading-none">
            <a href="{{ route('flash-sale') }}" aria-label="Flash Sale – {{ $flash['sale']->name }}">@include('partials.flash.logo')</a>
        </h2>
        <div class="flex items-center gap-2.5">
            <span class="hidden text-sm font-medium text-neutral-600 sm:inline">Kết thúc trong</span>
            @include('partials.flash.countdown')
        </div>
        <a href="{{ route('flash-sale') }}" class="ml-auto inline-flex items-center gap-1 rounded-full px-3 py-1.5 text-sm font-semibold text-primary-600 transition hover:bg-primary-50">
            Xem tất cả <x-icon name="chevron-right" class="h-4 w-4" />
        </a>
    </div>

    {{-- Sản phẩm: 1 hàng trượt ngang --}}
    <div class="relative mt-3" x-data="carousel">
        <div x-ref="track" @scroll.debounce.50ms="onScroll()" class="no-scrollbar flex snap-x snap-mandatory gap-3 overflow-x-auto scroll-smooth py-1">
            @foreach ($items as $item)
                <div class="w-[calc((100%-0.75rem)/2)] shrink-0 snap-start md:w-[calc((100%-1.5rem)/3)] lg:w-[calc((100%-3rem)/5)]">
                    @include('partials.flash.card')
                </div>
            @endforeach
        </div>
        <button type="button" x-show="pages > 1" x-cloak @click="go(page - 1)" aria-label="Sản phẩm trước"
                class="absolute top-[38%] -left-1 z-10 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white text-ink-800 shadow-lg transition hover:scale-105 max-md:hidden">
            <x-icon name="chevron-left" class="h-5 w-5" />
        </button>
        <button type="button" x-show="pages > 1" x-cloak @click="go(page + 1)" aria-label="Sản phẩm sau"
                class="absolute top-[38%] -right-1 z-10 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white text-ink-800 shadow-lg transition hover:scale-105 max-md:hidden">
            <x-icon name="chevron-right" class="h-5 w-5" />
        </button>
    </div>
</section>
