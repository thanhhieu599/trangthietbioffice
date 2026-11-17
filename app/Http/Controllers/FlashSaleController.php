<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\FlashSale;
use Illuminate\View\View;

/** Trang "Xem tất cả" Flash Sale: banner + sản phẩm đang sale nhóm theo danh mục (danh mục không có thì ẩn). */
class FlashSaleController extends Controller
{
    public function index(): View
    {
        ['sale' => $sale, 'items' => $items] = FlashSale::context();

        // Nhóm theo danh mục gốc (cấp 1) cho gọn; sản phẩm chưa có danh mục vào nhóm "Sản phẩm khác".
        $roots = Category::whereNull('parent_id')->orderBy('sort_order')->get()->keyBy('id');
        $rootOf = function (?Category $c) {
            while ($c && $c->parent_id) {
                $c = $c->parent;
            }

            return $c;
        };

        $groups = $items->groupBy(fn ($i) => $rootOf($i->product->category)?->id ?? 0)
            ->map(fn ($list, $rootId) => [
                'category' => $roots->get($rootId),
                'name' => $roots->get($rootId)?->name ?? 'Sản phẩm khác',
                'slug' => $roots->get($rootId)?->slug ?? 'khac',
                'items' => $list->values(),
            ])
            ->sortBy(fn ($g) => $g['category']?->sort_order ?? PHP_INT_MAX)
            ->values();

        return view('pages.flash-sale', compact('sale', 'groups', 'items'));
    }
}
