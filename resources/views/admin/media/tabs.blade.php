{{-- Thanh tab chung của mục "Hình ảnh" --}}
<div class="flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="adm-h1">Hình ảnh &amp; banner</h1>
    </div>
</div>
<nav class="mt-5 flex w-full flex-wrap gap-1 rounded-xl bg-slate-200/60 p-1 text-sm sm:w-max">
    @foreach ([
        ['admin.banners.index', 'Trang chủ', route('home')],
        ['admin.media.categories', 'Danh mục', null],
        ['admin.media.flash', 'Flash Sale', route('flash-sale')],
        ['admin.media.brands', 'Thương hiệu', route('brands.index')],
        ['admin.media.contact', 'Liên hệ', route('contact')],
    ] as [$r, $label])
        <a href="{{ route($r) }}" class="adm-tab {{ request()->routeIs($r) ? 'adm-tab-active' : '' }}">{{ $label }}</a>
    @endforeach
</nav>
