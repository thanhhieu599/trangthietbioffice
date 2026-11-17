<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Redirect;
use App\Support\Html;
use App\Support\SeoAudit;
use App\Support\Upload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    private const DIR = 'images/categories/uploads';

    public function index(): View
    {
        $all = Category::withCount('products')->orderBy('sort_order')->orderBy('name')->get();

        return view('admin.categories.index', ['rows' => self::flatten($all)]);
    }

    public function create(Request $request): View
    {
        return view('admin.categories.form', [
            'category' => new Category([
                'is_active' => true,
                'parent_id' => $request->integer('parent') ?: null,
                'sort_order' => 0,
            ]),
            'parentOptions' => self::options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $category = new Category;
        $this->fill($request, $category);
        $category->save();
        Redirect::release($category->url());
        SeoAudit::flush();

        return redirect()->route('admin.categories.edit', $category)->with('ok', 'Đã tạo danh mục.');
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.form', [
            'category' => $category->loadCount('products'),
            'parentOptions' => self::options(exclude: $category),
        ]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $oldUrl = $category->url();
        $this->fill($request, $category);
        $category->save();

        if ($oldUrl !== $category->url()) {
            Redirect::record($oldUrl, $category->url());
        }
        Redirect::release($category->url());
        SeoAudit::flush();

        return redirect()->route('admin.categories.edit', $category)->with('ok',
            $oldUrl !== $category->url() ? 'Đã lưu. Đường dẫn cũ được tự chuyển hướng 301 sang đường dẫn mới.' : 'Đã lưu danh mục.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $products = $category->products()->count();
        $children = Category::where('parent_id', $category->id)->count();
        if ($products || $children) {
            return back()->withErrors(['category' => "Không xóa được: danh mục còn {$products} sản phẩm và {$children} danh mục con. Hãy chuyển chúng sang danh mục khác trước (hoặc chọn \"Ẩn\" thay vì xóa)."]);
        }

        Redirect::record($category->url(), $category->parent?->url() ?? route('home'));
        Upload::delete($category->image);
        $category->delete();
        SeoAudit::flush();

        return redirect()->route('admin.categories.index')->with('ok', 'Đã xóa danh mục. Link cũ được chuyển hướng 301.');
    }

    private function fill(Request $request, Category $category): void
    {
        $forbidden = $category->exists ? self::subtreeIds($category) : [];

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:categories,id', Rule::notIn($forbidden)],
            'intro' => ['nullable', 'string', 'max:2000'],
            'content' => ['nullable', 'string', 'max:'.Html::MAX_LENGTH],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'focus_keyword' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'image' => ['nullable', ...Upload::RULE],
        ], [
            'content.max' => 'Nội dung quá dài (tối đa 100.000 ký tự HTML).',
            'parent_id.not_in' => 'Không thể chọn chính danh mục này hoặc danh mục con của nó làm danh mục cha.',
        ], ['name' => 'tên danh mục']);

        $category->fill([
            'name' => $data['name'],
            'slug' => SeoAudit::uniqueSlug(Category::class, ($data['slug'] ?? null) ?: $data['name'], $category->id),
            'parent_id' => $data['parent_id'] ?? null,
            'intro' => $data['intro'] ?? null,
            'content' => Html::clean($data['content'] ?? null),
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'focus_keyword' => $data['focus_keyword'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
            'show_on_home' => $request->boolean('show_on_home'),
        ]);

        if ($request->boolean('remove_image') || $request->hasFile('image')) {
            Upload::delete($category->image);
            $category->image = null;
        }
        if ($request->hasFile('image')) {
            $category->image = Upload::image($request->file('image'), self::DIR, $category->slug);
        }
    }

    /** Danh mục dạng cây phẳng: [['category' => ..., 'depth' => 0], ...] (gồm cả danh mục ẩn). */
    public static function flatten(Collection $all, ?int $parentId = null, int $depth = 0): Collection
    {
        return $all->where('parent_id', $parentId)->values()->flatMap(fn (Category $c) => collect([
            ['category' => $c, 'depth' => $depth],
        ])->merge(self::flatten($all, $c->id, $depth + 1)));
    }

    /** Danh sách cho ô chọn: [id => '— — Tên'], bỏ $exclude và nhánh con của nó. */
    public static function options(?Category $exclude = null): array
    {
        $skip = $exclude ? self::subtreeIds($exclude) : [];

        return self::flatten(Category::orderBy('sort_order')->orderBy('name')->get())
            ->reject(fn ($r) => in_array($r['category']->id, $skip, true))
            ->mapWithKeys(fn ($r) => [$r['category']->id => str_repeat('— ', $r['depth']).$r['category']->name
                .($r['category']->is_active ? '' : ' (ẩn)')])
            ->all();
    }

    /** ID của danh mục và mọi danh mục con cháu (kể cả đang ẩn). */
    private static function subtreeIds(Category $category): array
    {
        $all = Category::get(['id', 'parent_id']);
        $ids = [$category->id];
        for ($i = 0; $i < count($ids); $i++) {
            foreach ($all->where('parent_id', $ids[$i]) as $child) {
                $ids[] = $child->id;
            }
        }

        return $ids;
    }
}
