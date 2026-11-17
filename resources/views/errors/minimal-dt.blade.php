{{--
    Trang lỗi độc lập: KHÔNG dùng database, Vite hay view composer, để vẫn hiển thị được
    khi chính database / build bị lỗi (500, 503). Biến: $code, $heading, $message.
--}}
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $heading }} | {{ config('site.brand', 'D&T Office') }}</title>
    <style>
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f5f5f5;
               font-family: 'Be Vietnam Pro', system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif; color: #333; padding: 16px; box-sizing: border-box; }
        .box { max-width: 520px; width: 100%; background: #fff; border-radius: 8px; padding: 32px; text-align: center; }
        .code { font-size: 48px; font-weight: 700; color: #d71920; margin: 0; }
        h1 { font-size: 22px; color: #141414; margin: 12px 0 8px; }
        p { margin: 0; line-height: 1.6; color: #555; }
        .links { margin-top: 24px; display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
        a { color: #d71920; text-decoration: none; border: 1px solid #ddd; padding: 8px 16px; border-radius: 6px; }
        a:hover { border-color: #d71920; }
    </style>
</head>
<body>
    <main class="box">
        <p class="code">{{ $code }}</p>
        <h1>{{ $heading }}</h1>
        <p>{{ $message }}</p>
        <div class="links">
            <a href="/">Về trang chủ</a>
            @if ($phone = config('site.phone'))<a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}">Gọi {{ $phone }}</a>@endif
        </div>
    </main>
</body>
</html>
