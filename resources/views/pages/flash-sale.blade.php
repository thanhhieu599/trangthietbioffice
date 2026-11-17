@extends('layouts.app')

@section('title', ($sale ? 'Flash Sale – '.$sale->name : 'Flash Sale').' | '.config('site.brand'))
@section('description', $sale
    ? 'Flash Sale '.$sale->name.': '.$items->count().' sản phẩm văn phòng phẩm giá sốc đến '.$sale->ends_at->format('H:i d/m/Y').'. Số lượng có hạn.'
    : 'Chương trình Flash Sale văn phòng phẩm giá sốc của '.config('site.brand').'.')
@section('canonical', route('flash-sale'))
@unless ($sale && $items->isNotEmpty())
    @section('robots', 'noindex, follow')
@endunless

@section('content')
<div class="mx-auto max-w-site px-4 py-5">
    @include('partials.breadcrumb', ['items' => [['name' => 'Flash Sale', 'url' => route('flash-sale')]]])

    @if ($sale && $items->isNotEmpty())
        <div x-data="countdown(@js($sale->ends_at->toIso8601String()), {{ $sale->cycleSeconds() }})">

            {{-- ===== Banner ===== --}}
            @if ($sale->banner)
                <div class="overflow-hidden rounded-xl shadow-sm">
                    <picture>
                        @if ($sale->banner_mobile)<source media="(max-width: 767px)" srcset="{{ $sale->bannerUrl($sale->banner_mobile) }}">@endif
                        <img src="{{ $sale->bannerUrl() }}" alt="Flash Sale – {{ $sale->name }}" width="1200" height="300" fetchpriority="high" class="aspect-[1200/300] w-full object-cover max-md:aspect-[2/1]">
                    </picture>
                </div>
                <div class="mt-3 flex flex-wrap items-center gap-x-6 gap-y-3 rounded-xl bg-white px-5 py-4 shadow-sm">
                    <h1 class="leading-none">@include('partials.flash.logo')<span class="sr-only"> – {{ $sale->name }}</span></h1>
                    <p class="font-semibold text-ink-900">{{ $sale->name }}</p>
                    <div class="flex items-center gap-2.5 sm:ml-auto">
                        <span class="text-sm text-neutral-600" x-text="done ? 'Chương trình đã kết thúc' : 'Kết thúc trong'">Kết thúc trong</span>
                        <span x-show="!done">@include('partials.flash.countdown')</span>
                    </div>
                </div>
            @else
                <div class="relative overflow-hidden rounded-xl bg-[linear-gradient(120deg,#b31217_0%,#e3262d_50%,#ff6a3d_100%)] px-6 py-10 text-white shadow-[0_12px_40px_-14px_rgba(215,25,32,.6)] md:px-12 md:py-14">
                    <svg class="pointer-events-none absolute -top-16 right-10 h-96 w-64 rotate-12 text-white/10" viewBox="0 0 20 30" aria-hidden="true"><path d="M12.5 0 1 17h7l-2.5 13L19 11h-7.5L12.5 0z" fill="currentColor"/></svg>
                    <svg class="pointer-events-none absolute -bottom-24 left-1/2 h-80 w-56 -rotate-12 text-white/[0.06]" viewBox="0 0 20 30" aria-hidden="true"><path d="M12.5 0 1 17h7l-2.5 13L19 11h-7.5L12.5 0z" fill="currentColor"/></svg>
                    <div class="pointer-events-none absolute -top-20 -left-20 h-72 w-72 rounded-full bg-[#ffd400]/20 blur-3xl"></div>

                    <div class="relative grid items-center gap-8 md:grid-cols-[1fr_auto]">
                        <div>
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold tracking-wide uppercase ring-1 ring-white/25 backdrop-blur">
                                <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-[#ffd400]"></span> Đang diễn ra
                            </span>
                            <h1 class="mt-4">
                                <span class="inline-flex items-center gap-2 text-4xl font-black italic tracking-tight md:text-6xl">
                                    FLASH
                                    <svg class="h-12 w-8 drop-shadow md:h-16 md:w-11" viewBox="0 0 20 30" aria-hidden="true"><path d="M12.5 0 1 17h7l-2.5 13L19 11h-7.5L12.5 0z" fill="#ffd400"/></svg>
                                    SALE
                                </span>
                                <span class="mt-2 block text-lg font-semibold text-white/95 md:text-2xl">{{ $sale->name }}</span>
                            </h1>
                            <p class="mt-2 max-w-xl text-white/85">Giá sốc cho {{ $items->count() }} sản phẩm văn phòng phẩm – số lượng có hạn, hết giờ là hết ưu đãi.</p>
                        </div>
                        <div class="rounded-2xl bg-black/20 p-5 ring-1 ring-white/20 backdrop-blur-sm">
                            <p class="mb-3 text-center text-sm font-medium text-white/90" x-text="done ? 'Chương trình đã kết thúc' : 'Kết thúc sau'">Kết thúc sau</p>
                            <div x-show="!done" class="flex justify-center">@include('partials.flash.countdown', ['big' => true, 'dark' => true])</div>
                            <p class="mt-3 text-center text-xs text-white/75">đến {{ $sale->ends_at->format('H:i, d/m/Y') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- ===== Chip danh mục (nhảy nhanh) ===== --}}
            @if ($groups->count() > 1)
                <nav class="sticky top-0 z-20 -mx-4 mt-4 bg-neutral-100/90 px-4 py-2.5 backdrop-blur" aria-label="Danh mục Flash Sale">
                    <ul class="no-scrollbar flex gap-2 overflow-x-auto">
                        @foreach ($groups as $g)
                            <li class="shrink-0">
                                <a href="#fs-{{ $g['slug'] }}" class="inline-flex items-center gap-1.5 rounded-full bg-white px-4 py-2 text-sm font-medium text-ink-800 shadow-sm ring-1 ring-neutral-200 transition hover:text-primary-600 hover:ring-primary-600">
                                    {{ $g['name'] }} <span class="rounded-full bg-primary-50 px-1.5 text-xs font-semibold text-primary-600">{{ $g['items']->count() }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            @endif

            {{-- ===== Sản phẩm theo danh mục ===== --}}
            <div class="mt-4 space-y-6" :class="done && 'pointer-events-none opacity-50 grayscale'">
                @foreach ($groups as $g)
                    <section id="fs-{{ $g['slug'] }}" class="scroll-mt-20 rounded-xl bg-[#fff4f2] p-3 ring-1 ring-primary-100 sm:p-4" aria-labelledby="fs-h-{{ $g['slug'] }}">
                        <div class="mb-3 flex items-center justify-between gap-3 px-1">
                            <h2 id="fs-h-{{ $g['slug'] }}" class="flex items-center gap-2.5 text-lg font-bold text-ink-900">
                                <span class="h-6 w-1.5 rounded-full bg-gradient-to-b from-[#ff9a3c] to-primary-600"></span>
                                {{ $g['name'] }}
                                <span class="text-sm font-medium text-neutral-500">({{ $g['items']->count() }})</span>
                            </h2>
                            @if ($g['category'])
                                <a href="{{ $g['category']->url() }}" class="inline-flex shrink-0 items-center gap-1 text-sm text-neutral-600 hover:text-primary-600">Xem danh mục <x-icon name="chevron-right" class="h-4 w-4" /></a>
                            @endif
                        </div>
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                            @foreach ($g['items'] as $item)
                                @include('partials.flash.card')
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        </div>
    @else
        {{-- ===== Chưa có chương trình ===== --}}
        <div class="rounded-xl bg-white px-6 py-14 text-center">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-primary-50">
                <svg class="h-9 w-6" viewBox="0 0 20 30" aria-hidden="true"><path d="M12.5 0 1 17h7l-2.5 13L19 11h-7.5L12.5 0z" fill="#d71920"/></svg>
            </div>
            <h1 class="mt-4 text-xl font-bold text-ink-900">Hiện chưa có chương trình Flash Sale</h1>
            <p class="mt-1 text-neutral-600">Chương trình tiếp theo sẽ sớm diễn ra. Trong lúc chờ, anh/chị có thể xem sản phẩm hoặc gửi yêu cầu báo giá sỉ.</p>
            <div class="mt-6 flex flex-wrap justify-center gap-3">
                <a href="{{ route('home') }}" class="rounded-lg bg-primary-600 px-5 py-2.5 font-semibold text-white hover:bg-primary-700">Về trang chủ</a>
                <a href="{{ route('quote.create') }}" class="rounded-lg border border-neutral-300 px-5 py-2.5 font-semibold text-ink-900 hover:border-primary-600 hover:text-primary-600">Gửi yêu cầu báo giá</a>
            </div>
        </div>
    @endif
</div>
@endsection
