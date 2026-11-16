{{--
    Lưới danh mục dạng biểu tượng (giữa banner và các khối sản phẩm).
    Ảnh mỗi ô: chép file vào public/images/categories/{đường-dẫn-danh-mục}.png
    (ví dụ bia-ho-so.png). Chưa có ảnh thì dùng biểu tượng .svg mặc định đi kèm web.
--}}
@php
    $count = $gridCategories->count();
    // Máy tính: tối đa 10 ô / hàng, chia đều số hàng để không có hàng lẻ quá ngắn.
    $colsLg = $count <= 10 ? $count : (int) ceil($count / ceil($count / 10));
    // Điện thoại / máy tính bảng: 5 ô / hàng (4 nếu chia hết cho 4 mà không chia hết cho 5).
    $colsSm = ($count % 5 !== 0 && $count % 4 === 0) ? 4 : 5;
    $fillLg = ($colsLg - $count % $colsLg) % $colsLg;
    $fillSm = ($colsSm - $count % $colsSm) % $colsSm;
@endphp

@if ($count)
<section aria-labelledby="home-categories" class="overflow-hidden rounded-md border border-neutral-200 bg-white">
    <h2 id="home-categories" class="sr-only">Danh mục sản phẩm</h2>
    <ul class="grid gap-px bg-neutral-200 [grid-template-columns:repeat(var(--cols-sm),minmax(0,1fr))] lg:[grid-template-columns:repeat(var(--cols-lg),minmax(0,1fr))]"
        style="--cols-sm: {{ $colsSm }}; --cols-lg: {{ $colsLg }};">
        @foreach ($gridCategories as $cat)
            <li class="bg-white">
                <a href="{{ $cat->url() }}"
                   class="group flex h-full flex-col items-center gap-2 px-1 pt-4 pb-3 text-center transition-colors hover:bg-primary-50 focus-visible:relative focus-visible:z-10 focus-visible:outline-2 focus-visible:outline-primary-600 sm:px-2 lg:gap-3 lg:pt-5 lg:pb-4">
                    <span class="flex h-11 w-11 items-center justify-center transition-transform duration-200 group-hover:-translate-y-1 group-hover:scale-105 sm:h-14 sm:w-14 lg:h-16 lg:w-16">
                        @if ($src = $cat->imageUrl())
                            <img src="{{ $src }}" alt="" width="64" height="64" loading="lazy" decoding="async" class="max-h-full max-w-full object-contain">
                        @else
                            <x-icon name="grid" class="h-9 w-9 text-primary-600 lg:h-11 lg:w-11" />
                        @endif
                    </span>
                    <span class="line-clamp-3 text-[11px] sm:line-clamp-2 leading-snug font-medium text-ink-800 group-hover:text-primary-600 sm:text-[13px] lg:min-h-[2.5em] lg:text-[13.5px]">
                        {{ $cat->name }}
                    </span>
                </a>
            </li>
        @endforeach

        {{-- Ô trống để hàng cuối đủ cột, giữ đường kẻ lưới đều --}}
        @for ($i = 0; $i < max($fillLg, $fillSm); $i++)
            <li aria-hidden="true"
                class="bg-white {{ $i < $fillSm ? '' : 'max-lg:hidden' }} {{ $i < $fillLg ? '' : 'lg:hidden' }}"></li>
        @endfor
    </ul>
</section>
@endif
