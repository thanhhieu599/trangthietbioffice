{{-- ===== Cam kết dịch vụ B2B ===== --}}
<section class="mt-10 border-y border-neutral-200 bg-white" aria-label="Cam kết dịch vụ">
    <ul class="mx-auto grid max-w-site grid-cols-2 gap-y-6 px-4 py-6 lg:grid-cols-4">
        @foreach ([
            ['clipboard', 'Báo giá trong 4 giờ', 'Gửi danh mục, nhận báo giá chi tiết'],
            ['calendar', 'Giao định kỳ', 'Lịch giao cố định theo tháng / quý'],
            ['receipt', 'Công nợ & hóa đơn VAT', 'Thanh toán theo hợp đồng'],
            ['truck', 'Giao tận nơi', 'Văn phòng, kho, khu công nghiệp'],
        ] as [$icon, $title, $text])
            <li class="flex items-center gap-3 lg:justify-center">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-primary-50 text-primary-600">
                    <x-icon :name="$icon" class="h-6 w-6" />
                </span>
                <span>
                    <span class="block text-sm font-bold text-ink-900">{{ $title }}</span>
                    <span class="block text-xs text-neutral-500">{{ $text }}</span>
                </span>
            </li>
        @endforeach
    </ul>
</section>

<div class="mx-auto max-w-site space-y-8 px-4 py-8">
    {{-- ===== Thương hiệu ===== --}}
    @if ($brands->isNotEmpty())
        <section aria-labelledby="home-brands">
            <div class="mb-4 flex items-end justify-between border-b-2 border-primary-600">
                <h2 id="home-brands" class="bg-primary-600 px-4 py-2 text-[15px] font-bold uppercase text-white">Thương hiệu phân phối</h2>
                <a href="{{ route('brands.index') }}" class="pb-2 text-sm text-neutral-600 hover:text-primary-600">Xem tất cả ›</a>
            </div>
            <ul class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6">
                @foreach ($brands as $brand)
                    <li>
                        <a href="{{ route('brands.show', $brand['slug']) }}"
                           class="flex h-16 items-center justify-center rounded-md border border-neutral-200 bg-white px-3 text-center font-bold uppercase tracking-wide text-ink-800 hover:border-primary-600 hover:text-primary-600">
                            {{ $brand['name'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- ===== Tin tức mới (cuối trang, ngay trên footer) ===== --}}
    @include('partials.news-carousel')
</div>
