<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\BannerController as AdminBannerController;
use App\Http\Controllers\CatalogController;
use App\Http\Middleware\AdminAuth;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CatalogController::class, 'home'])->name('home');

Route::get('/danh-muc/{category}', [CatalogController::class, 'category'])->name('categories.show');
Route::get('/san-pham/{product}', [CatalogController::class, 'product'])->name('products.show');
Route::get('/tim-kiem', [CatalogController::class, 'search'])->name('search');

Route::get('/thuong-hieu', [CatalogController::class, 'brandIndex'])->name('brands.index');
Route::get('/thuong-hieu/{slug}', [CatalogController::class, 'brandShow'])->name('brands.show');

Route::get('/tin-tuc', [PostController::class, 'index'])->name('posts.index');
Route::get('/tin-tuc/{post}', [PostController::class, 'show'])->name('posts.show');
Route::permanentRedirect('/kien-thuc', '/tin-tuc');
Route::get('/kien-thuc/{slug}', fn (string $slug) => redirect()->route('posts.show', $slug, 301));

Route::view('/lien-he', 'pages.contact')->name('contact');
Route::get('/trang/{page}', [PageController::class, 'show'])->name('pages.show');

Route::get('/bao-gia', [QuoteController::class, 'create'])->name('quote.create');
Route::post('/bao-gia', [QuoteController::class, 'store'])->middleware('throttle:5,1')->name('quote.store');
Route::get('/bao-gia/cam-on', [QuoteController::class, 'thanks'])->name('quote.thanks');
Route::get('/api/quote-items', [QuoteController::class, 'lookup'])->name('quote.lookup');

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');

// ===== Khu quản trị (mật khẩu: ADMIN_PASSWORD trong .env; trống = tắt) =====
Route::prefix('quan-tri')->name('admin.')->group(function () {
    Route::get('/', [AdminAuthController::class, 'show'])->name('login');
    Route::post('/dang-nhap', [AdminAuthController::class, 'login'])->middleware('throttle:5,1')->name('login.submit');
    Route::post('/dang-xuat', [AdminAuthController::class, 'logout'])->name('logout');

    Route::middleware(AdminAuth::class)->group(function () {
        Route::get('/banner', [AdminBannerController::class, 'index'])->name('banners.index');
        Route::post('/banner', [AdminBannerController::class, 'store'])->name('banners.store');
        Route::post('/banner/{banner}', [AdminBannerController::class, 'update'])->name('banners.update');
        Route::post('/banner/{banner}/xoa', [AdminBannerController::class, 'destroy'])->name('banners.destroy');
    });
});
