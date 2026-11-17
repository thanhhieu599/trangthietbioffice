<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PostComment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Duyệt bình luận bài viết (bình luận mới luôn chờ duyệt). */
class CommentController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', 'pending');

        return view('admin.comments', [
            'status' => $status,
            'comments' => PostComment::with('post:id,title,slug')
                ->when($status === 'pending', fn ($q) => $q->where('is_approved', false))
                ->when($status === 'approved', fn ($q) => $q->where('is_approved', true))
                ->latest()->paginate(30)->withQueryString(),
            'counts' => [
                'pending' => PostComment::where('is_approved', false)->count(),
                'approved' => PostComment::where('is_approved', true)->count(),
            ],
        ]);
    }

    public function approve(PostComment $comment): RedirectResponse
    {
        $comment->update(['is_approved' => ! $comment->is_approved]);

        return back()->with('ok', $comment->is_approved ? 'Đã duyệt – bình luận hiện trên bài viết.' : 'Đã ẩn bình luận.');
    }

    public function destroy(PostComment $comment): RedirectResponse
    {
        $comment->delete();

        return back()->with('ok', 'Đã xóa bình luận.');
    }
}
