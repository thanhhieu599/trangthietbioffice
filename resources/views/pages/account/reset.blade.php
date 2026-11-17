@extends('layouts.app')

@section('title', 'Đặt mật khẩu mới | '.config('site.brand'))
@section('robots', 'noindex, nofollow')
@section('canonical', 'none')

@php($field = 'w-full rounded-lg border border-neutral-300 px-3 py-2.5 focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15')

@section('content')
<div class="mx-auto max-w-md px-4 py-10">
    <div class="rounded-md bg-white p-6 shadow-sm sm:p-8">
        <h1 class="text-2xl font-bold text-ink-900">Đặt mật khẩu mới</h1>

        @include('pages.account._flash')

        <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div>
                <label for="email" class="mb-1 block text-sm font-medium text-ink-800">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="email" class="{{ $field }}">
            </div>
            <div>
                <label for="password" class="mb-1 block text-sm font-medium text-ink-800">Mật khẩu mới</label>
                <input id="password" type="password" name="password" required minlength="8" autocomplete="new-password" class="{{ $field }}">
            </div>
            <div>
                <label for="password_confirmation" class="mb-1 block text-sm font-medium text-ink-800">Nhập lại mật khẩu mới</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password" class="{{ $field }}">
            </div>
            <button class="w-full rounded-lg bg-primary-600 px-5 py-3 font-semibold text-white hover:bg-primary-700">Lưu mật khẩu mới</button>
        </form>
    </div>
</div>
@endsection
