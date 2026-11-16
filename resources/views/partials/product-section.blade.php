{{-- Một khối sản phẩm theo danh mục trên trang chủ: thanh tiêu đề + lưới sản phẩm --}}
<section class="overflow-hidden rounded-md bg-white" aria-labelledby="sec-{{ $category->slug }}">
    <div class="flex items-center justify-between gap-4 border-b border-neutral-200 bg-neutral-100 px-4 py-3">
        <h2 id="sec-{{ $category->slug }}" class="text-[17px] font-bold uppercase text-ink-900">
            <a href="{{ $category->url() }}" class="hover:text-primary-600">{{ $category->name }}</a>
        </h2>
        <a href="{{ $category->url() }}" class="flex shrink-0 items-center gap-1 text-sm text-neutral-600 hover:text-primary-600">
            Xem tất cả <x-icon name="chevron-right" class="h-4 w-4" />
        </a>
    </div>
    <div class="grid grid-cols-2 gap-3 p-3 md:grid-cols-3 lg:grid-cols-5">
        @foreach ($products as $product)
            @include('partials.product-card')
        @endforeach
    </div>
</section>
