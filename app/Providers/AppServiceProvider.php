<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Lệnh artisan / queue ở production (import sản phẩm, gửi email...) cũng sinh URL theo APP_URL.
        // Với request web, middleware SeoGuard làm việc này.
        if ($this->app->environment('production') && $this->app->runningInConsole() && config('app.url')) {
            \Illuminate\Support\Facades\URL::forceRootUrl(rtrim(config('app.url'), '/'));
            if (str_starts_with(config('app.url'), 'https://')) {
                \Illuminate\Support\Facades\URL::forceScheme('https');
            }
        }

        // Cây danh mục cho menu "Danh mục sản phẩm" và footer (cache 10 phút).
        // Lưu dạng mảng thuần: Laravel 13 không cho unserialize model Eloquent từ cache.
        View::composer(['partials.header', 'partials.footer', 'errors.404'], function ($view) {
            $tree = Cache::remember('menu.categories', 600, fn () => Category::active()
                ->roots()
                ->with('children')
                ->get()
                ->map(fn (Category $c) => [
                    'name' => $c->name,
                    'url' => $c->url(),
                    'children' => $c->children->map(fn (Category $ch) => ['name' => $ch->name, 'url' => $ch->url()])->all(),
                ])
                ->all());

            $view->with('menuCategories', collect($tree));
        });

        // Link trang chính sách cho footer.
        View::composer('partials.footer', function ($view) {
            $pages = Cache::remember('footer.pages', 600, fn () => Page::where('in_footer', true)
                ->orderBy('sort_order')
                ->get()
                ->map(fn (Page $p) => ['title' => $p->title, 'url' => $p->url()])
                ->all());

            $view->with('footerPages', collect($pages));
        });
    }
}
