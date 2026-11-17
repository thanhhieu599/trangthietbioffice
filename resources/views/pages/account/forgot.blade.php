@extends('layouts.app')

@section('title', 'Quên mật khẩu | '.config('site.brand'))
@section('robots', 'noindex, follow')

@section('content')
<div class="mx-auto max-w-md px-4 py-10">
    <div class="rounded-md bg-white p-6 shadow-sm sm:p-8">
        <h1 class="text-2xl font-bold text-ink-900">Quên mật khẩu</h1>
        <p class="mt-1 text-sm text-neutral-600">Nhập email đã đăng ký, chúng tôi sẽ gửi liên kết để đặt mật khẩu mới.</p>

        @include('pages.account._flash')

        <form method="post" action="{{ route('password.email') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label for="email" class="mb-1 block text-sm font-medium text-ink-800">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                       class="w-full rounded-lg border border-neutral-300 px-3 py-2.5 focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15">
            </div>
            <button class="w-full rounded-lg bg-primary-600 px-5 py-3 font-semibold text-white hover:bg-primary-700">Gửi liên kết đặt lại</button>
        </form>
        <p class="mt-6 text-center text-sm"><a href="{{ route('login') }}" class="text-primary-600 hover:underline">← Quay lại đăng nhập</a></p>
    </div>
</div>
@endsection
