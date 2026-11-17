@extends('layouts.app')

@section('title', 'Đăng ký tài khoản | '.config('site.brand'))
@section('description', 'Tạo tài khoản '.config('site.brand').' để lưu thông tin giao hàng, đặt hàng nhanh và theo dõi đơn.')
@section('robots', 'noindex, follow')

@php($field = 'w-full rounded-lg border border-neutral-300 px-3 py-2.5 text-[15px] focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15')

@section('content')
<div class="mx-auto max-w-2xl px-4 py-10">
    <div class="rounded-md bg-white p-6 shadow-sm sm:p-8">
        <h1 class="text-2xl font-bold text-ink-900">Đăng ký tài khoản</h1>
        <p class="mt-1 text-sm text-neutral-600">Điền một lần, lần sau đặt hàng không cần nhập lại. Ô có dấu <span class="text-primary-600">*</span> là bắt buộc.</p>

        @include('pages.account._flash', ['inline' => true])

        <form method="post" action="{{ route('register.submit') }}" class="mt-6" x-data="{ show: false }">
            @csrf
            @include('partials.customer-fields', ['nameField' => 'name'])

            <fieldset class="mt-6 grid gap-4 sm:grid-cols-2">
                <legend class="mb-3 text-base font-semibold text-ink-900">Mật khẩu</legend>
                <div>
                    <label for="password" class="mb-1 block text-sm font-medium text-ink-800">Mật khẩu <span class="text-primary-600">*</span></label>
                    <input id="password" :type="show ? 'text' : 'password'" type="password" name="password" required minlength="8" autocomplete="new-password" class="{{ $field }}">
                    <p class="mt-1 text-xs text-neutral-500">Tối thiểu 8 ký tự.</p>
                </div>
                <div>
                    <label for="password_confirmation" class="mb-1 block text-sm font-medium text-ink-800">Nhập lại mật khẩu <span class="text-primary-600">*</span></label>
                    <input id="password_confirmation" :type="show ? 'text' : 'password'" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password" class="{{ $field }}">
                </div>
                <label class="flex items-center gap-2 text-sm sm:col-span-2">
                    <input type="checkbox" x-model="show" class="h-4 w-4 accent-primary-600"> Hiện mật khẩu
                </label>
            </fieldset>

            <label class="mt-6 flex items-start gap-2 text-sm">
                <input type="checkbox" name="terms" value="1" required @checked(old('terms')) class="mt-0.5 h-4 w-4 accent-primary-600">
                <span>Tôi đồng ý để {{ config('site.brand') }} dùng thông tin này để giao hàng, báo giá và chăm sóc đơn hàng.</span>
            </label>

            <button class="mt-6 w-full rounded-lg bg-primary-600 px-5 py-3 font-semibold text-white hover:bg-primary-700">Tạo tài khoản</button>
        </form>

        <p class="mt-6 border-t border-neutral-200 pt-5 text-center text-sm">
            Đã có tài khoản? <a href="{{ route('login') }}" class="font-semibold text-primary-600 hover:underline">Đăng nhập</a>
        </p>
    </div>
</div>
@endsection
