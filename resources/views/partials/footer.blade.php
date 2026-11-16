@php
    $digits = fn (?string $s) => $s ? preg_replace('/[^0-9+]/', '', $s) : null;
    $phone = config('site.phone');
    $tel = $digits($phone);
    $zalo = config('site.zalo') ? preg_replace('/\D/', '', config('site.zalo')) : null;
    $about = config('site.about');
    $social = array_filter(config('site.social', []));
    $link = 'hover:text-white hover:underline underline-offset-2';
@endphp

<footer class="mt-auto bg-primary-600 text-[14px] text-white/90">
    {{-- ===== Khối giới thiệu D&T ===== --}}
    <div class="border-b border-white/20">
        <div class="mx-auto flex max-w-site flex-col gap-6 px-4 py-8 md:flex-row md:items-center md:gap-10">
            <a href="{{ config('site.parent_site') }}" class="flex w-full shrink-0 flex-col items-center justify-center rounded-md bg-white px-5 py-6 md:w-64"
               aria-label="trangthietbi.vn">
                @if ($footerLogo = \App\Support\SiteImage::footerLogo())
                    {{-- Logo: chép file vào public/images/logo-footer.png (hoặc .svg/.webp/.jpg) --}}
                    <img src="{{ $footerLogo }}" alt="trangthietbi.vn" class="max-h-24 w-full object-contain" loading="lazy">
                @else
                    <span class="text-[28px] font-extrabold leading-none tracking-tight">
                        <span class="text-ink-900">trang</span><span class="text-primary-600">thietbi</span><span class="text-ink-900">.vn</span>
                    </span>
                    <span class="mt-2 text-[10px] font-bold uppercase tracking-wide text-ink-900">Giữ chữ tín là việc quan trọng!</span>
                @endif
            </a>
            <div>
                <p class="text-lg font-bold uppercase leading-snug text-white">{{ $about['title'] }}</p>
                <p class="mt-2 leading-relaxed [&_strong]:text-white">{!! $about['text'] !!}</p>
                <ul class="mt-4 flex flex-wrap gap-2.5">
                    @foreach ($about['badges'] as $badge)
                        <li class="flex items-center gap-2 rounded-full border border-white/40 bg-white/10 px-4 py-2 text-[13px] font-semibold text-white">
                            <x-icon :name="$badge['icon']" class="h-4 w-4" /> {{ $badge['text'] }}
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    {{-- ===== 4 cột thông tin ===== --}}
    <div class="mx-auto grid max-w-site gap-8 px-4 py-10 sm:grid-cols-2 lg:grid-cols-[1.3fr_1fr_1.1fr_0.9fr]">
        {{-- Liên hệ --}}
        <div>
            <p class="mb-4 font-bold uppercase text-white">Liên hệ</p>
            <ul class="space-y-2.5">
                <li class="flex gap-2.5"><x-icon name="building" class="mt-0.5 h-4 w-4 shrink-0" /><span class="font-semibold text-white">{{ config('site.company') }}</span></li>
                @foreach (config('site.addresses', []) as $addr)
                    <li class="flex gap-2.5"><x-icon name="pin" class="mt-0.5 h-4 w-4 shrink-0" />
                        <span>@if ($addr['label'])<span class="font-semibold text-white">{{ $addr['label'] }}:</span> @endif{{ $addr['text'] }}</span></li>
                @endforeach
                @foreach (config('site.contacts', []) as $c)
                    <li class="flex gap-2.5"><x-icon name="user" class="mt-0.5 h-4 w-4 shrink-0" />
                        <span>{{ $c['name'] }}: <a href="tel:{{ $digits($c['phone']) }}" class="font-semibold text-white {{ $link }}">{{ $c['phone'] }}</a></span></li>
                @endforeach
                @if ($phone)
                    <li class="flex gap-2.5"><x-icon name="phone" class="mt-0.5 h-4 w-4 shrink-0" />
                        <span>Hotline: <a href="tel:{{ $tel }}" class="font-bold text-white {{ $link }}">{{ $phone }}</a></span></li>
                @endif
                @if (config('site.email'))
                    <li class="flex gap-2.5"><x-icon name="mail" class="mt-0.5 h-4 w-4 shrink-0" />
                        <a href="mailto:{{ config('site.email') }}" class="{{ $link }}">{{ config('site.email') }}</a></li>
                @endif
            </ul>
            @if ($social)
                <ul class="mt-5 flex gap-2.5">
                    @foreach ($social as $network => $url)
                        <li>
                            <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ ucfirst($network) }}"
                               class="flex h-9 w-9 items-center justify-center rounded-full bg-white text-primary-600 transition hover:bg-ink-900 hover:text-white">
                                <x-icon :name="$network" class="h-[18px] w-[18px]" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Danh mục sản phẩm: lấy theo danh mục thật của website --}}
        <div>
            <p class="mb-4 font-bold uppercase text-white">Danh mục sản phẩm</p>
            <ul class="space-y-2">
                @foreach ($menuCategories as $cat)
                    <li><a href="{{ $cat['url'] }}" class="{{ $link }}">{{ $cat['name'] }}</a></li>
                @endforeach
            </ul>
        </div>

        {{-- Chính sách --}}
        <div>
            <p class="mb-4 font-bold uppercase text-white">Chính sách</p>
            <ul class="space-y-2">
                @foreach ($footerPages as $p)
                    <li><a href="{{ $p['url'] }}" class="{{ $link }}">{{ $p['title'] }}</a></li>
                @endforeach
                <li><a href="{{ route('quote.create') }}" class="{{ $link }}">Tìm cung cấp hàng hóa theo yêu cầu</a></li>
                <li><a href="{{ config('site.parent_site') }}" class="font-semibold uppercase {{ $link }}">Về {{ parse_url(config('site.parent_site'), PHP_URL_HOST) }}</a></li>
            </ul>
        </div>

        {{-- Vận chuyển & thanh toán --}}
        <div>
            <p class="mb-4 font-bold uppercase text-white">Vận chuyển &amp; thanh toán</p>
            <ul class="flex flex-wrap gap-2">
                @foreach (config('site.payments', []) as $method)
                    <li class="rounded bg-white px-2.5 py-1.5 text-[12px] font-bold text-ink-800">{{ $method }}</li>
                @endforeach
            </ul>
            <p class="mt-4 flex gap-2.5"><x-icon name="truck" class="mt-0.5 h-4 w-4 shrink-0" />Giao tận nơi văn phòng, kho, khu công nghiệp</p>
            <p class="mt-2 flex gap-2.5"><x-icon name="clock" class="mt-0.5 h-4 w-4 shrink-0" />{{ config('site.working_hours') }}</p>

            @if (config('site.bct_url'))
                <a href="{{ config('site.bct_url') }}" target="_blank" rel="noopener nofollow"
                   class="mt-4 inline-flex items-center gap-2 rounded-md bg-white px-3 py-2 text-[#0b5cad]">
                    <x-icon name="check-circle" class="h-8 w-8" />
                    <span class="leading-tight"><span class="block text-[13px] font-extrabold uppercase">Đã thông báo</span><span class="block text-[11px] font-semibold uppercase">Bộ Công Thương</span></span>
                </a>
            @endif
        </div>
    </div>

    <div class="bg-primary-800 py-4 text-center text-[13px] text-white/80">
        © {{ date('Y') }} {{ config('site.company') }}. Bảo lưu mọi quyền.
    </div>
</footer>

{{-- Nút gọi nhanh / Zalo nổi góc phải --}}
@if ($tel || $zalo)
    <div class="fixed right-4 bottom-4 z-40 flex flex-col gap-3">
        @if ($zalo)
            <a href="https://zalo.me/{{ $zalo }}" target="_blank" rel="noopener nofollow" aria-label="Chat Zalo"
               class="flex h-12 w-12 items-center justify-center rounded-full bg-[#0068ff] text-xs font-bold text-white shadow-lg ring-2 ring-white">Zalo</a>
        @endif
        @if ($tel)
            <a href="tel:{{ $tel }}" aria-label="Gọi {{ $phone }}"
               class="flex h-12 w-12 items-center justify-center rounded-full bg-primary-600 text-white shadow-lg ring-2 ring-white">
                <x-icon name="phone" class="h-6 w-6" />
            </a>
        @endif
    </div>
@endif
