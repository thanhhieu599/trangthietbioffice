<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Quản trị') | {{ config('site.brand') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-neutral-100 font-sans text-ink-700 antialiased">
    @if (session('admin_ok'))
        <header class="bg-ink-900 text-white">
            <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-4 py-3">
                <div class="flex items-center gap-6">
                    <span class="font-bold"><span class="rounded bg-primary-600 px-1.5 py-0.5">D&amp;T</span> Quản trị</span>
                    <nav class="flex gap-4 text-sm">
                        <a href="{{ route('admin.banners.index') }}" class="{{ request()->routeIs('admin.banners.*') ? 'text-white underline underline-offset-4' : 'text-neutral-300 hover:text-white' }}">Banner trang chủ</a>
                    </nav>
                </div>
                <div class="flex items-center gap-4 text-sm">
                    <a href="{{ route('home') }}" target="_blank" class="text-neutral-300 hover:text-white">Xem website ↗</a>
                    <form method="post" action="{{ route('admin.logout') }}">@csrf
                        <button class="rounded bg-primary-600 px-3 py-1.5 font-semibold hover:bg-primary-700">Đăng xuất</button>
                    </form>
                </div>
            </div>
        </header>
    @endif

    <main class="mx-auto max-w-5xl px-4 py-6">
        @if (session('ok'))
            <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('ok') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-4 rounded-md border border-primary-100 bg-primary-50 px-4 py-3 text-sm text-primary-700">
                <ul class="list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif
        @yield('content')
    </main>
</body>
</html>
