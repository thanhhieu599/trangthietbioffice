@extends('layouts.app')

@section('title', 'Đã nhận yêu cầu báo giá | '.config('site.brand'))
@section('robots', 'noindex, nofollow')

@section('content')
<div class="mx-auto max-w-2xl px-4 py-16 text-center" x-data x-init="@if (session('quote_id')) $store.quote.clear() @endif">
    <h1 class="text-2xl font-bold text-ink-900">Cảm ơn anh/chị đã gửi yêu cầu!</h1>
    @if (session('quote_id'))
        <p class="mt-2 text-neutral-500">Mã yêu cầu: #{{ session('quote_id') }}</p>
    @endif
    <p class="mt-4">Bộ phận kinh doanh sẽ gửi báo giá qua email trong 4 giờ làm việc.@if (config('site.phone')) Cần gấp, vui lòng gọi <a href="tel:{{ preg_replace('/\s+/', '', config('site.phone')) }}" class="font-semibold text-primary-600">{{ config('site.phone') }}</a>.@endif</p>
    <a href="{{ route('home') }}" class="mt-8 inline-block rounded-lg bg-primary-600 px-5 py-3 font-semibold text-white hover:bg-primary-700">Về trang chủ</a>
</div>
@endsection
