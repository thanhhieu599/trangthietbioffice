<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Quản trị') | {{ config('site.brand') }}</title>
    {{-- admin.js phải nạp trước app.js (app.js khởi động Alpine) --}}
    @vite(['resources/css/app.css', 'resources/js/admin.js', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-adm-bg font-admin text-slate-700 antialiased">
@if (session('admin_ok'))
    @php
        $drafts = \App\Models\Post::whereNull('published_at')->count();
        $runningFlash = \App\Models\FlashSale::running()->exists();
        $pendingComments = \App\Models\PostComment::where('is_approved', false)->count();
        $menu = [
            'Tổng quan' => [
                ['admin.dashboard', 'admin.dashboard', 'Tổng quan', 'home', null],
            ],
            'Nội dung' => [
                ['admin.posts.index', 'admin.posts.*', 'Bài viết', 'document', $drafts ?: null],
                ['admin.comments.index', 'admin.comments.*', 'Bình luận', 'chat', $pendingComments ?: null],
            ],
            'Sản phẩm' => [
                ['admin.products.index', 'admin.products.*', 'Sản phẩm', 'box', null],
                ['admin.categories.index', 'admin.categories.*', 'Danh mục', 'grid', null],
                ['admin.flash-sales.index', 'admin.flash-sales.*', 'Flash Sale', 'bolt', $runningFlash ? 'ON' : null],
            ],
        ];
        $icons = [
            'home' => '<path d="M3 11 12 4l9 7"/><path d="M5 10v10h5v-6h4v6h5V10"/>',
            'document' => '<path d="M6 3h8l4 4v14H6z"/><path d="M14 3v4h4M9 12h6M9 16h6"/>',
            'box' => '<path d="m12 3 8 4.5v9L12 21l-8-4.5v-9z"/><path d="m4 7.5 8 4.5 8-4.5M12 12v9"/>',
            'grid' => '<rect x="4" y="4" width="6.5" height="6.5" rx="1.5"/><rect x="13.5" y="4" width="6.5" height="6.5" rx="1.5"/><rect x="4" y="13.5" width="6.5" height="6.5" rx="1.5"/><rect x="13.5" y="13.5" width="6.5" height="6.5" rx="1.5"/>',
            'chat' => '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"/><path d="M8.5 11h7M8.5 14.5h4"/>',
            'bolt' =>'<path d="M13 2 4 14h7l-1 8 9-12h-7l1-8z"/>',
            'eye' =>'<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
            'logout' => '<path d="M15 4h4v16h-4M10 8l-4 4 4 4M6 12h10"/>',
            'external' => '<path d="M14 4h6v6M20 4l-9 9M18 14v6H4V6h6"/>',
        ];
        $svg = fn ($name, $cls = 'h-5 w-5') => '<svg class="'.$cls.' shrink-0" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">'.$icons[$name].'</svg>';
        // Tên mục hiện tại cho thanh tiêu đề
        $section = collect($menu)->flatten(1)->first(fn ($i) => request()->routeIs($i[1]))[2] ?? null;
    @endphp

    <div x-data="{ open: false }" class="lg:pl-[17rem]">
        {{-- ===== Thanh menu bên trái ===== --}}
        <div x-show="open" x-cloak x-transition.opacity @click="open = false" class="fixed inset-0 z-30 bg-slate-950/50 backdrop-blur-sm lg:hidden"></div>
        <aside class="fixed inset-y-0 left-0 z-40 flex w-[17rem] flex-col bg-gradient-to-b from-adm-side to-adm-side-2 text-white shadow-2xl shadow-black/20 transition-transform duration-300 lg:shadow-none"
               :class="open ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-6 pt-6 pb-5">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-primary-600 to-primary-800 text-[13px] font-bold tracking-tight shadow-lg shadow-primary-900/40 ring-1 ring-white/10">D&amp;T</span>
                <span class="min-w-0">
                    <span class="block truncate text-[15px] font-semibold leading-tight">{{ config('site.brand') }}</span>
                    <span class="mt-0.5 block text-xs text-slate-400">Quản trị nội dung</span>
                </span>
            </a>
            <div class="mx-6 h-px bg-gradient-to-r from-white/0 via-white/10 to-white/0"></div>

            <nav class="flex-1 overflow-y-auto px-4 py-5">
                @foreach ($menu as $group => $items)
                    <p class="mb-2 px-3 text-[11px] font-semibold tracking-[0.14em] text-slate-500 uppercase {{ $loop->first ? '' : 'mt-6' }}">{{ $group }}</p>
                    @foreach ($items as [$route, $pattern, $label, $icon, $badge])
                        @php($active = request()->routeIs($pattern))
                        <a href="{{ route($route) }}"
                           class="relative mb-1 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ $active ? 'bg-white/[0.08] text-white' : 'text-slate-400 hover:bg-white/[0.04] hover:text-slate-100' }}">
                            @if ($active)<span class="absolute top-1/2 -left-4 h-6 w-1 -translate-y-1/2 rounded-r-full bg-primary-600"></span>@endif
                            <span class="{{ $active ? 'text-white' : 'text-slate-500' }}">{!! $svg($icon) !!}</span>
                            <span class="flex-1">{{ $label }}</span>
                            @if ($badge)
                                <span class="min-w-[1.375rem] rounded-full bg-primary-600 px-1.5 py-0.5 text-center text-[11px] font-semibold text-white" title="{{ $badge === 'ON' ? 'Chương trình đang diễn ra' : ($label === 'Bình luận' ? 'Bình luận chờ duyệt' : 'Bài nháp chưa đăng') }}">{{ $badge }}</span>
                            @endif
                        </a>
                    @endforeach
                @endforeach
            </nav>

            <div class="space-y-1 border-t border-white/[0.06] px-4 py-4">
                <a href="{{ route('home') }}" target="_blank" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-400 transition hover:bg-white/[0.04] hover:text-slate-100">
                    <span class="text-slate-500">{!! $svg('eye') !!}</span> Xem website
                </a>
                <form method="post" action="{{ route('admin.logout') }}">@csrf
                    <button class="flex w-full cursor-pointer items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-400 transition hover:bg-white/[0.04] hover:text-slate-100">
                        <span class="text-slate-500">{!! $svg('logout') !!}</span> Đăng xuất
                    </button>
                </form>
            </div>
        </aside>

        {{-- ===== Thanh tiêu đề ===== --}}
        <header class="sticky top-0 z-20 border-b border-adm-line bg-white/85 backdrop-blur-md">
            <div class="flex h-16 items-center gap-3 px-4 lg:px-8">
                <button type="button" @click="open = true" class="-ml-1 rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden" aria-label="Mở menu">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <nav class="flex min-w-0 items-center gap-2 text-sm" aria-label="Vị trí">
                    <a href="{{ route('admin.dashboard') }}" class="hidden text-slate-500 hover:text-adm-ink sm:inline">Quản trị</a>
                    @if ($section && $section !== 'Tổng quan')
                        <span class="hidden text-slate-300 sm:inline">/</span>
                        <span class="truncate font-medium text-adm-ink">{{ $section }}</span>
                    @else
                        <span class="hidden text-slate-300 sm:inline">/</span>
                        <span class="truncate font-medium text-adm-ink">@yield('title', 'Tổng quan')</span>
                    @endif
                </nav>
                <div class="ml-auto flex items-center gap-3">
                    <a href="{{ route('home') }}" target="_blank" class="adm-btn adm-btn-ghost hidden py-1.5 sm:inline-flex">
                        {!! $svg('external', 'h-4 w-4') !!} Xem website
                    </a>
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-adm-ink text-xs font-semibold text-white ring-4 ring-slate-100" title="Quản trị viên">AD</span>
                </div>
            </div>
        </header>

        <main class="mx-auto @yield('width', 'max-w-5xl') px-4 py-7 lg:px-8">
            @include('admin.partials.flash')
            @yield('content')
        </main>
    </div>
@else
    <main class="flex min-h-screen items-center justify-center bg-gradient-to-br from-adm-side via-adm-side-2 to-[#1b2540] px-4 py-10">
        <div class="w-full max-w-sm">
            @include('admin.partials.flash')
            @yield('content')
        </div>
    </main>
@endif
</body>
</html>
