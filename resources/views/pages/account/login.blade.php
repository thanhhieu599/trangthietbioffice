@extends('layouts.app')

@section('title', 'Đăng nhập | '.config('site.brand'))
@section('description', 'Đăng nhập tài khoản '.config('site.brand').' để đặt hàng nhanh và theo dõi đơn.')
@section('robots', 'noindex, follow')

@php($field = 'w-full rounded-lg border border-neutral-300 px-3 py-2.5 text-[15px] focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15')

@section('content')
<div class="mx-auto max-w-md px-4 py-10">
    <div class="rounded-md bg-white p-6 shadow-sm sm:p-8">
        <h1 class="text-2xl font-bold text-ink-900">Đăng nhập</h1>
        <p class="mt-1 text-sm text-neutral-600">Đặt hàng nhanh hơn với thông tin giao hàng đã lưu và theo dõi đơn của anh/chị.</p>

        @include('pages.account._flash')

        <form method="post" action="{{ route('login.submit') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label for="email" class="mb-1 block text-sm font-medium text-ink-800">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="{{ $field }}">
            </div>
            <div>
                <div class="mb-1 flex items-center justify-between">
                    <label for="password" class="text-sm font-medium text-ink-800">Mật khẩu</label>
                    <a href="{{ route('password.request') }}" class="text-sm text-primary-600 hover:underline">Quên mật khẩu?</a>
                </div>
                <input id="password" type="password" name="password" required autocomplete="current-password" class="{{ $field }}">
            </div>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="remember" value="1" @checked(old('remember', true)) class="h-4 w-4 accent-primary-600"> Ghi nhớ đăng nhập
            </label>
            <button class="w-full rounded-lg bg-primary-600 px-5 py-3 font-semibold text-white hover:bg-primary-700">Đăng nhập</button>
        </form>

        <p class="mt-6 border-t border-neutral-200 pt-5 text-center text-sm">
            Chưa có tài khoản? <a href="{{ route('register') }}" class="font-semibold text-primary-600 hover:underline">Đăng ký ngay</a>
        </p>
        <p class="mt-2 text-center text-sm text-neutral-500">
            Hoặc <a href="{{ route('quote.create') }}" class="text-primary-600 hover:underline">đặt hàng không cần tài khoản</a>
        </p>
    </div>
</div>
@endsection
