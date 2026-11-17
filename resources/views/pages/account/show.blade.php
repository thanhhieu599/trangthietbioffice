@extends('pages.account._layout')

@section('title', 'Tài khoản của tôi | '.config('site.brand'))

@php($field = 'w-full rounded-lg border border-neutral-300 px-3 py-2.5 text-[15px] focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15')

@section('account')
<div class="space-y-6">
    @if (! $user->hasDeliveryInfo())
        <div class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Hãy bổ sung số điện thoại và địa chỉ giao hàng để lần sau đặt hàng không cần nhập lại.
        </div>
    @endif

    <section class="rounded-md bg-white p-6">
        <h1 class="text-xl font-bold text-ink-900">Thông tin tài khoản</h1>
        <p class="mt-1 text-sm text-neutral-600">Thông tin này được điền sẵn ở bước đặt hàng. Ô có dấu <span class="text-primary-600">*</span> là bắt buộc.</p>
        @if (! $errors->password->any())
            @include('pages.account._flash', ['inline' => true])
        @endif

        <form method="post" action="{{ route('account.update') }}" class="mt-6">
            @csrf
            @include('partials.customer-fields', ['v' => $user->only(['name', 'email', 'phone', 'company', 'tax_code', 'address', 'ward', 'province']), 'nameField' => 'name'])
            <button class="mt-6 rounded-lg bg-primary-600 px-6 py-2.5 font-semibold text-white hover:bg-primary-700">Lưu thông tin</button>
        </form>
    </section>

    <section class="rounded-md bg-white p-6">
        <h2 class="text-lg font-bold text-ink-900">Đổi mật khẩu</h2>
        @if ($errors->password->any())
            <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
                <ul class="list-disc pl-5">@foreach ($errors->password->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif
        <form method="post" action="{{ route('account.password') }}" class="mt-4 grid gap-4 sm:grid-cols-3">
            @csrf
            <div>
                <label for="current_password" class="mb-1 block text-sm font-medium text-ink-800">Mật khẩu hiện tại</label>
                <input id="current_password" type="password" name="current_password" required autocomplete="current-password" class="{{ $field }}">
            </div>
            <div>
                <label for="new_password" class="mb-1 block text-sm font-medium text-ink-800">Mật khẩu mới</label>
                <input id="new_password" type="password" name="password" required minlength="8" autocomplete="new-password" class="{{ $field }}">
            </div>
            <div>
                <label for="new_password_confirmation" class="mb-1 block text-sm font-medium text-ink-800">Nhập lại mật khẩu mới</label>
                <input id="new_password_confirmation" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password" class="{{ $field }}">
            </div>
            <div class="sm:col-span-3">
                <button class="rounded-lg border border-neutral-300 px-5 py-2.5 font-semibold text-ink-900 hover:border-primary-600 hover:text-primary-600">Đổi mật khẩu</button>
            </div>
        </form>
    </section>

    <section class="rounded-md bg-white p-6">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold text-ink-900">Đơn hàng gần đây</h2>
            <a href="{{ route('account.orders') }}" class="text-sm text-primary-600 hover:underline">Xem tất cả</a>
        </div>
        @include('pages.account._orders-table', ['orders' => $recentOrders])
    </section>
</div>
@endsection
