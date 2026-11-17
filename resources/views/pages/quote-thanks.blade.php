@extends('layouts.app')

@section('title', 'Đã nhận đơn hàng | '.config('site.brand'))
@section('robots', 'noindex, nofollow')

@section('content')
<div class="mx-auto max-w-2xl px-4 py-16 text-center" x-data x-init="@if (session('quote_id')) $store.quote.clear() @endif">
    <x-icon name="check-circle" class="mx-auto h-14 w-14 text-green-600" />
    <h1 class="mt-3 text-2xl font-bold text-ink-900">Cảm ơn anh/chị đã đặt hàng!</h1>
    @if (session('quote_code'))
        <p class="mt-2 text-neutral-600">Mã đơn: <strong class="text-ink-900">{{ session('quote_code') }}</strong></p>
    @endif
    <p class="mt-4">Bộ phận kinh doanh sẽ xác nhận đơn và gửi báo giá qua email trong 4 giờ làm việc.@if (config('site.phone')) Cần gấp, vui lòng gọi <a href="tel:{{ preg_replace('/\s+/', '', config('site.phone')) }}" class="font-semibold text-primary-600">{{ config('site.phone') }}</a>.@endif</p>

    <div class="mt-8 flex flex-wrap justify-center gap-3">
        @auth
            @if (session('quote_id'))
                <a href="{{ route('account.order', session('quote_id')) }}" class="rounded-lg bg-primary-600 px-5 py-3 font-semibold text-white hover:bg-primary-700">Xem đơn hàng</a>
            @endif
        @endauth
        <a href="{{ route('home') }}" class="rounded-lg border border-neutral-300 px-5 py-3 font-semibold text-ink-900 hover:border-primary-600 hover:text-primary-600">Tiếp tục mua hàng</a>
    </div>

    @guest
        <p class="mt-8 text-sm text-neutral-600">Muốn lần sau không phải nhập lại thông tin và theo dõi đơn dễ hơn?
            <a href="{{ route('register') }}" class="font-semibold text-primary-600 hover:underline">Tạo tài khoản</a></p>
    @endguest
</div>
@endsection
