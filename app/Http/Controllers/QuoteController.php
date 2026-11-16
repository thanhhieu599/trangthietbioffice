<?php

namespace App\Http\Controllers;

use App\Mail\NewQuoteRequest;
use App\Models\Product;
use App\Models\QuoteRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class QuoteController extends Controller
{
    public function create(): View
    {
        return view('pages.quote');
    }

    public function store(Request $request): RedirectResponse
    {
        // Bẫy chống spam: ô ẩn "website" phải để trống.
        if ($request->filled('website')) {
            return redirect()->route('quote.thanks');
        }

        $data = $request->validate([
            'company' => ['required', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+().\s-]{8,}$/'],
            'email' => ['required', 'email', 'max:255'],
            'tax_code' => ['nullable', 'string', 'max:20'],
            'delivery_area' => ['nullable', 'string', 'max:255'],
            'frequency' => ['nullable', 'in:once,monthly,quarterly'],
            'note' => ['nullable', 'string', 'max:5000'],
            'source' => ['nullable', 'string', 'max:255'],
            'items' => ['nullable', 'array', 'max:200'],
            'items.*.sku' => ['required_with:items', 'string', 'max:100'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1', 'max:1000000'],
        ], [], [
            'company' => 'tên công ty',
            'contact_name' => 'người liên hệ',
            'phone' => 'số điện thoại',
            'note' => 'ghi chú',
        ]);

        if (empty($data['items']) && blank($data['note'] ?? null)) {
            return back()->withInput()->withErrors([
                'note' => 'Vui lòng chọn sản phẩm hoặc ghi danh mục cần báo giá.',
            ]);
        }

        $quote = DB::transaction(function () use ($data) {
            $quote = QuoteRequest::create($data);

            $skus = collect($data['items'] ?? [])->pluck('sku');
            $products = Product::whereIn('sku', $skus)->get()->keyBy('sku');

            foreach ($data['items'] ?? [] as $item) {
                $product = $products->get($item['sku']);
                $quote->items()->create([
                    'product_id' => $product?->id,
                    'sku' => $item['sku'],
                    'name' => $product?->name ?? $item['sku'],
                    'quantity' => $item['quantity'],
                ]);
            }

            return $quote;
        });

        if ($to = config('site.quote_notify_email')) {
            try {
                Mail::to($to)->send(new NewQuoteRequest($quote->load('items')));
            } catch (\Throwable $e) {
                // Không làm hỏng trải nghiệm khách nếu máy chủ email lỗi; yêu cầu đã lưu trong DB.
                Log::error('Gửi email báo giá thất bại', ['quote' => $quote->id, 'error' => $e->getMessage()]);
            }
        }

        return redirect()->route('quote.thanks')->with('quote_id', $quote->id);
    }

    public function thanks(): View
    {
        return view('pages.quote-thanks');
    }

    /** Trả thông tin sản phẩm cho danh sách báo giá (lưu ở trình duyệt). */
    public function lookup(Request $request)
    {
        $skus = array_slice((array) $request->query('skus', []), 0, 200);

        return Product::active()
            ->whereIn('sku', $skus)
            ->get(['sku', 'name', 'slug', 'unit', 'images'])
            ->map(fn (Product $p) => [
                'sku' => $p->sku,
                'name' => $p->name,
                'unit' => $p->unit,
                'url' => $p->url(),
                'image' => $p->mainImage(),
            ]);
    }
}
