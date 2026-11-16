@extends('layouts.app')

@section('title', 'Cung cấp văn phòng phẩm cho doanh nghiệp, nhà máy – báo giá định kỳ | '.config('site.brand'))
@section('description', 'Nhà cung cấp văn phòng phẩm và vật tư tiêu hao cho doanh nghiệp, nhà máy FDI: báo giá theo danh mục, giao định kỳ hàng tháng, công nợ và hóa đơn VAT đầy đủ.')

@section('content')
<section class="bg-gradient-to-br from-brand-900 to-brand-700 text-white">
    <div class="mx-auto grid max-w-7xl gap-8 px-4 py-14 md:grid-cols-2 md:items-center">
        <div>
            <h1 class="text-3xl font-bold leading-tight md:text-4xl">Văn phòng phẩm cho doanh nghiệp &amp; nhà máy – một đầu mối, giao định kỳ</h1>
            <p class="mt-4 text-brand-100">Gửi danh mục cần mua, nhận báo giá trong 4 giờ làm việc. Giao tận nơi tại các khu công nghiệp, công nợ theo hợp đồng, hóa đơn VAT đầy đủ.</p>
            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('quote.create') }}" class="rounded-lg bg-accent-500 px-5 py-3 font-semibold text-slate-900 hover:bg-accent-600">Gửi yêu cầu báo giá</a>
                @if ($categories->isNotEmpty())
                    <a href="{{ $categories->first()->url() }}" class="rounded-lg border border-white/40 px-5 py-3 font-semibold hover:bg-white/10">Xem sản phẩm</a>
                @endif
            </div>
        </div>
        <ul class="grid gap-3 sm:grid-cols-2">
            @foreach (config('site.usps') as $usp)
                <li class="rounded-xl bg-white/10 p-4">
                    <p class="font-semibold">{{ $usp['title'] }}</p>
                    <p class="mt-1 text-sm text-brand-100">{{ $usp['text'] }}</p>
                </li>
            @endforeach
        </ul>
    </div>
</section>

@if ($categories->isNotEmpty())
<section class="mx-auto max-w-7xl px-4 py-12">
    <h2 class="mb-6 text-2xl font-bold text-slate-900">Danh mục sản phẩm</h2>
    <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
        @foreach ($categories as $category)
            <a href="{{ $category->url() }}" class="rounded-xl border border-slate-200 bg-white p-5 hover:border-brand-600 hover:shadow-sm">
                <p class="font-semibold text-slate-900">{{ $category->name }}</p>
                @if ($category->intro)<p class="mt-1 line-clamp-2 text-sm text-slate-500">{{ $category->intro }}</p>@endif
            </a>
        @endforeach
    </div>
</section>
@endif

@if ($featured->isNotEmpty())
<section class="mx-auto max-w-7xl px-4 pb-12">
    <h2 class="mb-6 text-2xl font-bold text-slate-900">Sản phẩm doanh nghiệp mua nhiều</h2>
    <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
        @foreach ($featured as $product)
            @include('partials.product-card')
        @endforeach
    </div>
</section>
@endif

<section class="bg-white">
    <div class="mx-auto max-w-7xl px-4 py-12">
        <h2 class="mb-6 text-2xl font-bold text-slate-900">Quy trình đặt hàng cho doanh nghiệp</h2>
        <ol class="grid gap-4 md:grid-cols-4">
            @foreach ([
                ['Gửi danh mục', 'Chọn sản phẩm trên web hoặc gửi file Excel danh mục đang dùng.'],
                ['Nhận báo giá', 'Báo giá chi tiết theo số lượng, quy cách, có phương án thay thế tiết kiệm hơn.'],
                ['Ký hợp đồng / PO', 'Thống nhất lịch giao định kỳ và điều khoản công nợ.'],
                ['Giao hàng & hóa đơn', 'Giao tận kho/phòng hành chính, kèm hóa đơn VAT và biên bản giao nhận.'],
            ] as $i => [$step, $text])
                <li class="rounded-xl border border-slate-200 p-5">
                    <span class="text-sm font-bold text-accent-600">Bước {{ $i + 1 }}</span>
                    <p class="mt-1 font-semibold text-slate-900">{{ $step }}</p>
                    <p class="mt-1 text-sm">{{ $text }}</p>
                </li>
            @endforeach
        </ol>
    </div>
</section>

@if ($posts->isNotEmpty())
<section class="mx-auto max-w-7xl px-4 py-12">
    <h2 class="mb-6 text-2xl font-bold text-slate-900">Kiến thức mua sắm VPP cho doanh nghiệp</h2>
    <div class="grid gap-4 md:grid-cols-3">
        @foreach ($posts as $post)
            <a href="{{ $post->url() }}" class="rounded-xl border border-slate-200 bg-white p-5 hover:shadow-sm">
                <p class="font-semibold text-slate-900">{{ $post->title }}</p>
                <p class="mt-2 line-clamp-3 text-sm">{{ $post->excerpt }}</p>
            </a>
        @endforeach
    </div>
</section>
@endif
@endsection
