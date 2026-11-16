<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use App\Support\SiteImage;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CatalogController extends Controller
{
    /** Số sản phẩm tối đa mỗi khối trên trang chủ (1 hàng, trượt ngang: 3 trang x 5 sản phẩm). */
    private const HOME_SECTION_SIZE = 15;

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

        return view('pages.home', [
            'banners' => $this->banners(),
            'sections' => $sections,
            'posts' => Post::published()->latest('published_at')->take(12)->get(),
            'brands' => $this->brandList()->take(12),
        ]);
    }

    /**
     * Banner trong database (có link riêng) được ưu tiên; nếu chưa có thì tự lấy
     * ảnh trong thư mục public/images/banners (bỏ file vào là hiện).
     */
    private function banners(): Collection
    {
        $banners = Banner::active()->get();

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
            ->paginate(20)
            ->withQueryString();

        $brands = Product::active()
            ->whereIn('category_id', $category->descendantIds())
            ->whereNotNull('brand')
            ->distinct()
            ->orderBy('brand')
            ->pluck('brand');

        return view('pages.category', compact('category', 'products', 'brands'));
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
        $products = $term === ''
            ? collect()
            : Product::active()
                ->where(fn ($q) => $q->where('name', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%")
                    ->orWhere('brand', 'like', "%{$term}%"))
                ->take(50)
                ->get();

        return view('pages.search', compact('term', 'products'));
    }

    private function brandList(): Collection
    {
        return Product::active()
            ->whereNotNull('brand')
            ->where('brand', '!=', '')
            ->selectRaw('brand, count(*) as total')
            ->groupBy('brand')
            ->orderBy('brand')
            ->get()
            ->map(fn ($row) => ['name' => $row->brand, 'slug' => Str::slug($row->brand), 'total' => $row->total]);
    }

    public function brandIndex(): View
    {
        return view('pages.brands', ['brands' => $this->brandList()]);
    }

    public function brandShow(Request $request, string $slug): View
    {
        $brand = $this->brandList()->firstWhere('slug', $slug);
        abort_unless($brand, 404);

        $products = Product::active()
            ->where('brand', $brand['name'])
            ->tap(fn ($q) => $this->applySort($q, $request->query('sort')))
            ->paginate(20)
            ->withQueryString();

        return view('pages.brand', compact('brand', 'products'));
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
