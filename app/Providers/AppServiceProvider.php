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
        // Cây danh mục cho menu "Danh mục sản phẩm" và footer (cache 10 phút).
        // Lưu dạng mảng thuần: Laravel 13 không cho unserialize model Eloquent từ cache.
        View::composer(['partials.header', 'partials.footer'], function ($view) {
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
