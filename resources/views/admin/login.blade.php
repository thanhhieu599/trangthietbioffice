@extends('admin.layout')

@section('title', 'Đăng nhập')

@section('content')
<div class="text-center text-white">
    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-primary-600 to-primary-800 text-base font-bold shadow-xl shadow-black/30 ring-1 ring-white/10">D&amp;T</span>
    <h1 class="mt-5 text-xl font-semibold tracking-tight">{{ config('site.brand') }}</h1>
    <p class="mt-1 text-sm text-slate-400">Đăng nhập trang quản trị nội dung</p>
</div>

<div class="adm-card mt-8 p-6 shadow-2xl shadow-black/30">
    <form method="post" action="{{ route('admin.login.submit') }}" class="space-y-5">
        @csrf
        <label class="block">
            <span class="mb-1.5 block text-sm font-medium text-adm-ink">Mật khẩu</span>
            <input type="password" name="password" required autofocus autocomplete="current-password" class="adm-input py-2.5">
        </label>
        <button class="adm-btn adm-btn-primary w-full py-2.5">Đăng nhập</button>
    </form>
</div>
<p class="mt-6 text-center text-xs text-slate-500">Khu vực dành riêng cho quản trị viên.</p>
@endsection
