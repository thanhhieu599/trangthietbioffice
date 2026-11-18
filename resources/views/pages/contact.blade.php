@extends('layouts.app')

@section('title', 'Liên hệ báo giá văn phòng phẩm | '.config('site.brand'))
@section('description', 'Liên hệ '.config('site.company').' để nhận báo giá văn phòng phẩm, vật tư tiêu hao cho doanh nghiệp, nhà máy.')

@php
    $digits = fn (?string $s) => $s ? preg_replace('/[^0-9+]/', '', $s) : null;
    $phone = config('site.phone');
    $email = config('site.email');
    $zalo = config('site.zalo') ? 'https://zalo.me/'.preg_replace('/\D/', '', config('site.zalo')) : null;
    $addresses = config('site.addresses', []);
    $mainAddress = $addresses[0]['text'] ?? config('site.address');
    $mapsLink = 'https://www.google.com/maps/search/?api=1&query='.urlencode($mainAddress);
    $mapEmbed = config('site.map_embed') ?: 'https://maps.google.com/maps?q='.urlencode($mainAddress).'&z=16&output=embed';
    $input = 'w-full rounded-lg border border-neutral-200 bg-neutral-50 px-3.5 py-2.5 text-sm text-ink-900 placeholder:text-neutral-400 transition focus:border-primary-600 focus:bg-white focus:ring-4 focus:ring-primary-600/10 focus:outline-none';
    $label = 'mb-1.5 block text-[13px] font-semibold text-ink-800';
    $quick = array_filter([
        $phone ? ['icon' => 'phone', 'label' => 'Hotline', 'value' => $phone, 'href' => 'tel:'.$digits($phone)] : null,
        $email ? ['icon' => 'mail', 'label' => 'Email', 'value' => $email, 'href' => 'mailto:'.$email] : null,
        $mainAddress ? ['icon' => 'pin', 'label' => 'Địa chỉ', 'value' => $mainAddress, 'href' => $mapsLink] : null,
    ]);
@endphp

@section('content')
<div class="mx-auto max-w-site px-4 py-5">
    @include('partials.breadcrumb', ['items' => [['name' => 'Liên hệ', 'url' => route('contact')]]])

    {{-- ===== Ảnh đầu trang (tải lên tại Quản trị > Liên hệ) ===== --}}
    <section class="relative overflow-hidden rounded-2xl bg-ink-900 text-white">
        @if ($hero)
            <img src="{{ $hero->imageUrl() }}" alt="{{ $hero->title ?: config('site.company') }}" class="absolute inset-0 h-full w-full object-cover" fetchpriority="high">
            <div class="absolute inset-0 bg-gradient-to-r from-ink-900/90 via-ink-900/60 to-ink-900/5"></div>
        @else
            <div class="absolute inset-0 bg-gradient-to-br from-navy-900 via-navy-800 to-primary-800"></div>
            <div class="absolute -top-24 -right-16 h-72 w-72 rounded-full bg-white/10 blur-2xl"></div>
            <div class="absolute -bottom-32 right-1/3 h-72 w-72 rounded-full bg-black/20 blur-3xl"></div>
        @endif
        <div class="relative px-6 pt-10 pb-20 md:px-12 md:pt-14 md:pb-24">
            <h1 class="max-w-xl text-3xl leading-tight font-bold md:text-4xl">Liên hệ với D&amp;T</h1>
            <p class="mt-3 max-w-md text-[15px] text-white/80">Báo giá nhanh, tư vấn tận tâm cho doanh nghiệp và nhà máy.</p>
            <div class="mt-6 flex flex-wrap gap-3">
                @if ($phone)
                    <a href="tel:{{ $digits($phone) }}" class="inline-flex items-center gap-2 rounded-lg bg-white px-5 py-2.5 text-sm font-bold text-primary-700 shadow-lg shadow-black/20 transition hover:-translate-y-0.5">
                        <x-icon name="phone" class="h-4 w-4" /> {{ $phone }}
                    </a>
                @endif
                @if ($zalo)
                    <a href="{{ $zalo }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-lg px-5 py-2.5 text-sm font-semibold ring-1 ring-white/50 transition hover:bg-white/10">Chat Zalo</a>
                @endif
            </div>
        </div>
    </section>

    {{-- ===== 3 thẻ liên hệ nhanh, nổi lên trên ảnh ===== --}}
    <div class="relative z-10 -mt-12 grid gap-3 px-3 sm:grid-cols-3 md:px-8">
        @foreach ($quick as $q)
            <a href="{{ $q['href'] }}" @if ($q['icon'] === 'pin') target="_blank" rel="noopener" @endif
               class="group flex items-center gap-3.5 rounded-xl bg-white p-4 shadow-lg shadow-ink-900/5 ring-1 ring-black/5 transition hover:-translate-y-0.5 hover:shadow-xl">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-primary-50 text-primary-600 transition group-hover:bg-primary-600 group-hover:text-white">
                    <x-icon :name="$q['icon']" class="h-5 w-5" />
                </span>
                <span class="min-w-0">
                    <span class="block text-xs text-neutral-500">{{ $q['label'] }}</span>
                    <span class="block text-sm leading-snug font-semibold text-ink-900">{{ $q['value'] }}</span>
                </span>
            </a>
        @endforeach
    </div>

    <div class="mt-8 grid items-start gap-6 lg:grid-cols-[minmax(0,1.55fr)_minmax(0,1fr)]">
        {{-- ===== Form gửi tin nhắn ===== --}}
        <section id="gui-tin-nhan" class="scroll-mt-24 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-black/5 md:p-8">
            <h2 class="text-xl font-bold text-ink-900 md:text-2xl">Gửi yêu cầu cho chúng tôi</h2>
            <p class="mt-1.5 text-sm text-neutral-500">Để lại lời nhắn, chúng tôi phản hồi trong giờ làm việc.</p>

            @if (session('contact_sent'))
                <div class="mt-5 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800" role="status">
                    <x-icon name="check-circle" class="mt-0.5 h-5 w-5 shrink-0" />
                    <p><strong>Đã nhận tin nhắn của bạn.</strong> Bộ phận kinh doanh sẽ liên hệ lại sớm nhất.</p>
                </div>
            @endif

            <form method="post" action="{{ route('contact.store') }}" class="mt-6 grid gap-5 sm:grid-cols-2" novalidate>
                @csrf
                <fieldset class="sm:col-span-2">
                    <legend class="{{ $label }}">Bạn cần hỗ trợ về</legend>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($topics as $key => $text)
                            <label class="cursor-pointer">
                                <input type="radio" name="topic" value="{{ $key }}" class="peer sr-only" @checked(old('topic', 'quote') === $key)>
                                <span class="block rounded-full border border-neutral-200 px-4 py-2 text-[13px] text-neutral-600 transition peer-checked:border-primary-600 peer-checked:bg-primary-50 peer-checked:font-semibold peer-checked:text-primary-700 peer-focus-visible:ring-4 peer-focus-visible:ring-primary-600/15 hover:border-neutral-300">{{ $text }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('topic')<p class="mt-1 text-xs text-primary-600">{{ $message }}</p>@enderror
                </fieldset>

                <label class="block">
                    <span class="{{ $label }}">Họ tên <span class="text-primary-600">*</span></span>
                    <input type="text" name="name" value="{{ old('name', auth()->user()?->name) }}" required maxlength="255" autocomplete="name" placeholder="Nguyễn Văn A" class="{{ $input }}">
                    @error('name')<span class="mt-1 block text-xs text-primary-600">{{ $message }}</span>@enderror
                </label>
                <label class="block">
                    <span class="{{ $label }}">Điện thoại <span class="text-primary-600">*</span></span>
                    <input type="tel" name="phone" value="{{ old('phone', auth()->user()?->phone) }}" required maxlength="30" autocomplete="tel" placeholder="09xx xxx xxx" class="{{ $input }}">
                    @error('phone')<span class="mt-1 block text-xs text-primary-600">{{ $message }}</span>@enderror
                </label>
                <label class="block sm:col-span-2">
                    <span class="{{ $label }}">Email <span class="text-primary-600">*</span></span>
                    <input type="email" name="email" value="{{ old('email', auth()->user()?->email) }}" required maxlength="255" autocomplete="email" placeholder="ten@congty.com" class="{{ $input }}">
                    @error('email')<span class="mt-1 block text-xs text-primary-600">{{ $message }}</span>@enderror
                </label>
                <label class="block sm:col-span-2">
                    <span class="{{ $label }}">Nội dung <span class="text-primary-600">*</span></span>
                    <textarea name="content" rows="5" required maxlength="3000" placeholder="Sản phẩm, số lượng, thời gian cần hàng..." class="{{ $input }} resize-y">{{ old('content', $prefill) }}</textarea>
                    @error('content')<span class="mt-1 block text-xs text-primary-600">{{ $message }}</span>@enderror
                </label>
                <div class="hidden" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>

                <div class="flex flex-wrap items-center gap-x-5 gap-y-3 sm:col-span-2">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-primary-600/25 transition hover:-translate-y-0.5 hover:bg-primary-700">
                        Gửi tin nhắn
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M22 2 11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
                    </button>
                </div>
            </form>
        </section>

        {{-- ===== Thông tin công ty ===== --}}
        <aside class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-black/5 lg:sticky lg:top-24">
            <div class="flex items-center justify-center border-b border-neutral-100 bg-gradient-to-b from-neutral-50 to-white px-6 py-6">
                @if ($logo = \App\Support\SiteImage::footerLogo())
                    <img src="{{ $logo }}" alt="trangthietbi.vn" class="max-h-16 w-auto object-contain" loading="lazy">
                @else
                    <span class="text-center">
                        <span class="block text-[26px] leading-none font-extrabold tracking-tight"><span class="text-ink-900">trang</span><span class="text-primary-600">thietbi</span><span class="text-ink-900">.vn</span></span>
                        <span class="mt-1.5 block text-[10px] font-bold tracking-wide text-ink-900 uppercase">Giữ chữ tín là việc quan trọng!</span>
                    </span>
                @endif
            </div>
            <ul class="divide-y divide-neutral-100 px-6 text-sm text-neutral-700">
                <li class="flex gap-3 py-3.5"><x-icon name="building" class="mt-0.5 h-4 w-4 shrink-0 text-primary-600" /><span class="font-semibold text-ink-900">{{ config('site.company') }}</span></li>
                @foreach ($addresses as $addr)
                    <li class="flex gap-3 py-3.5"><x-icon name="pin" class="mt-0.5 h-4 w-4 shrink-0 text-primary-600" /><span><span class="text-neutral-500">{{ $addr['label'] }}:</span> {{ $addr['text'] }}</span></li>
                @endforeach
                @if ($contacts = config('site.contacts', []))
                    <li class="flex gap-3 py-3.5"><x-icon name="user" class="mt-0.5 h-4 w-4 shrink-0 text-primary-600" />
                        <span class="grid flex-1 gap-1">
                            @foreach ($contacts as $c)
                                <span class="flex justify-between gap-3"><span>{{ $c['name'] }}</span><a href="tel:{{ $digits($c['phone']) }}" class="font-semibold whitespace-nowrap text-ink-900 hover:text-primary-600">{{ $c['phone'] }}</a></span>
                            @endforeach
                        </span>
                    </li>
                @endif
                <li class="flex gap-3 py-3.5"><x-icon name="clock" class="mt-0.5 h-4 w-4 shrink-0 text-primary-600" />{{ config('site.working_hours') }}</li>
            </ul>
            <div class="grid grid-cols-2 gap-3 p-6 pt-2">
                @if ($phone)
                    <a href="tel:{{ $digits($phone) }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary-600 py-2.5 text-sm font-bold text-white transition hover:bg-primary-700"><x-icon name="phone" class="h-4 w-4" /> Gọi ngay</a>
                @endif
                @if ($zalo)
                    <a href="{{ $zalo }}" target="_blank" rel="noopener" class="inline-flex items-center justify-center rounded-lg border border-[#0068ff] py-2.5 text-sm font-bold text-[#0068ff] transition hover:bg-[#0068ff] hover:text-white">Chat Zalo</a>
                @endif
            </div>
        </aside>
    </div>

    {{-- ===== Bản đồ ===== --}}
    <div class="mt-8 overflow-hidden rounded-2xl bg-neutral-100 shadow-sm ring-1 ring-black/5">
        <iframe src="{{ $mapEmbed }}" title="Bản đồ đường đến {{ config('site.brand') }}" class="block h-[340px] w-full border-0 md:h-[420px]" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
    </div>
</div>
@endsection
