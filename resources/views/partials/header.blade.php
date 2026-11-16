@php($menu = \App\Models\Category::active()->roots()->get(['id', 'name', 'slug']))
<div class="bg-brand-900 text-xs text-brand-100">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-2">
        <span class="hidden sm:inline">{{ config('site.tagline') }}</span>
        <div class="flex gap-4">
            @if (config('site.phone'))
                <a href="tel:{{ preg_replace('/\s+/', '', config('site.phone')) }}" class="hover:text-white">Hotline: {{ config('site.phone') }}</a>
            @endif
            @if (config('site.email'))
                <a href="mailto:{{ config('site.email') }}" class="hidden hover:text-white md:inline">{{ config('site.email') }}</a>
            @endif
        </div>
    </div>
</div>

<header class="sticky top-0 z-30 border-b border-slate-200 bg-white" x-data="{ open: false }">
    <div class="mx-auto flex max-w-7xl items-center gap-4 px-4 py-3">
        <a href="{{ route('home') }}" class="shrink-0 text-xl font-bold text-brand-700">{{ config('site.brand') }}</a>

        <form action="{{ route('search') }}" method="get" class="hidden flex-1 md:block" role="search">
            <label for="q" class="sr-only">Tìm sản phẩm</label>
            <input id="q" name="q" type="search" value="{{ request('q') }}" placeholder="Tìm theo tên, mã SKU, thương hiệu…"
                   class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm focus:border-brand-600 focus:outline-none">
        </form>

        <a href="{{ route('quote.create') }}" class="ml-auto inline-flex items-center gap-2 rounded-lg bg-accent-500 px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-accent-600 md:ml-0">
            Danh sách báo giá
            <span class="rounded-full bg-white px-2 text-xs" x-text="$store.quote.count" x-cloak></span>
        </a>

        <button type="button" class="md:hidden" @click="open = !open" aria-label="Mở menu">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
    </div>

    <nav class="border-t border-slate-100" :class="open ? 'block' : 'hidden md:block'" aria-label="Danh mục sản phẩm">
        <ul class="mx-auto flex max-w-7xl flex-col gap-1 px-4 py-2 text-sm font-medium md:flex-row md:flex-wrap md:gap-6">
            @foreach ($menu as $item)
                <li><a href="{{ $item->url() }}" class="block py-1 hover:text-brand-600">{{ $item->name }}</a></li>
            @endforeach
            <li><a href="{{ route('posts.index') }}" class="block py-1 hover:text-brand-600">Kiến thức</a></li>
        </ul>
        <form action="{{ route('search') }}" method="get" class="px-4 pb-3 md:hidden" role="search">
            <input name="q" type="search" placeholder="Tìm sản phẩm…" class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm">
        </form>
    </nav>
</header>
