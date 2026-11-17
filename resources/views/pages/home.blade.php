@extends('layouts.app')

@section('title', 'Văn phòng phẩm cho doanh nghiệp, nhà máy – báo giá sỉ, giao định kỳ | '.config('site.brand'))
@section('description', 'Cung cấp văn phòng phẩm và vật tư tiêu hao cho doanh nghiệp, nhà máy: giấy in, bút, bìa hồ sơ, băng keo, mực in. Báo giá theo danh mục, giao định kỳ, công nợ và hóa đơn VAT.')

@push('schema')
{!! \App\Support\JsonLd::script(\App\Support\JsonLd::website()) !!}
@endpush

@section('content')
    {{-- ===== Khu banner đầu trang: banner chính + 2 banner phải + hàng banner dưới (quản lý ở Quản trị → Banner) ===== --}}
    @include('partials.home-hero')

    {{-- ===== Lưới danh mục dạng biểu tượng ===== --}}
    <div class="mx-auto mt-4 max-w-site px-4">
        @include('partials.category-grid')
    </div>

    {{-- ===== Flash Sale (chỉ hiện khi có chương trình đang chạy) ===== --}}
    @if ($flash['sale'] && $flash['items']->isNotEmpty())
        <div class="mx-auto mt-6 max-w-site px-4">
            @include('partials.flash-sale-strip')
        </div>
    @endif

    {{-- ===== Các khối sản phẩm theo danh mục ===== --}}
    <div class="mx-auto mt-6 max-w-site space-y-6 px-4">
        @if ($banners->isNotEmpty())
            <h1 class="sr-only">Văn phòng phẩm cho doanh nghiệp, nhà máy – {{ config('site.brand') }}</h1>
        @endif

        @forelse ($sections as $section)
            @include('partials.product-section', $section)
        @empty
            <p class="rounded-md bg-white p-6 text-center">Sản phẩm đang được cập nhật.</p>
        @endforelse
    </div>

    {{-- ===== Phần gần cuối trang ===== --}}
    @include('partials.home-bottom')
@endsection
