<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\ContactMessage;
use App\Models\Product;
use App\Support\CustomerInfo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function show(Request $request): View
    {
        // Đến từ nút "Liên hệ báo giá" ở trang sản phẩm chưa có giá: điền sẵn nội dung.
        $prefill = null;
        if ($sku = $request->query('san-pham')) {
            $product = Product::active()->where('sku', (string) $sku)->first();
            if ($product) {
                $qty = max(1, min(1000000, (int) $request->query('so-luong', 1)));
                $prefill = "Tôi cần báo giá sản phẩm: {$product->name} (Mã: {$product->sku}), số lượng: {$qty}".($product->unit ? " {$product->unit}" : '').'.';
            }
        }

        return view('pages.contact', [
            'hero' => Banner::where('position', 'contact_hero')->active()->first(),
            'topics' => ContactMessage::TOPICS,
            'prefill' => $prefill,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Bẫy chống spam: ô ẩn "website" phải để trống (bot thường điền hết các ô).
        if ($request->filled('website')) {
            return redirect()->to(route('contact').'#gui-tin-nhan')->with('contact_sent', true);
        }

        $data = $request->validate([
            'topic' => ['required', Rule::in(array_keys(ContactMessage::TOPICS))],
            'name' => ['required', 'string', 'max:255'],
            'phone' => CustomerInfo::PHONE,
            'email' => ['required', 'email', 'max:255'],
            'content' => ['required', 'string', 'min:5', 'max:3000'],
        ], CustomerInfo::messages() + ['topic.required' => 'Vui lòng chọn nội dung cần hỗ trợ.'],
            ['name' => 'họ tên', 'phone' => 'số điện thoại', 'email' => 'email', 'content' => 'nội dung', 'topic' => 'nội dung cần hỗ trợ']);

        $message = ContactMessage::create($data + ['ip' => $request->ip()]);

        if ($to = config('site.quote_notify_email')) {
            try {
                Mail::raw(
                    "Tin nhắn liên hệ mới từ website\n\n"
                    ."Chủ đề: {$message->topicLabel()}\nHọ tên: {$message->name}\nĐiện thoại: {$message->phone}\nEmail: {$message->email}\n\n"
                    .$message->content,
                    fn ($m) => $m->to($to)->replyTo($message->email, $message->name)->subject('[Liên hệ] '.$message->topicLabel().' – '.$message->name)
                );
            } catch (\Throwable $e) {
                // Máy chủ email lỗi không làm hỏng trải nghiệm khách; tin nhắn đã lưu trong DB.
                Log::error('Gửi email liên hệ thất bại', ['message' => $message->id, 'error' => $e->getMessage()]);
            }
        }

        return redirect()->to(route('contact').'#gui-tin-nhan')->with('contact_sent', true);
    }
}
