{{--
    Ô banner của danh mục – CHỈ LÀ ẢNH, không chữ / nút.
    Biến: $category, $banner (URL ảnh hoặc null), $wide (true = banner ngang đầu trang danh mục).
    Chưa tải ảnh: vẫn giữ ô banner (nền nhạt + biểu tượng danh mục) để bố cục luôn như thiết kế.
--}}
@php
    $wide = $wide ?? false;
    $link = $wide ? null : ($category->banner_link ?: $category->url());
    $icon = $category->imageUrl();
    $tag = $link ? 'a' : 'div';
@endphp
<{{ $tag }} @if ($link) href="{{ $link }}" @endif aria-label="{{ $category->name }}"
    class="group relative block h-full overflow-hidden rounded-lg bg-neutral-100 shadow-sm {{ $wide ? 'rounded-xl' : '' }}">
    @if ($banner)
        <img src="{{ $banner }}" alt="{{ $category->name }}" decoding="async" width="{{ $wide ? 1200 : 600 }}" height="{{ $wide ? 380 : 1000 }}"
             @if ($wide) fetchpriority="high" @else loading="lazy" @endif
             class="absolute inset-0 h-full w-full object-cover">
    @else
        {{-- Ô chờ ảnh: chỉ hình, không chữ --}}
        <span class="absolute inset-0 bg-[radial-gradient(circle_at_30%_20%,#fff_0%,transparent_55%),linear-gradient(160deg,#fdecea_0%,#f6d5d1_55%,#efc2bc_100%)]"></span>
        <span class="absolute -right-10 -bottom-10 h-48 w-48 rounded-full bg-white/40"></span>
        <span class="absolute -top-8 -left-8 h-32 w-32 rounded-full bg-white/30"></span>
        @if ($icon)
            <span class="absolute inset-0 flex items-center justify-center">
                <span class="flex {{ $wide ? 'h-28 w-28' : 'h-36 w-36 xl:h-44 xl:w-44' }} items-center justify-center rounded-full bg-white shadow-[0_10px_40px_-10px_rgba(179,38,45,.35)] ring-8 ring-white/50">
                    <img src="{{ $icon }}" alt="" class="{{ $wide ? 'h-14 w-14' : 'h-20 w-20 xl:h-24 xl:w-24' }} object-contain" loading="lazy">
                </span>
            </span>
        @endif
    @endif
</{{ $tag }}>
