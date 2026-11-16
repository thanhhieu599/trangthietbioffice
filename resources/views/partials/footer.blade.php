@php
    $phone = config('site.phone');
    $tel = $phone ? preg_replace('/[^0-9+]/', '', $phone) : null;
    $zalo = config('site.zalo') ? preg_replace('/\D/', '', config('site.zalo')) : null;
@endphp
<footer class="mt-auto border-t-4 border-primary-600 bg-ink-900 text-sm text-neutral-300">
    <div class="mx-auto grid max-w-site gap-8 px-4 py-10 sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <p class="mb-3 flex items-center gap-2">
                <span class="rounded bg-primary-600 px-2 py-1 text-lg font-bold leading-none text-white">D&amp;T</span>
                <span class="text-lg font-bold text-white">OFFICE</span>
            </p>
            <p class="font-semibold text-white">{{ config('site.company') }}</p>
            @if (config('site.tax_code'))<p class="mt-1">MST: {{ config('site.tax_code') }}</p>@endif
            <p class="mt-2 leading-relaxed">{{ config('site.tagline') }}.</p>
        </div>

        <div>
            <p class="mb-3 font-bold uppercase text-white">Danh mục sản phẩm</p>
            <ul class="space-y-1.5">
                @foreach ($menuCategories->take(8) as $cat)
                    <li><a href="{{ $cat['url'] }}" class="hover:text-white">{{ $cat['name'] }}</a></li>
                @endforeach
            </ul>
        </div>

        <div>
            <p class="mb-3 font-bold uppercase text-white">Hỗ trợ khách hàng</p>
            <ul class="space-y-1.5">
                <li><a href="{{ route('quote.create') }}" class="hover:text-white">Yêu cầu báo giá</a></li>
                <li><a href="{{ route('posts.index') }}" class="hover:text-white">Tin tức &amp; kiến thức</a></li>
                <li><a href="{{ route('brands.index') }}" class="hover:text-white">Thương hiệu</a></li>
                <li><a href="{{ route('contact') }}" class="hover:text-white">Liên hệ</a></li>
                <li><a href="{{ config('site.parent_site') }}" class="hover:text-white">Bảo hộ lao động (PPE) – {{ parse_url(config('site.parent_site'), PHP_URL_HOST) }}</a></li>
            </ul>
        </div>

        <div>
            <p class="mb-3 font-bold uppercase text-white">Liên hệ</p>
            <ul class="space-y-2">
                @if ($phone)
                    <li class="flex gap-2"><x-icon name="phone" class="mt-0.5 h-4 w-4 shrink-0 text-primary-600" />
                        <a href="tel:{{ $tel }}" class="text-base font-bold text-white hover:text-primary-100">{{ $phone }}</a></li>
                @endif
                @if (config('site.email'))
                    <li class="flex gap-2"><x-icon name="mail" class="mt-0.5 h-4 w-4 shrink-0 text-primary-600" />
                        <a href="mailto:{{ config('site.email') }}" class="hover:text-white">{{ config('site.email') }}</a></li>
                @endif
                @if (config('site.address'))
                    <li class="flex gap-2"><x-icon name="pin" class="mt-0.5 h-4 w-4 shrink-0 text-primary-600" />{{ config('site.address') }}</li>
                @endif
                <li class="flex gap-2"><x-icon name="clock" class="mt-0.5 h-4 w-4 shrink-0 text-primary-600" />{{ config('site.working_hours') }}</li>
            </ul>
        </div>
    </div>
    <div class="bg-black/40 py-4 text-center text-xs text-neutral-500">
        © {{ date('Y') }} {{ config('site.company') }}. Bảo lưu mọi quyền.
    </div>
</footer>

{{-- Nút gọi nhanh / Zalo nổi góc phải (chỉ hiện khi đã khai báo) --}}
@if ($tel || $zalo)
    <div class="fixed right-4 bottom-4 z-40 flex flex-col gap-3">
        @if ($zalo)
            <a href="https://zalo.me/{{ $zalo }}" target="_blank" rel="noopener nofollow" aria-label="Chat Zalo"
               class="flex h-12 w-12 items-center justify-center rounded-full bg-[#0068ff] text-xs font-bold text-white shadow-lg">Zalo</a>
        @endif
        @if ($tel)
            <a href="tel:{{ $tel }}" aria-label="Gọi {{ $phone }}"
               class="flex h-12 w-12 items-center justify-center rounded-full bg-primary-600 text-white shadow-lg">
                <x-icon name="phone" class="h-6 w-6" />
            </a>
        @endif
    </div>
@endif
