@php
    // @section('x', 'chuỗi') đã escape sẵn -> giải mã một lần để {{ }} không escape hai lần.
    $section = fn (string $name) => html_entity_decode(trim($__env->yieldContent($name)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $brand = config('site.brand');
    $pageTitle = $section('title') ?: $brand.' – '.config('site.tagline');
    $pageDescription = $section('description') ?: config('site.tagline');
    // Canonical mặc định = URL hiện tại không kèm tham số; bỏ "/index.php" để không sinh bản trùng của trang chủ.
    $canonical = $section('canonical') ?: preg_replace('#/index\.php(?=/|$)#', '', url()->current());
    // Ảnh chia sẻ: ảnh riêng của trang > public/images/og-default.* > logo. Không trỏ tới file không tồn tại.
    $ogImage = $section('og_image') ?: (\App\Support\SiteImage::find('og-default', ['jpg', 'jpeg', 'png', 'webp']) ?: \App\Support\SiteImage::logo());
    $ogImage = $ogImage ? \App\Support\JsonLd::absolute(strtok($ogImage, '?')) : null;
@endphp
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags($pageDescription), 160) }}">
    {{-- Trang lỗi đặt @section('canonical', 'none') để không khai báo canonical --}}
    @if ($canonical !== 'none')<link rel="canonical" href="{{ $canonical }}">@endif
    @hasSection('robots')<meta name="robots" content="@yield('robots')">@endif

    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:site_name" content="{{ $brand }}">
    <meta property="og:locale" content="vi_VN">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ \Illuminate\Support\Str::limit(strip_tags($pageDescription), 200) }}">
    @if ($canonical !== 'none')<meta property="og:url" content="{{ $canonical }}">@endif
    @if ($ogImage)<meta property="og:image" content="{{ $ogImage }}">@endif
    <meta name="twitter:card" content="{{ $ogImage ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ \Illuminate\Support\Str::limit(strip_tags($pageDescription), 200) }}">
    @if ($ogImage)<meta name="twitter:image" content="{{ $ogImage }}">@endif

    @if (config('site.gsc_verification'))
        <meta name="google-site-verification" content="{{ config('site.gsc_verification') }}">
    @endif

    <link rel="icon" href="/favicon.ico">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {!! \App\Support\JsonLd::script(\App\Support\JsonLd::organization()) !!}
    @stack('schema')

    @if (config('site.ga4_id'))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('site.ga4_id') }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', @json(config('site.ga4_id')));
        </script>
    @endif
</head>
<body class="flex min-h-screen flex-col bg-neutral-100 font-sans text-ink-700 antialiased">
    @include('partials.header')

    <main class="flex-1">
        @yield('content')
    </main>

    @include('partials.footer')
</body>
</html>
