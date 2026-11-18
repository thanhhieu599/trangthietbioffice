<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Tin nhắn từ trang Liên hệ + ảnh của trang Liên hệ (ảnh nền đầu trang, hình ảnh hoạt động). */
class ContactController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', 'new');

        return view('admin.contacts', [
            'status' => $status,
            'messages' => ContactMessage::query()
                ->when($status === 'new', fn ($q) => $q->where('is_read', false))
                ->latest()->paginate(30)->withQueryString(),
            'counts' => ['new' => ContactMessage::where('is_read', false)->count(), 'all' => ContactMessage::count()],
        ]);
    }

    public function toggleRead(ContactMessage $message): RedirectResponse
    {
        $message->update(['is_read' => ! $message->is_read]);

        return back()->with('ok', $message->is_read ? 'Đã đánh dấu đã xử lý.' : 'Đã chuyển về chưa xử lý.');
    }

    public function destroy(ContactMessage $message): RedirectResponse
    {
        $message->delete();

        return back()->with('ok', 'Đã xóa tin nhắn.');
    }
}
