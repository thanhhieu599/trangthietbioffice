<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Chốt chặn SEO trước / ngay sau khi deploy. Gửi request NỘI BỘ vào ứng dụng (không cần mạng, không cần DNS)
 * và báo lỗi nếu cấu hình production có thể làm hỏng việc index của Google.
 *
 *   php artisan seo:check            # chạy trên server production sau config:cache
 *   php artisan seo:check --sample=0 # kiểm tra toàn bộ URL trong sitemap (mặc định 300 URL đầu)
 *
 * Trả mã thoát 1 khi có lỗi nghiêm trọng -> dùng được trong script deploy: `php artisan seo:check || exit 1`.
 */
class SeoCheck extends Command
{
    protected $signature = 'seo:check {--sample=300 : Số URL trong sitemap cần kiểm tra (0 = tất cả)}';

    protected $description = 'Kiểm tra SEO kỹ thuật bắt buộc trước khi deploy (robots, sitemap, canonical, noindex, debug, 404)';

    private int $errors = 0;

    private int $warnings = 0;

    public function handle(): int
    {
        $appUrl = rtrim((string) config('app.url'), '/');
        $this->line('<options=bold>Kiểm tra SEO kỹ thuật</> – môi trường: '.app()->environment().' – APP_URL: '.$appUrl);

        // 1. Cấu hình
        $this->check(app()->environment('production'), 'APP_ENV=production', 'APP_ENV đang là "'.app()->environment().'": robots.txt sẽ chặn toàn site và mọi trang có X-Robots-Tag noindex.');
        $this->check(! config('app.debug'), 'APP_DEBUG=false', 'APP_DEBUG đang bật: lộ thông tin hệ thống khi có lỗi.');
        $badHost = (bool) preg_match('#//(localhost|127\.|0\.0\.0\.0|[^/]+\.(test|local|localhost))(:|/|$)#i', $appUrl.'/');
        $this->check($appUrl !== '' && ! $badHost, 'APP_URL là tên miền thật', 'APP_URL "'.$appUrl.'" là địa chỉ máy cục bộ – canonical/sitemap sẽ trỏ sai.');
        $this->check(str_starts_with($appUrl, 'https://'), 'APP_URL dùng HTTPS', 'APP_URL không dùng https://', warnOnly: true);

        // 2. robots.txt
        $robots = $this->get('/robots.txt');
        $body = (string) $robots->getContent();
        $this->check($robots->getStatusCode() === 200, 'robots.txt trả 200', 'robots.txt trả '.$robots->getStatusCode());
        $this->check(str_starts_with((string) $robots->headers->get('Content-Type'), 'text/plain'), 'robots.txt là text/plain', 'robots.txt sai Content-Type');
        $this->check(! preg_match('/^Disallow:\s*\/\s*$/mi', $body), 'robots.txt không chặn toàn site', 'robots.txt đang có "Disallow: /" – Google không crawl được website.');
        $this->check(str_contains($body, 'Sitemap: '.$appUrl.'/sitemap.xml'), 'robots.txt khai báo sitemap đúng tên miền', 'robots.txt thiếu dòng "Sitemap: '.$appUrl.'/sitemap.xml"');

        // 3. Trang chủ
        $home = $this->get('/');
        $html = (string) $home->getContent();
        $this->check($home->getStatusCode() === 200, 'Trang chủ trả 200', 'Trang chủ trả '.$home->getStatusCode());
        $this->check(! $this->isNoindex($home), 'Trang chủ không bị noindex', 'Trang chủ đang có noindex (meta robots hoặc X-Robots-Tag).');
        $canon = $this->canonical($html);
        $this->check($canon !== null && str_starts_with($canon, $appUrl), 'Canonical trang chủ đúng tên miền', 'Canonical trang chủ "'.($canon ?? 'không có').'" không thuộc '.$appUrl);
        $this->check($this->jsonLdValid($html), 'JSON-LD trang chủ hợp lệ', 'JSON-LD trang chủ không parse được.');
        $this->check(! preg_match('#(localhost|127\.0\.0\.1)#', $html) || $badHost, 'HTML trang chủ không chứa localhost', 'HTML trang chủ còn link localhost / 127.0.0.1.');

        // 4. 404 thật
        $missing = $this->get('/khong-ton-tai-'.bin2hex(random_bytes(4)));
        $this->check($missing->getStatusCode() === 404, 'URL không tồn tại trả 404', 'URL không tồn tại trả '.$missing->getStatusCode().' (soft 404).');

        // 5. Sitemap
        $sm = $this->get('/sitemap.xml');
        $this->check($sm->getStatusCode() === 200, 'sitemap.xml trả 200', 'sitemap.xml trả '.$sm->getStatusCode());
        $xml = @simplexml_load_string((string) $sm->getContent());
        $this->check($xml !== false, 'sitemap.xml là XML hợp lệ', 'sitemap.xml không phải XML hợp lệ.');
        $locs = $xml ? array_map('strval', $xml->xpath('//*[local-name()="loc"]') ?: []) : [];
        $this->check(count($locs) > 0 && count($locs) <= 50000, 'Sitemap có '.count($locs).' URL (≤ 50.000)', 'Sitemap có '.count($locs).' URL.');
        $foreign = array_filter($locs, fn ($l) => ! str_starts_with($l, $appUrl.'/') && $l !== $appUrl);
        $this->check(! $foreign, 'Mọi URL trong sitemap thuộc '.$appUrl, count($foreign).' URL sitemap sai tên miền, ví dụ: '.(reset($foreign) ?: ''));

        $sample = (int) $this->option('sample');
        $toCheck = $sample > 0 ? array_slice($locs, 0, $sample) : $locs;
        $bad = [];
        foreach ($toCheck as $loc) {
            $path = substr($loc, strlen($appUrl)) ?: '/';
            $r = $this->get($path);
            $c = $this->canonical((string) $r->getContent());
            $problem = match (true) {
                $r->getStatusCode() !== 200 => 'HTTP '.$r->getStatusCode(),
                $this->isNoindex($r) => 'noindex',
                $c !== null && rtrim($c, '/') !== rtrim($loc, '/') => 'canonical khác: '.$c,
                ! $this->jsonLdValid((string) $r->getContent()) => 'JSON-LD lỗi',
                default => null,
            };
            if ($problem) {
                $bad[] = $loc.' → '.$problem;
            }
        }
        $this->check(! $bad, 'Đã kiểm tra '.count($toCheck).' URL sitemap: 200, không noindex, canonical khớp', count($bad).' URL sitemap lỗi:'.PHP_EOL.'      '.implode(PHP_EOL.'      ', array_slice($bad, 0, 15)));

        $this->newLine();
        if ($this->errors) {
            $this->error("KHÔNG ĐẠT: {$this->errors} lỗi, {$this->warnings} cảnh báo. Không nên deploy / cần sửa ngay.");

            return self::FAILURE;
        }
        $this->info("ĐẠT: 0 lỗi, {$this->warnings} cảnh báo.");

        return self::SUCCESS;
    }

    private function get(string $path): Response
    {
        $root = rtrim((string) config('app.url'), '/') ?: 'http://localhost';
        $request = Request::create($root.$path, 'GET', server: ['HTTP_USER_AGENT' => 'DT-SEO-Check']);
        $kernel = app(Kernel::class);
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);

        return $response;
    }

    private function canonical(string $html): ?string
    {
        return preg_match('#<link rel="canonical" href="([^"]+)"#', $html, $m) ? html_entity_decode($m[1]) : null;
    }

    private function isNoindex(Response $r): bool
    {
        return str_contains(strtolower((string) $r->headers->get('X-Robots-Tag')), 'noindex')
            || (bool) preg_match('#<meta name="robots" content="[^"]*noindex#i', (string) $r->getContent());
    }

    private function jsonLdValid(string $html): bool
    {
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
        foreach ($m[1] as $json) {
            json_decode($json);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return false;
            }
        }

        return true;
    }

    private function check(bool $ok, string $pass, string $fail, bool $warnOnly = false): void
    {
        if ($ok) {
            $this->line("  <fg=green>✓</> {$pass}");

            return;
        }
        if ($warnOnly) {
            $this->warnings++;
            $this->line("  <fg=yellow>!</> {$fail}");

            return;
        }
        $this->errors++;
        $this->line("  <fg=red>✗</> {$fail}");
    }
}
