{{-- Khung trang tài khoản: menu bên trái + nội dung. Dùng: @extends('pages.account._layout') + @section('account') --}}
@extends('layouts.app')

@section('robots', 'noindex, nofollow')
@section('canonical', 'none')

@section('content')
@php
    $tabs = [
        ['account.show', 'Thông tin tài khoản', 'user'],
        ['account.orders', 'Đơn hàng của tôi', 'clipboard'],
    ];
@endphp
<div class="mx-auto max-w-site px-4 py-6">
    @include('partials.breadcrumb', ['items' => [['name' => 'Tài khoản', 'url' => route('account.show')]]])
    <div class="grid gap-6 lg:grid-cols-[250px_1fr]">
        <aside class="h-max rounded-md bg-white p-4">
            <div class="flex items-center gap-3 border-b border-neutral-200 pb-4">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-primary-600 text-lg font-bold text-white">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                <div class="min-w-0">
                    <p class="truncate font-semibold text-ink-900">{{ auth()->user()->name }}</p>
                    <p class="truncate text-xs text-neutral-500">{{ auth()->user()->email }}</p>
                </div>
            </div>
            <nav class="mt-3 space-y-1 text-[15px]" aria-label="Tài khoản">
                @foreach ($tabs as [$route, $label, $icon])
                    @php($on = request()->routeIs($route) || ($route === 'account.orders' && request()->routeIs('account.order')))
                    <a href="{{ route($route) }}" class="flex items-center gap-2.5 rounded-md px-3 py-2 {{ $on ? 'bg-primary-50 font-semibold text-primary-700' : 'text-ink-700 hover:bg-neutral-100' }}">
                        <x-icon :name="$icon" class="h-5 w-5" /> {{ $label }}
                    </a>
                @endforeach
                <a href="{{ route('quote.create') }}" class="flex items-center gap-2.5 rounded-md px-3 py-2 text-ink-700 hover:bg-neutral-100">
                    <x-icon name="cart" class="h-5 w-5" /> Giỏ hàng / Đặt hàng
                </a>
                <form method="post" action="{{ route('logout') }}">@csrf
                    <button class="flex w-full items-center gap-2.5 rounded-md px-3 py-2 text-left text-ink-700 hover:bg-neutral-100">
                        <x-icon name="close" class="h-5 w-5" /> Đăng xuất
                    </button>
                </form>
            </nav>
        </aside>
        <div class="min-w-0">
            @yield('account')
        </div>
    </div>
</div>
@endsection
