{{--
    Khu banner đầu trang chủ (bố cục kiểu sàn TMĐT):
      [ banner chính – trình chiếu (2/3) ][ 2 banner bên phải xếp dọc (1/3) ]
      [ promo 1 ][ promo 2 ][ promo 3 ][ promo 4 ]
    Vị trí nào chưa có ảnh thì ẩn; không có banner phải thì banner chính rộng hết khung.
--}}
@php
    $tile = function ($b, $extra = '', $eager = false) {
        $alt = e($b->title ?: config('site.brand'));
        // Ảnh phủ kín khung (absolute): chiều cao khung do tỉ lệ / lưới quyết định, không do kích thước ảnh.
        $img = '<img src="'.e($b->imageUrl()).'" alt="'.$alt.'" class="absolute inset-0 h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]"'
            .($eager ? '' : ' loading="lazy"').' decoding="async">';

        return $b->link
            ? '<a href="'.e($b->link).'" class="group relative block min-h-0 overflow-hidden rounded-md bg-neutral-100 '.$extra.'">'.$img.'</a>'
            : '<div class="group relative min-h-0 overflow-hidden rounded-md bg-neutral-100 '.$extra.'">'.$img.'</div>';
    };
    $sideCount = $sideBanners->count();
@endphp

<div class="mx-auto max-w-site px-4 pt-3">
    <div class="grid gap-2.5 {{ $sideCount ? 'lg:grid-cols-[2fr_1fr]' : '' }}">
        <div class="aspect-[1200/460] min-w-0">
            @include('partials.banner-slider')
        </div>

        @if ($sideCount)
            <div class="grid grid-cols-2 gap-2.5 lg:grid-cols-1 {{ $sideCount > 1 ? 'lg:grid-rows-2' : 'lg:grid-rows-1' }}">
                @foreach ($sideBanners as $b)
                    {!! $tile($b, 'aspect-[600/230] lg:aspect-auto lg:h-full', true) !!}
                @endforeach
            </div>
        @endif
    </div>

    {{--
        Hàng banner nhỏ dưới banner chính: tự chia cột đều theo số ảnh (3 ảnh = 3 cột như mẫu, 4 ảnh = 4 cột).
        Điện thoại: dải trượt ngang (mỗi ảnh ~80% màn hình) để ảnh không bị thu quá nhỏ.
    --}}
    @if ($promoBanners->isNotEmpty())
        @php($cols = [1 => 'md:grid-cols-1', 2 => 'md:grid-cols-2', 3 => 'md:grid-cols-3', 4 => 'md:grid-cols-4'][$promoBanners->count()] ?? 'md:grid-cols-4')
        <div class="no-scrollbar -mx-4 mt-2.5 flex snap-x snap-mandatory scroll-px-4 gap-2.5 overflow-x-auto px-4 md:mx-0 md:grid md:scroll-px-0 md:gap-3 md:overflow-visible md:px-0 {{ $cols }}">
            @foreach ($promoBanners as $b)
                {!! $tile($b, 'aspect-[600/400] w-[80%] shrink-0 snap-start md:w-auto shadow-sm ring-1 ring-black/5 hover:shadow-lg') !!}
            @endforeach
        </div>
    @endif
</div>
