@php
    $isHome = request()->routeIs('home');
    $phone = config('site.phone');
    $tel = $phone ? preg_replace('/[^0-9+]/', '', $phone) : null;
    $nav = [
        ['label' => 'Trang chủ', 'url' => route('home'), 'active' => $isHome],
        ['label' => 'Tin tức', 'url' => route('posts.index'), 'active' => request()->routeIs('posts.*')],
        ['label' => 'Thương hiệu', 'url' => route('brands.index'), 'active' => request()->routeIs('brands.*')],
        ['label' => 'Liên hệ', 'url' => route('contact'), 'active' => request()->routeIs('contact')],
    ];
@endphp

<header x-data="{ drawer: false }" @keydown.escape.window="drawer = false">
    {{-- ===== Hàng 1: logo – tìm kiếm – hotline – giỏ báo giá ===== --}}
    <div class="bg-white">
        <div class="mx-auto flex max-w-site items-center gap-3 px-4 py-3 lg:gap-8">
            <button type="button" class="-ml-1 p-1 text-ink-800 lg:hidden" @click="drawer = true" aria-label="Mở danh mục">
                <x-icon name="menu" class="h-7 w-7" />
            </button>

            <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2" aria-label="{{ config('site.brand') }} – Trang chủ">
                @if ($logo = \App\Support\SiteImage::logo())
                    {{-- Logo: chép file vào public/images/logo.png (hoặc .svg/.webp/.jpg) --}}
                    <img src="{{ $logo }}" alt="{{ config('site.brand') }}" class="h-10 w-auto max-w-[180px] object-contain lg:h-12 lg:max-w-[240px]">
                @else
                    <span class="rounded bg-primary-600 px-2 py-1 text-lg font-bold leading-none text-white lg:text-xl">D&amp;T</span>
                    <span class="leading-tight">
                        <span class="block text-lg font-bold tracking-wide text-ink-900 lg:text-xl">OFFICE</span>
                        <span class="hidden text-[10px] font-medium uppercase tracking-wider text-neutral-500 sm:block">Văn phòng phẩm doanh nghiệp</span>
                    </span>
                @endif
            </a>

            <form action="{{ route('search') }}" method="get" role="search" class="hidden flex-1 md:flex">
                <label for="q" class="sr-only">Tìm sản phẩm</label>
                <input id="q" name="q" type="search" value="{{ request('q') }}" placeholder="Bạn tìm giấy in, bút, bìa hồ sơ, băng keo…"
                       class="min-w-0 flex-1 rounded-l-md border border-r-0 border-neutral-300 px-4 py-2.5 text-sm focus:border-primary-600 focus:outline-none">
                <button type="submit" class="rounded-r-md bg-primary-600 px-5 text-white hover:bg-primary-700" aria-label="Tìm kiếm">
                    <x-icon name="search" />
                </button>
            </form>

            <div class="ml-auto flex items-center gap-5 md:ml-0">
                @if ($phone)
                    <a href="tel:{{ $tel }}" class="hidden items-center gap-2 lg:flex">
                        <x-icon name="headset" class="h-8 w-8 text-ink-800" />
                        <span class="leading-tight">
                            <span class="block text-xs text-neutral-500">Tư vấn miễn phí</span>
                            <span class="block font-bold text-primary-600">{{ $phone }}</span>
                        </span>
                    </a>
                @endif

                <a href="{{ route('quote.create') }}" class="flex items-center gap-2" x-data>
                    <span class="relative">
                        <x-icon name="cart" class="h-8 w-8 text-ink-800" />
                        <span class="absolute -top-1.5 -right-2 flex h-5 min-w-5 items-center justify-center rounded-full bg-primary-600 px-1 text-[11px] font-bold text-white"
                              x-text="$store.quote.count" x-cloak></span>
                    </span>
                    <span class="hidden leading-tight sm:block">
                        <span class="block text-xs text-neutral-500">Giỏ hàng</span>
                        <span class="block text-sm font-semibold text-ink-900">Báo giá</span>
                    </span>
                </a>
            </div>
        </div>

        <form action="{{ route('search') }}" method="get" role="search" class="flex px-4 pb-3 md:hidden">
            <input name="q" type="search" value="{{ request('q') }}" placeholder="Bạn tìm sản phẩm gì?"
                   class="min-w-0 flex-1 rounded-l-md border border-r-0 border-neutral-300 px-3 py-2 text-sm focus:outline-none">
            <button type="submit" class="rounded-r-md bg-primary-600 px-4 text-white" aria-label="Tìm kiếm"><x-icon name="search" /></button>
        </form>
    </div>

    {{-- ===== Hàng 2: thanh menu chính màu đỏ ===== --}}
    <nav class="bg-primary-600 text-white" aria-label="Menu chính">
        <div class="relative mx-auto flex max-w-site items-stretch px-4">
            {{-- Nút Danh mục sản phẩm (máy tính). Trang chủ: luôn mở bên trái banner. --}}
            <div class="relative hidden w-[260px] shrink-0 lg:block"
                 x-data="{ open: @js($isHome) }"
                 @if (! $isHome) @mouseenter="open = true" @mouseleave="open = false" @endif>
                <button type="button" class="flex h-full w-full items-center gap-3 bg-ink-900 px-4 py-3 text-[15px] font-semibold"
                        @if (! $isHome) @click="open = !open" @endif :aria-expanded="open">
                    <x-icon name="menu" />
                    <span class="flex-1 text-left">Danh mục sản phẩm</span>
                    <x-icon name="chevron-down" class="h-4 w-4 transition" ::class="open && 'rotate-180'" />
                </button>

                {{-- Trang chủ: menu cao bằng banner, hiện tối đa $homeLimit mục; nhiều hơn thì có nút "Xem thêm" để mở hết --}}
                @php($homeLimit = 9)
                @php($homeMore = $isHome && $menuCategories->count() > $homeLimit + 1)
                <ul class="absolute top-full left-0 z-40 w-full bg-ink-800 py-1.5 text-[14px] shadow-lg {{ $isHome ? 'min-h-[412px]' : '' }}"
                    x-data="{ all: false }"
                    x-show="open" @if (! $isHome) x-cloak x-transition.opacity @endif>
                    @foreach ($menuCategories as $i => $cat)
                        <li class="group/item" @if ($homeMore && $i >= $homeLimit) x-show="all" x-cloak @endif>
                            <a href="{{ $cat['url'] }}" class="flex items-center justify-between px-4 py-2 text-neutral-100 hover:bg-primary-600 hover:text-white">
                                <span>{{ $cat['name'] }}</span>
                                @if (! empty($cat['children']))<x-icon name="chevron-right" class="h-4 w-4 opacity-70" />@endif
                            </a>
                            @if (! empty($cat['children']))
                                <div class="invisible absolute top-0 left-full z-40 h-full min-h-full w-[280px] bg-white p-4 text-ink-800 opacity-0 shadow-xl transition group-hover/item:visible group-hover/item:opacity-100">
                                    <a href="{{ $cat['url'] }}" class="mb-2 block border-b border-neutral-200 pb-2 font-bold uppercase text-primary-600">{{ $cat['name'] }}</a>
                                    <ul class="space-y-1">
                                        @foreach ($cat['children'] as $child)
                                            <li><a href="{{ $child['url'] }}" class="block py-1 hover:text-primary-600">{{ $child['name'] }}</a></li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </li>
                    @endforeach
                    @if ($homeMore)
                        <li>
                            <button type="button" @click="all = !all"
                                    class="flex w-full items-center justify-between px-4 py-2 font-semibold text-primary-100 hover:bg-primary-600 hover:text-white">
                                <span x-text="all ? 'Thu gọn' : 'Xem thêm {{ $menuCategories->count() - $homeLimit }} danh mục'"></span>
                                <x-icon name="chevron-down" class="h-4 w-4 transition" ::class="all && 'rotate-180'" />
                            </button>
                        </li>
                    @endif
                </ul>
            </div>

            <ul class="no-scrollbar -mx-4 flex flex-1 items-stretch overflow-x-auto text-[13px] font-semibold sm:mx-0 sm:text-[15px] lg:pl-6">
                @foreach ($nav as $item)
                    <li class="shrink-0">
                        <a href="{{ $item['url'] }}" @if ($item['active']) aria-current="page" @endif
                           class="flex h-full items-center px-3 py-3 uppercase tracking-wide hover:bg-primary-700 sm:px-4 {{ $item['active'] ? 'bg-primary-700' : '' }}">
                            {{ $item['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </nav>

    {{-- ===== Ngăn kéo danh mục (điện thoại) ===== --}}
    <div class="fixed inset-0 z-50 lg:hidden" x-show="drawer" x-cloak>
        <div class="absolute inset-0 bg-black/50" @click="drawer = false" x-show="drawer" x-transition.opacity></div>
        <aside class="absolute inset-y-0 left-0 flex w-[85%] max-w-xs flex-col bg-white" x-show="drawer"
               x-transition:enter="transition duration-200" x-transition:enter-start="-translate-x-full"
               x-transition:leave="transition duration-150" x-transition:leave-end="-translate-x-full">
            <div class="flex items-center justify-between bg-primary-600 px-4 py-3 font-semibold text-white">
                Danh mục sản phẩm
                <button type="button" @click="drawer = false" aria-label="Đóng"><x-icon name="close" /></button>
            </div>
            <ul class="flex-1 overflow-y-auto text-[15px]">
                @foreach ($menuCategories as $cat)
                    <li class="border-b border-neutral-100" x-data="{ sub: false }">
                        <div class="flex items-center">
                            <a href="{{ $cat['url'] }}" class="flex-1 px-4 py-3 text-ink-800">{{ $cat['name'] }}</a>
                            @if (! empty($cat['children']))
                                <button type="button" class="px-4 py-3 text-neutral-500" @click="sub = !sub" :aria-expanded="sub" aria-label="Mở danh mục con">
                                    <x-icon name="chevron-down" class="h-4 w-4 transition" ::class="sub && 'rotate-180'" />
                                </button>
                            @endif
                        </div>
                        @if (! empty($cat['children']))
                            <ul class="bg-neutral-50 pb-2" x-show="sub" x-cloak>
                                @foreach ($cat['children'] as $child)
                                    <li><a href="{{ $child['url'] }}" class="block px-8 py-2 text-sm text-neutral-600">{{ $child['name'] }}</a></li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ul>
            @if ($phone)
                <a href="tel:{{ $tel }}" class="flex items-center justify-center gap-2 bg-ink-900 py-3 font-semibold text-white">
                    <x-icon name="phone" /> {{ $phone }}
                </a>
            @endif
        </aside>
    </div>
</header>
