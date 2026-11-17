<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\PostComment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PostController extends Controller
{
    public function index(): View
    {
        $posts = Post::published()->latest('published_at')->paginate(12);
        CatalogController::abortIfPageOutOfRange($posts);

        return view('pages.posts.index', ['posts' => $posts]);
    }

    public function show(Post $post): View
    {
        abort_if($post->published_at === null || $post->published_at->isFuture(), 404);

        return view('pages.posts.show', [
            'post' => $post,
            'article' => $post->contentWithToc(),
            'related' => Post::published()->whereKeyNot($post->id)->latest('published_at')->take(3)->get(),
            'latest' => Post::published()->whereKeyNot($post->id)->latest('published_at')->take(6)->get(),
            'postCount' => Post::published()->count(),
            'categories' => Category::active()->roots()->get(['id', 'name', 'slug']),
            'comments' => $post->comments()->where('is_approved', true)->latest()->get(),
        ]);
    }

    /** Bình luận mới: lưu ở trạng thái chờ duyệt (chống spam), quản trị duyệt mới hiện. */
    public function comment(Request $request, Post $post): RedirectResponse
    {
        abort_if($post->published_at === null || $post->published_at->isFuture(), 404);

        // Bẫy spam: ô ẩn "website" phải để trống.
        if ($request->filled('website')) {
            return redirect($post->url().'#binh-luan')->with('comment_ok', true);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'content' => ['required', 'string', 'min:5', 'max:2000'],
        ], [
            'required' => 'Vui lòng nhập :attribute.',
            'email' => 'Email không hợp lệ.',
            'content.min' => 'Bình luận quá ngắn.',
            'max' => ':Attribute quá dài.',
        ], ['name' => 'họ tên', 'email' => 'email', 'content' => 'nội dung bình luận']);

        $post->comments()->create($data + ['user_id' => $request->user()?->id, 'ip' => $request->ip(), 'is_approved' => false]);

        return redirect($post->url().'#binh-luan')->with('comment_ok', true);
    }
}
