<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $xml = Cache::remember('sitemap.xml', now()->addHour(), function () {
            $urls = collect([
                ['loc' => route('home'), 'lastmod' => null],
                ['loc' => route('quote.create'), 'lastmod' => null],
                ['loc' => route('posts.index'), 'lastmod' => null],
                ['loc' => route('brands.index'), 'lastmod' => null],
                ['loc' => route('contact'), 'lastmod' => null],
            ]);

            \App\Models\Page::all()->filter->hasContent()
                ->each(fn ($p) => $urls->push(['loc' => $p->url(), 'lastmod' => $p->updated_at]));

            Product::active()->whereNotNull('brand')->where('brand', '!=', '')->distinct()->pluck('brand')
                ->each(fn ($b) => $urls->push(['loc' => route('brands.show', \Illuminate\Support\Str::slug($b)), 'lastmod' => null]));

            // Chỉ danh mục đáng index (có sản phẩm trong nhánh hoặc có nội dung riêng) – khớp với thẻ robots của trang.
            $indexable = Category::indexableIds();
            Category::active()->get()->filter(fn ($c) => isset($indexable[$c->id]))
                ->each(fn ($c) => $urls->push(['loc' => $c->url(), 'lastmod' => $c->updated_at]));
            Product::active()->get(['slug', 'updated_at'])->each(fn ($p) => $urls->push(['loc' => $p->url(), 'lastmod' => $p->updated_at]));
            Post::published()->get(['slug', 'updated_at'])->each(fn ($p) => $urls->push(['loc' => $p->url(), 'lastmod' => $p->updated_at]));

            return view('sitemap', ['urls' => $urls])->render();
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        $body = app()->environment('production')
            // Chỉ chặn crawl các URL không có giá trị tìm kiếm. Trang quản trị được bảo vệ bằng mật khẩu, không dựa vào robots.
            ? "User-agent: *\nDisallow: /tim-kiem\nDisallow: /bao-gia/cam-on\nDisallow: /quan-tri\nDisallow: /api/\nDisallow: /up\nDisallow: /tai-khoan\nDisallow: /dat-lai-mat-khau\n\nSitemap: ".route('sitemap')."\n"
            : "User-agent: *\nDisallow: /\n"; // chặn index khi chạy thử nghiệm

        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
