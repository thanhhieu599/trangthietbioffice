<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lớp bảo vệ SEO cho mọi request web:
 *
 * 1. Production: mọi URL tuyệt đối (canonical, og:url, sitemap, schema, menu đã cache) đều sinh theo APP_URL,
 *    KHÔNG theo tên miền trong request. Tránh trùng lặp www / không www / IP và tránh "đầu độc" cache
 *    sitemap/menu bằng header Host giả.
 * 2. Môi trường khác production (local, staging): gắn X-Robots-Tag noindex để bản thử nghiệm không bị index
 *    kể cả khi có link trỏ tới (robots.txt chỉ chặn crawl, không chặn index).
 * 3. Header bảo mật cơ bản (không phải tín hiệu xếp hạng, chỉ tăng độ an toàn).
 */
class SeoGuard
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('production')) {
            $root = rtrim((string) config('app.url'), '/');
            if ($root !== '') {
                URL::forceRootUrl($root);
                if (str_starts_with($root, 'https://')) {
                    URL::forceScheme('https');
                }
            }
        }

        $response = $next($request);

        if (! app()->environment('production')) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        if (! $response->headers->has('X-Frame-Options')) {
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        }

        return $response;
    }
}
