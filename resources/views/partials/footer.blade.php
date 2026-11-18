@php
    $digits = fn (?string $s) => $s ? preg_replace('/[^0-9+]/', '', $s) : null;
    $phone = config('site.phone');
    $tel = $digits($phone);
    $zalo = config('site.zalo') ? preg_replace('/\D/', '', config('site.zalo')) : null;
    $about = config('site.about');
    $social = array_filter(config('site.social', []));
    $link = 'transition hover:text-amber-300';
    // Tiêu đề cột: chữ trắng + gạch ngắn chuyển màu đỏ -> vàng
    $heading = 'relative mb-5 pb-2.5 font-bold uppercase tracking-wide text-white after:absolute after:bottom-0 after:left-0 after:h-[3px] after:w-10 after:rounded-full after:bg-gradient-to-r after:from-primary-500 after:to-amber-400';
    $iconBox = 'mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-white/10 text-amber-300';@endphp

<footer class="relative mt-auto overflow-hidden bg-[linear-gradient(160deg,#14284a_0%,#1a3460_55%,#1f3d70_100%)] text-[14px] text-slate-300">
    {{-- Dải màu nhấn trên cùng + vầng sáng trang trí --}}
    <div class="h-1 bg-gradient-to-r from-primary-600 via-[#e0663a] to-amber-400"></div>
    <div class="pointer-events-none absolute -top-32 -right-24 h-80 w-80 rounded-full bg-primary-500/20 blur-3xl" aria-hidden="true"></div>
    <div class="pointer-events-none absolute top-1/2 -left-32 h-96 w-96 rounded-full bg-sky-400/10 blur-3xl" aria-hidden="true"></div>

    {{-- ===== Khối giới thiệu D&T ===== --}}
    <div class="relative mx-auto max-w-site px-4 pt-10">
        <div class="flex flex-col gap-6 rounded-2xl bg-white/[0.06] p-5 ring-1 ring-white/10 backdrop-blur-sm md:flex-row md:items-center md:gap-10 md:p-7">
            <a href="{{ config('site.parent_site') }}" class="flex w-full shrink-0 flex-col items-center justify-center rounded-xl bg-white px-5 py-6 shadow-lg shadow-black/20 md:w-64"
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
                <p class="mt-2 leading-relaxed text-slate-300 [&_strong]:text-amber-300">{!! $about['text'] !!}</p>
            </div>
        </div>
    </div>

    {{-- ===== 4 cột thông tin ===== --}}
    <div class="relative mx-auto grid max-w-site gap-8 px-4 py-10 sm:grid-cols-2 lg:grid-cols-[1.3fr_1fr_1.1fr_0.9fr]">
        {{-- Liên hệ --}}
        <div>
            <p class="{{ $heading }}">Liên hệ</p>
            <ul class="space-y-2.5">
                <li class="flex items-start gap-2.5"><span class="{{ $iconBox }}"><x-icon name="building" class="h-4 w-4" /></span><span class="pt-1 font-semibold text-white">{{ config('site.company') }}</span></li>
                @foreach (config('site.addresses', []) as $addr)
                    <li class="flex items-start gap-2.5"><span class="{{ $iconBox }}"><x-icon name="pin" class="h-4 w-4" /></span>
                        <span class="pt-1">@if ($addr['label'])<span class="font-semibold text-white">{{ $addr['label'] }}:</span> @endif{{ $addr['text'] }}</span></li>
                @endforeach
                @foreach (config('site.contacts', []) as $c)
                    <li class="flex items-start gap-2.5"><span class="{{ $iconBox }}"><x-icon name="user" class="h-4 w-4" /></span>
                        <span class="pt-1">{{ $c['name'] }}: <a href="tel:{{ $digits($c['phone']) }}" class="font-semibold text-white {{ $link }}">{{ $c['phone'] }}</a></span></li>
                @endforeach
                @if (config('site.email'))
                    <li class="flex items-start gap-2.5"><span class="{{ $iconBox }}"><x-icon name="mail" class="h-4 w-4" /></span>
                        <a href="mailto:{{ config('site.email') }}" class="pt-1 {{ $link }}">{{ config('site.email') }}</a></li>
                @endif
            </ul>
            @if ($social)
                <ul class="mt-5 flex gap-2.5">
                    @foreach ($social as $network => $url)
                        <li>
                            <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ ucfirst($network) }}"
                               class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white ring-1 ring-white/10 transition hover:bg-primary-600 hover:ring-primary-500">
                                <x-icon :name="$network" class="h-[18px] w-[18px]" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Danh mục sản phẩm: lấy theo danh mục thật của website --}}
        <div>
            <p class="{{ $heading }}">Danh mục sản phẩm</p>
            <ul class="space-y-2">
                @foreach ($menuCategories as $cat)
                    <li><a href="{{ $cat['url'] }}" class="{{ $link }}">{{ $cat['name'] }}</a></li>
                @endforeach
            </ul>
        </div>

        {{-- Chính sách --}}
        <div>
            <p class="{{ $heading }}">Chính sách</p>
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
            <p class="{{ $heading }}">Vận chuyển &amp; thanh toán</p>
            <p class="flex items-start gap-2.5"><span class="{{ $iconBox }}"><x-icon name="truck" class="h-4 w-4" /></span><span class="pt-1">Giao tận nơi văn phòng, kho, khu công nghiệp</span></p>
            <p class="mt-2.5 flex items-start gap-2.5"><span class="{{ $iconBox }}"><x-icon name="clock" class="h-4 w-4" /></span><span class="pt-1">{{ config('site.working_hours') }}</span></p>

            @if (config('site.bct_url'))
                <a href="{{ config('site.bct_url') }}" target="_blank" rel="noopener nofollow"
                   class="mt-4 inline-flex items-center gap-2 rounded-md bg-white px-3 py-2 text-[#0b5cad]">
                    <x-icon name="check-circle" class="h-8 w-8" />
                    <span class="leading-tight"><span class="block text-[13px] font-extrabold uppercase">Đã thông báo</span><span class="block text-[11px] font-semibold uppercase">Bộ Công Thương</span></span>
                </a>
            @endif
        </div>
    </div>

    <div class="relative border-t border-white/10 bg-black/20 py-4 text-center text-[13px] text-slate-400">
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
