<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\BannerController as AdminBannerController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\PostController as AdminPostController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
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
        Route::get('/tong-quan', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::post('/tai-anh', [AdminDashboardController::class, 'uploadImage'])->name('upload');

        // Bài viết (tin tức)
        Route::get('/bai-viet', [AdminPostController::class, 'index'])->name('posts.index');
        Route::get('/bai-viet/them', [AdminPostController::class, 'create'])->name('posts.create');
        Route::post('/bai-viet', [AdminPostController::class, 'store'])->name('posts.store');
        Route::get('/bai-viet/{post:id}', [AdminPostController::class, 'edit'])->name('posts.edit');
        Route::post('/bai-viet/{post:id}', [AdminPostController::class, 'update'])->name('posts.update');
        Route::post('/bai-viet/{post:id}/xoa', [AdminPostController::class, 'destroy'])->name('posts.destroy');

        // Sản phẩm
        Route::get('/san-pham', [AdminProductController::class, 'index'])->name('products.index');
        Route::get('/san-pham/them', [AdminProductController::class, 'create'])->name('products.create');
        Route::post('/san-pham', [AdminProductController::class, 'store'])->name('products.store');
        Route::get('/san-pham/{product:id}', [AdminProductController::class, 'edit'])->name('products.edit');
        Route::post('/san-pham/{product:id}', [AdminProductController::class, 'update'])->name('products.update');
        Route::post('/san-pham/{product:id}/xoa', [AdminProductController::class, 'destroy'])->name('products.destroy');

        // Danh mục sản phẩm
        Route::get('/danh-muc', [AdminCategoryController::class, 'index'])->name('categories.index');
        Route::get('/danh-muc/them', [AdminCategoryController::class, 'create'])->name('categories.create');
        Route::post('/danh-muc', [AdminCategoryController::class, 'store'])->name('categories.store');
        Route::get('/danh-muc/{category:id}', [AdminCategoryController::class, 'edit'])->name('categories.edit');
        Route::post('/danh-muc/{category:id}', [AdminCategoryController::class, 'update'])->name('categories.update');
        Route::post('/danh-muc/{category:id}/xoa', [AdminCategoryController::class, 'destroy'])->name('categories.destroy');

        // Chuyển hướng 301
        Route::get('/chuyen-huong', [AdminDashboardController::class, 'redirects'])->name('redirects.index');
        Route::post('/chuyen-huong', [AdminDashboardController::class, 'storeRedirect'])->name('redirects.store');
        Route::post('/chuyen-huong/{redirect}/xoa', [AdminDashboardController::class, 'destroyRedirect'])->name('redirects.destroy');

        Route::get('/banner', [AdminBannerController::class, 'index'])->name('banners.index');
        Route::post('/banner', [AdminBannerController::class, 'store'])->name('banners.store');
        Route::post('/banner/{banner}', [AdminBannerController::class, 'update'])->name('banners.update');
        Route::post('/banner/{banner}/xoa', [AdminBannerController::class, 'destroy'])->name('banners.destroy');
    });
});
