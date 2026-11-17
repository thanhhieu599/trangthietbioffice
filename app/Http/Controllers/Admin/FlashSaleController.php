<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FlashSale;
use App\Models\Product;
use App\Support\Upload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Quản trị chương trình Flash Sale: thời gian, banner, sản phẩm + giá flash + số suất. */
class FlashSaleController extends Controller
{
    private const DIR = 'images/flash/uploads';

    public function index(): View
    {
        FlashSale::renewExpired(); // chương trình tự lặp: hiện đúng chu kỳ hiện tại

        return view('admin.flash-sales.index', [
            'sales' => FlashSale::withCount('items')->orderByDesc('starts_at')->get(),
        ]);
    }

    public function create(): View
    {
        $start = now()->addHour()->startOfHour();

        return view('admin.flash-sales.form', [
            'sale' => new FlashSale(['name' => 'Flash Sale '.now()->format('d/m'), 'starts_at' => $start, 'ends_at' => $start->copy()->addDay(), 'is_active' => true]),
            'rows' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $sale = new FlashSale;
        $this->save($request, $sale);

        return redirect()->route('admin.flash-sales.edit', $sale)->with('ok', 'Đã tạo chương trình Flash Sale.');
    }

    public function edit(FlashSale $flashSale): View
    {
        $rows = $flashSale->items()->with('product')->get()->filter->product->map(fn ($i) => [
            'product_id' => $i->product_id, 'name' => $i->product->name, 'sku' => $i->product->sku,
            'price' => (int) $i->product->price, 'image' => $i->product->mainImage(),
            'sale_price' => (int) $i->sale_price, 'quantity_limit' => $i->quantity_limit,
        ])->values()->all();

        return view('admin.flash-sales.form', ['sale' => $flashSale, 'rows' => $rows]);
    }

    public function update(Request $request, FlashSale $flashSale): RedirectResponse
    {
        $this->save($request, $flashSale);

        return redirect()->route('admin.flash-sales.edit', $flashSale)->with('ok', 'Đã lưu chương trình Flash Sale.');
    }

    public function destroy(FlashSale $flashSale): RedirectResponse
    {
        Upload::delete($flashSale->banner);
        Upload::delete($flashSale->banner_mobile);
        $flashSale->delete();

        return redirect()->route('admin.flash-sales.index')->with('ok', 'Đã xóa chương trình Flash Sale.');
    }

    /** Tìm sản phẩm (đang bán, có giá) để thêm vào chương trình. */
    public function products(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q'));
        $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_substr($q, 0, 100)).'%';

        return response()->json(Product::where('is_active', true)->whereNotNull('price')->where('price', '>', 0)
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->whereRaw("name like ? escape '!'", [$like])->orWhereRaw("sku like ? escape '!'", [$like])))
            ->orderBy('name')->limit(20)->get()
            ->map(fn (Product $p) => ['product_id' => $p->id, 'name' => $p->name, 'sku' => $p->sku, 'price' => (int) $p->price, 'image' => $p->mainImage()]));
    }

    private function save(Request $request, FlashSale $sale): void
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'banner' => ['nullable', ...Upload::RULE],
            'banner_mobile' => ['nullable', ...Upload::RULE],
            'items' => ['nullable', 'array', 'max:300'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id', 'distinct'],
            'items.*.sale_price' => ['required', 'integer', 'min:1'],
            'items.*.quantity_limit' => ['nullable', 'integer', 'min:1', 'max:1000000'],
        ], [
            'ends_at.after' => 'Thời gian kết thúc phải sau thời gian bắt đầu.',
            'items.*.sale_price.required' => 'Nhập giá flash sale cho mọi sản phẩm.',
            'items.*.product_id.distinct' => 'Một sản phẩm bị thêm hai lần.',
        ], ['name' => 'tên chương trình', 'starts_at' => 'thời gian bắt đầu', 'ends_at' => 'thời gian kết thúc']);

        $starts = Carbon::parse($data['starts_at']);
        $ends = Carbon::parse($data['ends_at']);
        $active = $request->boolean('is_active');

        // Giá flash phải thấp hơn giá gốc.
        $items = collect($data['items'] ?? []);
        $prices = Product::whereIn('id', $items->pluck('product_id'))->pluck('price', 'id');
        $bad = $items->filter(fn ($i) => ! ($prices[$i['product_id']] ?? 0) || $i['sale_price'] >= $prices[$i['product_id']]);
        if ($bad->isNotEmpty()) {
            throw ValidationException::withMessages(['items' => 'Giá flash sale phải thấp hơn giá gốc ('.$bad->count().' sản phẩm chưa đúng).']);
        }

        // Không cho hai chương trình đang bật chạy chồng thời gian (khách sẽ thấy giá không rõ ràng).
        if ($active) {
            $overlap = FlashSale::where('is_active', true)->whereKeyNot($sale->id)
                ->where('starts_at', '<', $ends)->where('ends_at', '>', $starts)->first();
            if ($overlap) {
                throw ValidationException::withMessages(['starts_at' => "Trùng thời gian với chương trình đang bật \"{$overlap->name}\" ({$overlap->starts_at->format('H:i d/m')} – {$overlap->ends_at->format('H:i d/m')})."]);
            }
        }

        DB::transaction(function () use ($request, $sale, $data, $starts, $ends, $active, $items) {
            $sale->fill(['name' => $data['name'], 'starts_at' => $starts, 'ends_at' => $ends, 'is_active' => $active, 'auto_renew' => $request->boolean('auto_renew')]);
            foreach (['banner', 'banner_mobile'] as $field) {
                if ($request->boolean("remove_{$field}") || $request->hasFile($field)) {
                    Upload::delete($sale->{$field});
                    $sale->{$field} = null;
                }
                if ($request->hasFile($field)) {
                    $sale->{$field} = Upload::image($request->file($field), self::DIR, 'flash-sale-'.$starts->format('Ymd'));
                }
            }
            $sale->save();

            $sale->items()->delete();
            foreach ($items->values() as $i => $row) {
                $sale->items()->create([
                    'product_id' => $row['product_id'],
                    'sale_price' => $row['sale_price'],
                    'quantity_limit' => $row['quantity_limit'] ?? null,
                    'sort_order' => $i,
                ]);
            }
        });
    }
}
