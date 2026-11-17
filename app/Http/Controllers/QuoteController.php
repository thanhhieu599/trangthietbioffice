<?php

namespace App\Http\Controllers;

use App\Mail\NewQuoteRequest;
use App\Models\Product;
use App\Models\QuoteRequest;
use App\Services\Sapo\SapoSync;
use App\Support\CustomerInfo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class QuoteController extends Controller
{
    public function create(Request $request): View
    {
        return view('pages.quote', [
            'user' => $request->user(),
            'provinces' => config('site.provinces', []),
        ]);
    }

    /**
     * Bước đặt hàng. Khách đã đăng nhập: thông tin điền sẵn từ tài khoản, đơn gắn vào tài khoản.
     * Khách chưa đăng nhập: vẫn đặt được nhưng bắt buộc nhập đủ họ tên, điện thoại, email, địa chỉ giao hàng.
     */
    public function store(Request $request): RedirectResponse
    {
        // Bẫy chống spam: ô ẩn "website" phải để trống.
        if ($request->filled('website')) {
            return redirect()->route('quote.thanks');
        }

        $customer = CustomerInfo::rules();
        $data = $request->validate([
            'contact_name' => $customer['name'],
            'phone' => $customer['phone'],
            'email' => ['required', 'email', 'max:255'],
            'company' => $customer['company'],
            'tax_code' => $customer['tax_code'],
            'address' => $customer['address'],
            'ward' => $customer['ward'],
            'province' => $customer['province'],
            'frequency' => ['nullable', 'in:once,monthly,quarterly'],
            'note' => ['nullable', 'string', 'max:5000'],
            'source' => ['nullable', 'string', 'max:255'],
            'items' => ['nullable', 'array', 'max:200'],
            'items.*.sku' => ['required_with:items', 'string', 'max:100'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1', 'max:1000000'],
            // Nghị định 13/2023/NĐ-CP: khách đồng ý cho xử lý thông tin cá nhân để giao hàng / báo giá.
            'consent' => ['accepted'],
        ], CustomerInfo::messages(), CustomerInfo::attributes() + ['note' => 'ghi chú', 'consent' => 'việc xử lý thông tin cá nhân']);
        unset($data['consent']);

        $user = $request->user();
        $data['user_id'] = $user?->id;
        $data['delivery_area'] = $data['province']; // giữ cột cũ cho email / báo cáo đang dùng

        // Khách đăng nhập chọn "lưu vào tài khoản": cập nhật thông tin giao hàng mặc định.
        if ($user && $request->boolean('save_to_account')) {
            $user->update([
                'name' => $data['contact_name'],
                'phone' => $data['phone'],
                'company' => $data['company'] ?? null,
                'tax_code' => $data['tax_code'] ?? null,
                'address' => $data['address'],
                'ward' => $data['ward'],
                'province' => $data['province'],
            ]);
        }

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
                $flash = $product?->flashItem();
                $quote->items()->create([
                    'product_id' => $product?->id,
                    'sku' => $item['sku'],
                    'name' => $product?->name ?? $item['sku'],
                    'quantity' => $item['quantity'],
                    // Ghi lại giá lúc đặt (giá flash sale nếu đang chạy) để đơn / Sapo không lệch giá về sau.
                    'unit_price' => $product?->currentPrice(),
                    'is_flash_sale' => (bool) $flash,
                ]);
            }

            return $quote;
        });

        // Đẩy khách + đơn sang Sapo (chạy ngầm; Sapo lỗi thì tự thử lại, đơn vẫn đã lưu trên web).
        if ($user && $request->boolean('save_to_account')) {
            SapoSync::customer($user);
        }
        SapoSync::quote($quote);

        if ($to = config('site.quote_notify_email')) {
            try {
                Mail::to($to)->send(new NewQuoteRequest($quote->load('items')));
            } catch (\Throwable $e) {
                // Không làm hỏng trải nghiệm khách nếu máy chủ email lỗi; yêu cầu đã lưu trong DB.
                Log::error('Gửi email báo giá thất bại', ['quote' => $quote->id, 'error' => $e->getMessage()]);
            }
        }

        return redirect()->route('quote.thanks')->with(['quote_id' => $quote->id, 'quote_code' => $quote->code()]);
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
