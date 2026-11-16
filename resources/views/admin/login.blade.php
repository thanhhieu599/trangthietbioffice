@extends('admin.layout')

@section('title', 'Đăng nhập')

@section('content')
<div class="mx-auto mt-16 max-w-sm rounded-md bg-white p-6 shadow-sm">
    <p class="text-center text-lg font-bold text-ink-900"><span class="rounded bg-primary-600 px-1.5 py-0.5 text-white">D&amp;T</span> Quản trị website</p>
    <form method="post" action="{{ route('admin.login.submit') }}" class="mt-6 space-y-4">
        @csrf
        <label class="block">
            <span class="mb-1 block text-sm font-medium">Mật khẩu</span>
            <input type="password" name="password" required autofocus autocomplete="current-password"
                   class="w-full rounded border border-neutral-300 px-3 py-2.5 focus:border-primary-600 focus:outline-none">
        </label>
        <button class="w-full rounded bg-primary-600 py-2.5 font-semibold text-white hover:bg-primary-700">Đăng nhập</button>
    </form>
</div>
@endsection
