{{-- Thẻ thương hiệu: logo to ở trên, tên bên dưới. Logo: public/images/brands/{slug}.png --}}
@php($logo = \App\Support\SiteImage::brand($brand['slug']))
<a href="{{ route('brands.show', $brand['slug']) }}"
   class="group flex h-full flex-col items-center justify-between gap-2 rounded-lg border border-neutral-200 bg-white px-3 pt-4 pb-3 text-center transition hover:border-primary-600 hover:shadow-md focus-visible:outline-2 focus-visible:outline-primary-600">
    <span class="flex h-14 w-full items-center justify-center lg:h-16">
        @if ($logo)
            <img src="{{ $logo }}" alt="Logo {{ $brand['name'] }}" loading="lazy" decoding="async"
                 class="max-h-full max-w-[85%] object-contain transition duration-200 group-hover:scale-105">
        @else
            {{-- Chưa có logo: hiện tên thương hiệu dạng chữ lớn --}}
            <span class="line-clamp-2 text-[15px] leading-tight font-extrabold tracking-wide [overflow-wrap:anywhere] text-ink-900 uppercase group-hover:text-primary-600 sm:text-lg lg:text-xl">{{ $brand['name'] }}</span>
        @endif
    </span>
    <span class="text-[13px] font-semibold text-ink-700 group-hover:text-primary-600">{{ $brand['name'] }}</span>
</a>
