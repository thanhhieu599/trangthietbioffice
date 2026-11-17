@extends('pages.account._layout')

@section('title', 'Đơn hàng của tôi | '.config('site.brand'))

@section('account')
<section class="rounded-md bg-white p-6">
    <h1 class="text-xl font-bold text-ink-900">Đơn hàng của tôi</h1>
    <p class="mt-1 text-sm text-neutral-600">Bộ phận kinh doanh gửi báo giá / xác nhận đơn qua email và điện thoại trong 4 giờ làm việc.</p>
    @include('pages.account._orders-table', ['orders' => $orders])
    <div class="mt-4">{{ $orders->links() }}</div>
</section>
@endsection
