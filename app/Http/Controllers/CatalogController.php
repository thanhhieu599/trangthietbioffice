<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function home(): View
    {
        return view('pages.home', [
            'categories' => Category::active()->roots()->withCount('products')->get(),
            'featured' => Product::active()->where('is_featured', true)->with('category')->latest()->take(8)->get(),
            'posts' => Post::published()->latest('published_at')->take(3)->get(),
        ]);
    }

    public function category(Request $request, Category $category): View
    {
        abort_unless($category->is_active, 404);

        $sort = $request->query('sort');
        $products = Product::active()
            ->whereIn('category_id', $category->descendantIds())
            ->when($request->filled('brand'), fn ($q) => $q->where('brand', $request->query('brand')))
            ->when($sort === 'name', fn ($q) => $q->orderBy('name'), fn ($q) => $q->orderByDesc('is_featured')->orderBy('name'))
            ->paginate(24)
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
            ->take(4)
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
                ->take(48)
                ->get();

        return view('pages.search', compact('term', 'products'));
    }
}
