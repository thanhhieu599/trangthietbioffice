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

            Category::active()->get()->each(fn ($c) => $urls->push(['loc' => $c->url(), 'lastmod' => $c->updated_at]));
            Product::active()->get(['slug', 'updated_at'])->each(fn ($p) => $urls->push(['loc' => $p->url(), 'lastmod' => $p->updated_at]));
            Post::published()->get(['slug', 'updated_at'])->each(fn ($p) => $urls->push(['loc' => $p->url(), 'lastmod' => $p->updated_at]));

            return view('sitemap', ['urls' => $urls])->render();
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        $body = app()->environment('production')
            ? "User-agent: *\nDisallow: /tim-kiem\nDisallow: /bao-gia/cam-on\nDisallow: /quan-tri\n\nSitemap: ".route('sitemap')."\n"
            : "User-agent: *\nDisallow: /\n"; // chặn index khi chạy thử nghiệm

        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
