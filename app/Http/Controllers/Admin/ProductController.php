<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Redirect;
use App\Support\Html;
use App\Support\SeoAudit;
use App\Support\Upload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    private const DIR = 'images/products/uploads';

    public function index(Request $request): View
    {
        $products = Product::with('category')
            ->when($request->query('q'), fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%")->orWhere('brand', 'like', "%{$s}%")))
            ->when($request->query('category'), function ($q, $id) {
                $cat = Category::find($id);
                $q->whereIn('category_id', $cat ? $cat->descendantIds() : [0]);
            })
            ->when($request->query('status') === 'active', fn ($q) => $q->where('is_active', true))
            ->when($request->query('status') === 'hidden', fn ($q) => $q->where('is_active', false))
            ->when($request->query('status') === 'featured', fn ($q) => $q->where('is_featured', true))
            ->latest('updated_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'categoryOptions' => CategoryController::options(),
        ]);
    }

    public function create(): View
    {
        return view('admin.products.form', [
            'product' => new Product(['is_active' => true, 'min_order_qty' => 1]),
            'categoryOptions' => CategoryController::options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $product = new Product;
        $this->fill($request, $product);
        $product->save();
        Redirect::release($product->url());
        SeoAudit::flush();

        return redirect()->route('admin.products.edit', $product)->with('ok', 'Đã thêm sản phẩm.');
    }

    public function edit(Product $product): View
    {
        return view('admin.products.form', [
            'product' => $product,
            'categoryOptions' => CategoryController::options(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $oldUrl = $product->url();
        $this->fill($request, $product);
        $product->save();

        if ($oldUrl !== $product->url()) {
            Redirect::record($oldUrl, $product->url());
        }
        Redirect::release($product->url());
        SeoAudit::flush();

        return redirect()->route('admin.products.edit', $product)->with('ok',
            $oldUrl !== $product->url() ? 'Đã lưu. Đường dẫn cũ được tự chuyển hướng 301 sang đường dẫn mới.' : 'Đã lưu sản phẩm.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        // Link cũ chuyển về danh mục của sản phẩm (giữ giá trị SEO), không có thì về trang chủ.
        Redirect::record($product->url(), $product->category?->url() ?? route('home'));
        foreach ($product->images ?? [] as $img) {
            Upload::delete($img);
        }
        $product->delete();
        SeoAudit::flush();

        return redirect()->route('admin.products.index')->with('ok', 'Đã xóa sản phẩm. Link cũ được chuyển hướng 301 về danh mục.');
    }

    private function fill(Request $request, Product $product): void
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($product->id)],
            'slug' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'brand' => ['nullable', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:50'],
            'pack_size' => ['nullable', 'string', 'max:255'],
            'price' => ['nullable', 'integer', 'min:0'],
            'compare_price' => ['nullable', 'integer', 'min:0'],
            'min_order_qty' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'stock_status' => ['nullable', Rule::in(array_keys(Product::STOCK))],
            'short_description' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string', 'max:'.Html::MAX_LENGTH],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'focus_keyword' => ['nullable', 'string', 'max:255'],
            'specs' => ['nullable', 'array', 'max:50'],
            'specs.*.k' => ['nullable', 'string', 'max:255'],
            'specs.*.v' => ['nullable', 'string', 'max:500'],
            'keep_images' => ['nullable', 'array'],
            'keep_images.*' => ['string'],
            'new_images' => ['nullable', 'array', 'max:10'],
            'new_images.*' => Upload::RULE,
        ], [
            'sku.unique' => 'Mã SKU này đã có sản phẩm khác dùng.',
            'description.max' => 'Mô tả chi tiết quá dài (tối đa 100.000 ký tự HTML).',
            'new_images.*.max' => 'Mỗi ảnh tối đa 3 MB.',
            'new_images.*.mimes' => 'Ảnh phải là JPG, PNG hoặc WebP.',
        ], ['name' => 'tên sản phẩm']);

        $product->fill([
            'name' => $data['name'],
            'sku' => trim($data['sku']),
            'slug' => SeoAudit::uniqueSlug(Product::class, ($data['slug'] ?? null) ?: $data['name'], $product->id),
            'category_id' => $data['category_id'] ?? null,
            'brand' => $data['brand'] ?? null,
            'unit' => $data['unit'] ?? null,
            'pack_size' => $data['pack_size'] ?? null,
            'price' => $data['price'] ?? null,
            'compare_price' => $data['compare_price'] ?? null,
            'min_order_qty' => $data['min_order_qty'] ?? 1,
            'stock_status' => $data['stock_status'] ?? ($product->stock_status ?: 'in_stock'),
            'short_description' => $data['short_description'] ?? null,
            'description' => Html::clean($data['description'] ?? null),
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'focus_keyword' => $data['focus_keyword'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'is_featured' => $request->boolean('is_featured'),
            'specs' => collect($data['specs'] ?? [])
                ->filter(fn ($s) => filled($s['k'] ?? null) && filled($s['v'] ?? null))
                ->mapWithKeys(fn ($s) => [trim($s['k']) => trim($s['v'])])
                ->all() ?: null,
        ]);

        // Ảnh: giữ các ảnh cũ theo thứ tự đã sắp xếp (chỉ nhận ảnh vốn thuộc sản phẩm), thêm ảnh mới vào cuối.
        $current = $product->images ?? [];
        $keep = array_values(array_intersect($data['keep_images'] ?? [], $current));
        foreach (array_diff($current, $keep) as $removed) {
            Upload::delete($removed);
        }
        foreach ($request->file('new_images', []) as $i => $file) {
            $keep[] = Upload::image($file, self::DIR, $product->slug.($i ? '-'.($i + 1) : ''));
        }
        $product->images = $keep ?: null;
    }
}
