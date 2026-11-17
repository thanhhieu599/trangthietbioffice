{{--
    Một khối sản phẩm theo danh mục trên trang chủ:
      [ tên danh mục .......................................... Xem tất cả › ]
      [ banner dọc (1/3) ][ 2 hàng × 4 sản phẩm (2/3) ]  – banner luôn hiện, cao đúng bằng 2 hàng
    Banner chỉ là ảnh (Quản trị → Danh mục); chưa có ảnh thì là ô chờ ảnh (nền + biểu tượng, không chữ).
    Điện thoại / máy tính bảng: ẩn banner, 2 hàng sản phẩm trượt ngang.
--}}
@php($shown = $products->take(8))
<section class="overflow-hidden rounded-md bg-white" aria-labelledby="sec-{{ $category->slug }}">
    <div class="flex items-center justify-between gap-4 border-b border-neutral-200 bg-neutral-100 px-4 py-3">
        <h2 id="sec-{{ $category->slug }}" class="text-[17px] font-bold uppercase text-ink-900">
            <a href="{{ $category->url() }}" class="hover:text-primary-600">{{ $category->name }}</a>
        </h2>
        <a href="{{ $category->url() }}" class="flex shrink-0 items-center gap-1 text-sm text-neutral-600 hover:text-primary-600">
            Xem tất cả @if ($total > $shown->count())({{ $total }})@endif <x-icon name="chevron-right" class="h-4 w-4" />
        </a>
    </div>

    <div class="grid gap-3 p-3 lg:grid-cols-[minmax(0,1fr)_minmax(0,2fr)]">
        <div class="hidden min-h-[26rem] lg:block">
            @include('partials.category-banner', ['banner' => $category->bannerUrl()])
        </div>

        <div class="no-scrollbar grid snap-x snap-mandatory auto-cols-[46%] grid-flow-col grid-rows-2 gap-3 overflow-x-auto md:auto-cols-[31.5%]
                    lg:auto-cols-auto lg:grid-flow-row lg:grid-cols-4 lg:grid-rows-none lg:overflow-visible">
            @foreach ($shown as $product)
                <div class="min-w-0 snap-start">
                    @include('partials.product-card')
                </div>
            @endforeach
        </div>
    </div>
</section>
