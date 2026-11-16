<footer class="mt-16 bg-slate-900 text-sm text-slate-300">
    <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 md:grid-cols-3">
        <div>
            <p class="mb-2 text-base font-semibold text-white">{{ config('site.brand') }}</p>
            <p class="mb-2">{{ config('site.company') }}</p>
            @if (config('site.tax_code'))<p>MST: {{ config('site.tax_code') }}</p>@endif
            @if (config('site.address'))<p>Địa chỉ: {{ config('site.address') }}</p>@endif
        </div>
        <div>
            <p class="mb-2 font-semibold text-white">Liên hệ báo giá</p>
            @if (config('site.phone'))<p>Hotline: <a class="hover:text-white" href="tel:{{ preg_replace('/\s+/', '', config('site.phone')) }}">{{ config('site.phone') }}</a></p>@endif
            @if (config('site.zalo'))<p>Zalo: <a class="hover:text-white" href="https://zalo.me/{{ preg_replace('/\D/', '', config('site.zalo')) }}" rel="nofollow">{{ config('site.zalo') }}</a></p>@endif
            @if (config('site.email'))<p>Email: <a class="hover:text-white" href="mailto:{{ config('site.email') }}">{{ config('site.email') }}</a></p>@endif
            <a href="{{ route('quote.create') }}" class="mt-3 inline-block rounded bg-accent-500 px-4 py-2 font-semibold text-slate-900">Gửi yêu cầu báo giá</a>
        </div>
        <div>
            <p class="mb-2 font-semibold text-white">Hệ sinh thái D&amp;T</p>
            <p>Bảo hộ lao động (PPE), MRO cho nhà máy:</p>
            <a href="{{ config('site.parent_site') }}" class="text-white underline">{{ parse_url(config('site.parent_site'), PHP_URL_HOST) }}</a>
        </div>
    </div>
    <div class="border-t border-slate-800 py-4 text-center text-xs text-slate-500">© {{ date('Y') }} {{ config('site.company') }}</div>
</footer>
