<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use App\Models\QuoteRequest;
use App\Models\Redirect;
use App\Support\SeoAudit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        // Danh sách việc cần làm SEO: lấy các mục còn lỗi, nhiều lỗi nhất lên đầu.
        $todo = fn ($items, callable $audit, string $route, string $label) => $items
            ->map(fn ($m) => ['model' => $m, 'issues' => $audit($m), 'route' => route($route, $m), 'name' => $m->{$label}])
            ->filter(fn ($r) => $r['issues'])
            ->sortByDesc(fn ($r) => count($r['issues']));

        $postTodo = $todo(Post::latest('id')->get(), [SeoAudit::class, 'post'], 'admin.posts.edit', 'title');
        $productTodo = $todo(Product::where('is_active', true)->latest('updated_at')->get(), [SeoAudit::class, 'product'], 'admin.products.edit', 'name');
        $categoryTodo = $todo(Category::where('is_active', true)->get(), [SeoAudit::class, 'category'], 'admin.categories.edit', 'name');

        return view('admin.dashboard', [
            'stats' => [
                'posts_published' => Post::published()->count(),
                'posts_draft' => Post::whereNull('published_at')->count(),
                'posts_scheduled' => Post::where('published_at', '>', now())->count(),
                'products_active' => Product::where('is_active', true)->count(),
                'products_hidden' => Product::where('is_active', false)->count(),
                'categories' => Category::count(),
                'quotes_new' => QuoteRequest::where('status', 'new')->count(),
            ],
            'postTodo' => $postTodo,
            'productTodo' => $productTodo,
            'categoryTodo' => $categoryTodo,
            'recentPosts' => Post::latest('updated_at')->take(5)->get(),
            'recentQuotes' => QuoteRequest::latest()->take(5)->get(),
        ]);
    }

    /** Ảnh chèn trong trình soạn thảo (bài viết, mô tả sản phẩm, nội dung danh mục). */
    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate(['image' => ['required', ...\App\Support\Upload::RULE], 'name' => ['nullable', 'string', 'max:120']], [
            'image.max' => 'Ảnh tối đa 3 MB.',
            'image.mimes' => 'Ảnh phải là JPG, PNG hoặc WebP.',
        ]);

        return response()->json([
            'url' => \App\Support\Upload::image($request->file('image'), 'images/editor/uploads', $request->input('name')),
        ]);
    }

    public function redirects(): View
    {
        return view('admin.redirects', ['redirects' => Redirect::latest('updated_at')->paginate(50)]);
    }

    public function storeRedirect(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'from_path' => ['required', 'string', 'max:255'],
            'to_path' => ['required', 'string', 'max:500'],
        ], [], ['from_path' => 'link cũ', 'to_path' => 'link mới']);

        Redirect::record($data['from_path'], $data['to_path']);

        return back()->with('ok', 'Đã thêm chuyển hướng 301.');
    }

    public function destroyRedirect(Redirect $redirect): RedirectResponse
    {
        $redirect->delete();

        return back()->with('ok', 'Đã xóa chuyển hướng.');
    }
}
