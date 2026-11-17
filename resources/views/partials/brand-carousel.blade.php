{{--
    Khối Thương hiệu trên trang chủ – luôn hiển thị 2 HÀNG thẻ logo.
      - Vừa 2 hàng (máy tính ≤ 12 thương hiệu, điện thoại ≤ 6): đứng yên, chia đều 2 hàng.
      - Nhiều hơn: băng chuyền 2 hàng tự trượt mỗi 4 giây, có nút trái / phải.
    Hàng trên = nửa đầu danh sách, hàng dưới = nửa sau.
--}}
@php
    $count = $brands->count();
    $slideLg = $count > 12; // 6 thẻ / hàng trên máy tính
    $slideSm = $count > 6;  // 3 thẻ / hàng trên điện thoại
    $cols = (int) ceil($count / 2);

    // Lớp CSS viết đầy đủ để Tailwind nhận diện. Khoảng cách thẻ 12px (gap-3).
    $fit = 'grid [grid-template-columns:repeat(var(--cols),minmax(0,1fr))]';
    $slideBase = 'no-scrollbar grid overflow-x-auto snap-x snap-mandatory scroll-smooth [grid-template-columns:repeat(var(--cols),calc((100%-24px)/3))]';
    $trackClass = match (true) {
        $slideLg => $slideBase.' lg:[grid-template-columns:repeat(var(--cols),calc((100%-60px)/6))]',
        $slideSm => $slideBase.' lg:overflow-visible lg:snap-none lg:[grid-template-columns:repeat(var(--cols),minmax(0,1fr))]',
        default => $fit,
    };
@endphp

@if ($count)
<section aria-labelledby="home-brands" @if ($slideSm || $slideLg) x-data="carousel({ autoplay: 4000 })" @endif>
    <div class="mb-4 flex items-end justify-between border-b-2 border-primary-600">
        <h2 id="home-brands" class="bg-primary-600 px-4 py-2 text-[15px] font-bold uppercase text-white">Thương hiệu phân phối</h2>
        <a href="{{ route('brands.index') }}" class="flex items-center gap-1 pb-2 text-sm text-neutral-600 hover:text-primary-600">
            Xem tất cả <x-icon name="chevron-right" class="h-4 w-4" />
        </a>
    </div>

    <div class="relative rounded-md bg-white p-3">
        <ul @if ($slideSm || $slideLg) x-ref="track" @scroll.debounce.50ms="onScroll()" @endif
            class="gap-3 py-1 {{ $trackClass }}" @style(['--cols: '.$cols])>
            @foreach ($brands as $brand)
                <li class="snap-start">@include('partials.brand-card')</li>
            @endforeach
        </ul>

        @if ($slideSm || $slideLg)
            <button type="button" x-show="pages > 1" x-cloak @click="go(page - 1)" aria-label="Thương hiệu trước"
                    class="absolute top-1/2 left-0 z-10 -translate-y-1/2 bg-black/40 px-1 py-4 text-white transition hover:bg-black/60 lg:px-1.5 lg:py-5">
                <x-icon name="chevron-left" />
            </button>
            <button type="button" x-show="pages > 1" x-cloak @click="go(page + 1)" aria-label="Thương hiệu tiếp"
                    class="absolute top-1/2 right-0 z-10 -translate-y-1/2 bg-black/40 px-1 py-4 text-white transition hover:bg-black/60 lg:px-1.5 lg:py-5">
                <x-icon name="chevron-right" />
            </button>
        @endif
    </div>
</section>
@endif
