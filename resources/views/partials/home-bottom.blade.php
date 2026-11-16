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
    {{-- ===== Thương hiệu: 2 hàng thẻ logo, tự trượt khi nhiều ===== --}}
    @include('partials.brand-carousel')

    {{-- ===== Tin tức mới (cuối trang, ngay trên footer) ===== --}}
    @include('partials.news-carousel')
</div>
