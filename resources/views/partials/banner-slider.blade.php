{{--
    Banner đầu trang chủ. Ảnh khuyến nghị: 1170 x 500 px (máy tính), 800 x 450 px (điện thoại, tùy chọn).
    Đặt chữ / sản phẩm chính ở giữa ảnh: màn hình hẹp sẽ cắt bớt hai bên.
    Thêm / đổi ảnh trong bảng "banners" (sau này là trang quản trị).
--}}
@if ($banners->isNotEmpty())
    <div class="relative h-full overflow-hidden rounded-md bg-neutral-100"
         x-data="{
            i: 0, n: {{ $banners->count() }}, timer: null,
            go(k) { this.i = (k + this.n) % this.n },
            play() { if (this.n > 1) this.timer = setInterval(() => this.go(this.i + 1), 5000) },
            stop() { clearInterval(this.timer) },
         }"
         x-init="play()" @mouseenter="stop()" @mouseleave="play()"
         role="region" aria-roledescription="carousel" aria-label="Chương trình nổi bật">
        @foreach ($banners as $k => $banner)
            <a href="{{ $banner->link ?: '#' }}" class="absolute inset-0 transition-opacity duration-500"
               :class="i === {{ $k }} ? 'opacity-100 z-10' : 'opacity-0 z-0'"
               @if ($k > 0) aria-hidden="true" :aria-hidden="i !== {{ $k }}" @endif>
                <picture>
                    @if ($banner->image_mobile)
                        <source media="(max-width: 767px)" srcset="{{ $banner->imageUrl($banner->image_mobile) }}">
                    @endif
                    <img src="{{ $banner->imageUrl() }}" alt="{{ $banner->title ?: config('site.brand') }}"
                         width="1170" height="500" class="h-full w-full object-cover"
                         @if ($k === 0) fetchpriority="high" @else loading="lazy" @endif>
                </picture>
            </a>
        @endforeach

        @if ($banners->count() > 1)
            <button type="button" @click="go(i - 1)" aria-label="Ảnh trước"
                    class="absolute top-1/2 left-2 z-20 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-white/85 text-ink-800 shadow-md transition hover:bg-white">
                <x-icon name="chevron-left" class="h-5 w-5" />
            </button>
            <button type="button" @click="go(i + 1)" aria-label="Ảnh sau"
                    class="absolute top-1/2 right-2 z-20 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-white/85 text-ink-800 shadow-md transition hover:bg-white">
                <x-icon name="chevron-right" class="h-5 w-5" />
            </button>
            <div class="absolute bottom-3 left-1/2 z-20 flex -translate-x-1/2 gap-2">
                @foreach ($banners as $k => $banner)
                    <button type="button" @click="go({{ $k }})" aria-label="Ảnh {{ $k + 1 }}"
                            class="h-2 rounded-full transition-all"
                            :class="i === {{ $k }} ? 'w-5 bg-white' : 'w-2 bg-white/50 hover:bg-white/80'"></button>
                @endforeach
            </div>
        @endif
    </div>
@else
    {{-- Chưa có banner: hiển thị khối giới thiệu mặc định --}}
    <div class="flex h-full flex-col justify-center rounded-md bg-gradient-to-br from-ink-900 via-ink-800 to-primary-800 p-8 text-white md:p-12">
        <p class="text-sm font-semibold uppercase tracking-widest text-primary-100">Văn phòng phẩm cho doanh nghiệp</p>
        <h1 class="mt-3 max-w-xl text-2xl font-bold leading-tight md:text-4xl">Một đầu mối – giao định kỳ – công nợ &amp; hóa đơn VAT</h1>
        <p class="mt-3 max-w-xl text-neutral-300">Gửi danh mục cần mua, nhận báo giá trong 4 giờ làm việc.</p>
        <a href="{{ route('quote.create') }}" class="mt-6 inline-block self-start rounded-md bg-primary-600 px-6 py-3 font-semibold hover:bg-primary-700">Gửi yêu cầu báo giá</a>
    </div>
@endif
