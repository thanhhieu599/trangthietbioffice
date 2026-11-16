<?php

use App\Http\Controllers\CatalogController;
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

Route::get('/bao-gia', [QuoteController::class, 'create'])->name('quote.create');
Route::post('/bao-gia', [QuoteController::class, 'store'])->middleware('throttle:5,1')->name('quote.store');
Route::get('/bao-gia/cam-on', [QuoteController::class, 'thanks'])->name('quote.thanks');
Route::get('/api/quote-items', [QuoteController::class, 'lookup'])->name('quote.lookup');

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');
