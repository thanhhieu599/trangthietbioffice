<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\FlashSale;
use App\Support\SeoAudit;
use App\Support\SiteImage;
use App\Support\Upload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Mục "Hình ảnh": một nơi quản lý mọi ảnh banner trên website.
 *   Tab Trang chủ  -> BannerController (banner chính, bên phải, hàng dưới)
 *   Tab Danh mục   -> banner ngang đầu trang danh mục + banner dọc ở khối sản phẩm trang chủ
 *   Tab Flash Sale -> banner trang /flash-sale (máy tính + điện thoại)
 *   Tab Liên hệ    -> ảnh nền đầu trang Liên hệ (dùng bảng banners)
 */
class MediaController extends Controller
{
    /** Ô ảnh của danh mục: [nhãn, kích thước khuyến nghị, tỉ lệ khung, chiều ngang tối thiểu, nơi hiển thị]. */
    public const CATEGORY_SLOTS = [
        'page_banner' => ['Banner ngang', '1200 × 380', '1200/380', 800, 'Đầu trang danh mục (khi bấm "Xem tất cả")'],
        'banner' => ['Banner dọc', '600 × 1000', '600/1000', 300, 'Bên trái khối sản phẩm ở trang chủ'],
    ];

    public const FLASH_SLOTS = [
        'banner' => ['Banner máy tính', '1200 × 300', '1200/300'],
        'banner_mobile' => ['Banner điện thoại', '800 × 400', '800/400'],
    ];

    public function categories(): View
    {
        return view('admin.media.categories', [
            'rows' => CategoryController::flatten(Category::orderBy('sort_order')->orderBy('name')->get()),
        ]);
    }

    public function flash(): View
    {
        return view('admin.media.flash', ['sales' => FlashSale::latest('starts_at')->get()]);
    }

    /** Tab Thương hiệu: banner lớn đầu trang /thuong-hieu/{slug} cho từng thương hiệu đang có sản phẩm. */
    public function brands(): View
    {
        $brands = \App\Models\Product::whereNotNull('brand')->where('brand', '!=', '')
            ->selectRaw('brand, count(*) as total, sum(is_active) as active')->groupBy('brand')->get()
            ->groupBy(fn ($r) => \Illuminate\Support\Str::slug($r->brand))
            ->map(fn ($rows, $slug) => ['slug' => $slug, 'name' => $rows->sortByDesc('total')->first()->brand,
                'total' => $rows->sum('total'), 'active' => (int) $rows->sum('active'), 'banner' => SiteImage::brandBanner($slug)])
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values();

        return view('admin.media.brands', ['brands' => $brands]);
    }

    public function updateBrand(Request $request, string $slug): RedirectResponse
    {
        abort_unless(preg_match('/^[a-z0-9-]{1,100}$/', $slug), 404);
        $dir = public_path(self::BRAND_DIR);
        $remove = function () use ($dir, $slug) {
            foreach (['webp', 'jpg', 'jpeg', 'png'] as $ext) {
                @unlink("$dir/$slug.$ext");
            }
        };

        if ($request->boolean('remove')) {
            $remove();

            return back()->with('ok', 'Đã xóa banner thương hiệu.');
        }

        $request->validate(['image' => ['required', ...Upload::RULE, 'dimensions:min_width=800']], $this->messages(800, '1200 × 300'));
        $file = $request->file('image');
        $ext = match ($file->extension()) { 'jpeg', 'jpg' => 'jpg', 'png' => 'png', 'webp' => 'webp', default => abort(422) }; // theo NỘI DUNG ảnh
        $remove();
        $file->move($dir, "$slug.$ext");

        return back()->with('ok', 'Đã cập nhật banner thương hiệu.');
    }

    private const BRAND_DIR = 'images/brands/banners';

    public function contact(): View
    {
        return view('admin.media.contact', [
            'images' => Banner::whereIn('position', array_keys(Banner::CONTACT_POSITIONS))->orderBy('sort_order')->orderBy('id')->get()->groupBy('position'),
        ]);
    }

    public function updateCategory(Request $request, Category $category, string $slot): RedirectResponse
    {
        abort_unless(isset(self::CATEGORY_SLOTS[$slot]), 404);
        [$label, $size, , $minWidth] = self::CATEGORY_SLOTS[$slot];

        if ($request->boolean('remove')) {
            Upload::delete($category->{$slot});
            $category->forceFill([$slot => null])->save();
            SeoAudit::flush();

            return back()->with('ok', "Đã xóa {$label} của “{$category->name}”.");
        }

        $request->validate(['image' => ['required', ...Upload::RULE, 'dimensions:min_width='.$minWidth]], $this->messages($minWidth, $size));
        Upload::delete($category->{$slot});
        $prefix = $slot === 'banner' ? 'banner-' : 'banner-ngang-';
        $category->forceFill([$slot => Upload::image($request->file('image'), 'images/categories/uploads', $prefix.$category->slug)])->save();
        SeoAudit::flush();

        return back()->with('ok', "Đã cập nhật {$label} cho “{$category->name}”.");
    }

    public function updateFlash(Request $request, FlashSale $flashSale, string $slot): RedirectResponse
    {
        abort_unless(isset(self::FLASH_SLOTS[$slot]), 404);
        $label = self::FLASH_SLOTS[$slot][0];

        if ($request->boolean('remove')) {
            Upload::delete($flashSale->{$slot});
            $flashSale->forceFill([$slot => null])->save();

            return back()->with('ok', "Đã xóa {$label} của “{$flashSale->name}”.");
        }

        $request->validate(['image' => ['required', ...Upload::RULE]], $this->messages(0, self::FLASH_SLOTS[$slot][1]));
        Upload::delete($flashSale->{$slot});
        $flashSale->forceFill([$slot => Upload::image($request->file('image'), 'images/flash/uploads', 'flash-sale-'.$flashSale->starts_at->format('Ymd'))])->save();

        return back()->with('ok', "Đã cập nhật {$label} cho “{$flashSale->name}”.");
    }

    private function messages(int $minWidth, string $size): array
    {
        return [
            'image.required' => 'Vui lòng chọn ảnh.',
            'image.mimes' => 'Ảnh phải là JPG, PNG hoặc WebP.',
            'image.extensions' => 'Tên file ảnh phải có đuôi .jpg, .png hoặc .webp.',
            'image.max' => 'Ảnh tối đa 3 MB – hãy nén ảnh (ví dụ tinypng.com) trước khi tải lên.',
            'image.uploaded' => 'Ảnh chưa tải lên được (thường do ảnh quá nặng) – hãy nén ảnh rồi thử lại.',
            'image.dimensions' => "Ảnh quá nhỏ: chiều ngang tối thiểu {$minWidth} px (khuyến nghị {$size} px).",
        ];
    }
}
