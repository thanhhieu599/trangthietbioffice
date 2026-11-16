@extends('layouts.app')

@section('title', ($term ? 'Kết quả tìm "'.$term.'"' : 'Tìm sản phẩm').' | '.config('site.brand'))
@section('robots', 'noindex, follow')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8">
    <h1 class="text-2xl font-bold text-slate-900">{{ $term ? 'Kết quả cho "'.$term.'"' : 'Tìm sản phẩm' }}</h1>
    @if ($term && $products->isEmpty())
        <p class="mt-4">Chưa tìm thấy mã phù hợp. Anh/chị có thể <a href="{{ route('quote.create') }}" class="text-brand-600 underline">gửi yêu cầu báo giá</a> kèm tên hàng, chúng tôi sẽ tìm nguồn.</p>
    @endif
    <div class="mt-6 grid grid-cols-2 gap-4 md:grid-cols-4">
        @foreach ($products as $product)
            @include('partials.product-card')
        @endforeach
    </div>
</div>
@endsection
