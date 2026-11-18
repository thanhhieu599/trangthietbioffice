<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use App\Support\SiteImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CatalogController extends Controller
{
    /** Số sản phẩm mỗi khối danh mục trên trang chủ. */
    private const HOME_SECTION_SIZE = 8; // 2 hàng × 4 sản phẩm cạnh banner dọc

    public function home(): View
    {
        // Mỗi danh mục bật "show_on_home" thành một khối sản phẩm riêng, theo thứ tự sort_order.
        $sections = Category::active()
            ->where('show_on_home', true)
            ->orderBy('sort_order')
            ->with('children')
            ->get()
            ->map(function (Category $category) {
                $query = Product::active()->whereIn('category_id', $category->descendantIds());

                return [
                    'category' => $category,
                    'total' => (clone $query)->count(),
                    'products' => $query->orderByDesc('is_featured')->latest('id')->take(self::HOME_SECTION_SIZE)->get(),
                ];
            })
            ->filter(fn ($s) => $s['products']->isNotEmpty())
            ->values();

        $active = Banner::active()->get()->groupBy('position');

        return view('pages.home', [
            'banners' => $this->banners($active->get('slider', collect())),
            'sideBanners' => $active->get('side', collect())->take(Banner::POSITIONS['side']['max']),
            'promoBanners' => $active->get('promo', collect())->take(Banner::POSITIONS['promo']['max']),
            'flash' => \App\Models\FlashSale::context(),
            'gridCategories' => Category::active()->roots()->get(),
            'sections' => $sections,
            'posts' => Post::published()->latest('published_at')->take(12)->get(),
            'brands' => $this->brandList()->take(40),
        ]);
    }

    /**
     * Banner trong database (có link riêng) được ưu tiên; nếu chưa có thì tự lấy
     * ảnh trong thư mục public/images/banners (bỏ file vào là hiện).
     */
    private function banners(Collection $banners): Collection
    {
        return $banners->isNotEmpty()
            ? $banners
            : SiteImage::banners()->map(fn (array $b) => new Banner($b + ['link' => route('quote.create')]));
    }

    public function category(Request $request, Category $category): View
    {
        abort_unless($category->is_active, 404);

        $products = Product::active()
            ->whereIn('category_id', $category->descendantIds())
            ->when($request->filled('brand'), fn ($q) => $q->where('brand', $request->query('brand')))
            ->tap(fn ($q) => $this->applySort($q, $request->query('sort')))
            ->paginate(30) // chia hết cho 5 / 3 / 2 cột: hàng cuối không lẻ
            ->withQueryString();
        self::abortIfPageOutOfRange($products);

        $brands = Product::active()
            ->whereIn('category_id', $category->descendantIds())
            ->whereNotNull('brand')
            ->distinct()
            ->orderBy('brand')
            ->pluck('brand');

        // Danh mục chưa có sản phẩm và chưa có nội dung riêng: vẫn hiển thị cho khách nhưng không cho index.
        $indexable = $products->total() > 0 || trim(strip_tags((string) $category->content)) !== '';

        return view('pages.category', compact('category', 'products', 'brands', 'indexable'));
    }

    public function product(Product $product): View
    {
        abort_unless($product->is_active, 404);
        $product->load('category');

        $related = Product::active()
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->id)
            ->take(5)
            ->get();

        return view('pages.product', compact('product', 'related'));
    }

    public function search(Request $request): View
    {
        $term = trim((string) $request->query('q'));
        $products = $term === '' ? collect() : Product::active()->search($term)->take(50)->get();

        return view('pages.search', compact('term', 'products'));
    }

    /** Gợi ý tức thì cho ô tìm kiếm ở header (JSON): tối đa 6 sản phẩm + 3 danh mục. */
    public function suggest(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q'));
        if (mb_strlen($term) < 2) {
            return response()->json(['products' => [], 'categories' => [], 'total' => 0]);
        }

        $query = Product::active()->search($term);
        $total = (clone $query)->count();
        $products = $query->take(6)->get()->map(function (Product $p) {
            $price = $p->currentPrice();

            return [
                'name' => $p->name,
                'sku' => $p->sku,
                'url' => $p->url(),
                'image' => $p->mainImage(),
                'price' => $price ? Product::money($price) : 'Liên hệ báo giá',
                'old_price' => $p->flashItem() && $p->price ? Product::money((int) $p->price) : null,
            ];
        });

        $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_substr($term, 0, 100)).'%';
        $categories = Category::active()->whereRaw("name like ? escape '!'", [$like])->orderBy('name')->take(3)->get()
            ->map(fn (Category $c) => ['name' => $c->name, 'url' => $c->url()]);

        return response()->json(['products' => $products, 'categories' => $categories, 'total' => $total])
            ->header('X-Robots-Tag', 'noindex');
    }

    private function brandList(): Collection
    {
        return Product::active()
            ->whereNotNull('brand')
            ->where('brand', '!=', '')
            ->selectRaw('brand, count(*) as total')
            ->groupBy('brand')
            ->get()
            // Gộp các cách viết của cùng 1 thương hiệu ("DELI" / "Deli"): lấy cách viết có nhiều sản phẩm nhất làm tên
            ->groupBy(fn ($row) => Str::slug($row->brand))
            ->map(fn ($rows, $slug) => ['name' => $rows->sortByDesc('total')->first()->brand, 'slug' => $slug, 'total' => $rows->sum('total'), 'names' => $rows->pluck('brand')->all()])
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    /** Mọi cách viết tên của 1 thương hiệu (theo slug). */
    private function brandNames(string $slug): array
    {
        return $this->brandList()->firstWhere('slug', $slug)['names'] ?? [];
    }

    public function brandIndex(): View
    {
        return view('pages.brands', ['brands' => $this->brandList()]);
    }

    public function brandShow(Request $request, string $slug): View
    {
        $brand = $this->brandList()->firstWhere('slug', $slug);
        abort_unless($brand, 404);

        abort_if((int) $request->query('page', 1) > 1, 404); // trang thương hiệu không chia trang (tránh trang rỗng 200)

        // Tất cả sản phẩm của thương hiệu (gộp các cách viết như "DELI" / "Deli"), chia theo danh mục gốc
        $products = Product::active()->with('category')
            ->whereIn('brand', $this->brandNames($brand['slug']))
            ->tap(fn ($q) => $this->applySort($q, $request->query('sort')))
            ->take(1000)->get();

        $categories = Category::get(['id', 'name', 'slug', 'parent_id', 'sort_order'])->keyBy('id');
        $rootOf = function (?int $id) use ($categories) {
            $c = $id ? $categories->get($id) : null;
            for ($i = 0; $c && $c->parent_id && $i < 10; $i++) {
                $c = $categories->get($c->parent_id) ?? $c;
            }

            return $c;
        };
        $groups = $products->groupBy(fn ($p) => $rootOf($p->category_id)?->id ?? 0)
            ->map(fn ($items, $id) => ['category' => $categories->get($id), 'items' => $items])
            ->sortBy(fn ($g) => [$g['category'] ? 0 : 1, $g['category']->sort_order ?? 0, $g['category']->name ?? ''])
            ->values();

        return view('pages.brand', [
            'brand' => $brand, 'groups' => $groups, 'total' => $products->count(),
            'banner' => SiteImage::brandBanner($brand['slug']), 'logo' => SiteImage::brand($brand['slug']),
        ]);
    }

    /** ?page=999 vượt quá số trang thật -> 404 (tránh trang rỗng trả 200 / soft 404). */
    public static function abortIfPageOutOfRange(\Illuminate\Pagination\LengthAwarePaginator $paginator): void
    {
        abort_if($paginator->currentPage() > 1 && $paginator->currentPage() > $paginator->lastPage(), 404);
    }

    private function applySort($query, ?string $sort): void
    {
        match ($sort) {
            'price_asc' => $query->orderByRaw('price is null')->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'name' => $query->orderBy('name'),
            default => $query->orderByDesc('is_featured')->latest('id'),
        };
    }
}
