{{--
    Lưới danh mục dạng biểu tượng (giữa banner và các khối sản phẩm) – luôn hiển thị 2 HÀNG.
      - Vừa 2 hàng (máy tính ≤ 16 ô, điện thoại ≤ 10 ô): lưới đứng yên, chia đều 2 hàng.
      - Nhiều hơn: chuyển thành băng chuyền 2 hàng (hàng trên nửa đầu, hàng dưới nửa sau danh sách),
        tự trượt mỗi 4 giây, có nút trái / phải.
    Ảnh mỗi ô: chép file vào public/images/categories/{đường-dẫn-danh-mục}.png (ví dụ bia-ho-so.png).
--}}
@php
    $count = $gridCategories->count();
    $perRowLg = 8;   // số ô mỗi hàng tối đa trên máy tính
    $perRowSm = 5;   // số ô mỗi hàng tối đa trên điện thoại / máy tính bảng
    $slideLg = $count > $perRowLg * 2;
    $slideSm = $count > $perRowSm * 2;
    $cols = (int) ceil($count / 2); // số cột khi lưới đứng yên (2 hàng đầy)

    // Hàng trên = nửa đầu danh sách, hàng dưới = nửa sau (đọc trái -> phải đúng thứ tự).
    // Khi trượt: mỗi cột rộng đúng 1/8 (máy tính) hoặc 1/5 (điện thoại) khung nhìn, 2 hàng trượt cùng nhau.
    // Các lớp CSS viết đầy đủ để Tailwind nhận diện được.
    $fit = 'grid [grid-template-columns:repeat(var(--cols),minmax(0,1fr))]';
    $slideBase = 'no-scrollbar grid overflow-x-auto snap-x snap-mandatory scroll-smooth [grid-template-columns:repeat(var(--cols),calc((100%-4px)/5))]';
    $trackClass = match (true) {
        $slideLg => $slideBase.' lg:[grid-template-columns:repeat(var(--cols),calc((100%-7px)/8))]',
        $slideSm => $slideBase.' lg:overflow-visible lg:snap-none lg:[grid-template-columns:repeat(var(--cols),minmax(0,1fr))]',
        default => $fit,
    };
    // Số lẻ ô -> thêm 1 ô trống để 2 hàng thẳng đường kẻ.
    $fill = $count % 2;
@endphp

@if ($count)
<section aria-labelledby="home-categories" class="relative overflow-hidden rounded-md border border-neutral-200 bg-white"
         @if ($slideSm || $slideLg) x-data="carousel({ autoplay: 4000 })" aria-roledescription="carousel" @endif>
    <h2 id="home-categories" class="sr-only">Danh mục sản phẩm</h2>

    <ul @if ($slideSm || $slideLg) x-ref="track" @scroll.debounce.50ms="onScroll()" @endif
        class="gap-px bg-neutral-200 {{ $trackClass }}" style="--cols: {{ $cols }};">
        @foreach ($gridCategories as $cat)
            <li class="snap-start bg-white">
                <a href="{{ $cat->url() }}"
                   class="group flex h-full flex-col items-center gap-2 px-1 pt-4 pb-3 text-center transition-colors hover:bg-primary-50 focus-visible:relative focus-visible:z-10 focus-visible:outline-2 focus-visible:outline-primary-600 sm:px-2 lg:gap-3 lg:pt-5 lg:pb-4">
                    <span class="flex h-11 w-11 items-center justify-center transition-transform duration-200 group-hover:-translate-y-1 group-hover:scale-105 sm:h-14 sm:w-14 lg:h-16 lg:w-16">
                        @if ($src = $cat->imageUrl())
                            <img src="{{ $src }}" alt="" width="64" height="64" loading="lazy" decoding="async" class="max-h-full max-w-full object-contain">
                        @else
                            <x-icon name="grid" class="h-9 w-9 text-primary-600 lg:h-11 lg:w-11" />
                        @endif
                    </span>
                    <span class="line-clamp-3 text-[11px] leading-snug font-medium text-ink-800 group-hover:text-primary-600 sm:line-clamp-2 sm:text-[13px] lg:min-h-[2.5em] lg:text-[13.5px]">
                        {{ $cat->name }}
                    </span>
                </a>
            </li>
        @endforeach
        @if ($fill)
            <li aria-hidden="true" class="bg-white"></li>
        @endif
    </ul>

    @if ($slideSm || $slideLg)
        {{-- Nút trái / phải: chỉ hiện khi danh mục nhiều hơn 2 hàng (cùng kiểu nút banner) --}}
        <button type="button" x-show="pages > 1" x-cloak @click="go(page - 1)" aria-label="Danh mục trước"
                class="absolute top-1/2 left-0 z-10 -translate-y-1/2 bg-black/40 px-1 py-4 text-white transition hover:bg-black/60 lg:px-1.5 lg:py-5">
            <x-icon name="chevron-left" />
        </button>
        <button type="button" x-show="pages > 1" x-cloak @click="go(page + 1)" aria-label="Danh mục tiếp"
                class="absolute top-1/2 right-0 z-10 -translate-y-1/2 bg-black/40 px-1 py-4 text-white transition hover:bg-black/60 lg:px-1.5 lg:py-5">
            <x-icon name="chevron-right" />
        </button>
    @endif
</section>
@endif
