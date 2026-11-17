<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Redirect;
use App\Support\Html;
use App\Support\SeoAudit;
use App\Support\Upload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class PostController extends Controller
{
    private const DIR = 'images/posts/uploads';

    public function index(Request $request): View
    {
        $status = $request->query('status');

        $posts = Post::query()
            ->when($request->query('q'), fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
            ->when($status === 'published', fn ($q) => $q->published())
            ->when($status === 'draft', fn ($q) => $q->whereNull('published_at'))
            ->when($status === 'scheduled', fn ($q) => $q->where('published_at', '>', now()))
            ->orderByRaw('published_at is null desc')
            ->latest('published_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.posts.index', [
            'posts' => $posts,
            'counts' => [
                'all' => Post::count(),
                'published' => Post::published()->count(),
                'draft' => Post::whereNull('published_at')->count(),
                'scheduled' => Post::where('published_at', '>', now())->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.posts.form', ['post' => new Post(['author' => null])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $post = new Post;
        $this->fill($request, $post);
        $post->save();
        Redirect::release($post->url());
        SeoAudit::flush();

        return redirect()->route('admin.posts.edit', $post)->with('ok', 'Đã tạo bài viết.');
    }

    public function edit(Post $post): View
    {
        return view('admin.posts.form', ['post' => $post]);
    }

    public function update(Request $request, Post $post): RedirectResponse
    {
        $oldUrl = $post->url();
        $this->fill($request, $post);
        $post->save();

        if ($oldUrl !== $post->url()) {
            Redirect::record($oldUrl, $post->url());
        }
        Redirect::release($post->url());
        SeoAudit::flush();

        $msg = $oldUrl !== $post->url() ? 'Đã lưu. Đường dẫn cũ được tự chuyển hướng 301 sang đường dẫn mới.' : 'Đã lưu bài viết.';

        return redirect()->route('admin.posts.edit', $post)->with('ok', $msg);
    }

    public function destroy(Post $post): RedirectResponse
    {
        if ($post->published_at) {
            Redirect::record($post->url(), route('posts.index'));
        }
        Upload::delete($post->image);
        $post->delete();
        SeoAudit::flush();

        return redirect()->route('admin.posts.index')->with('ok', 'Đã xóa bài viết. Link cũ được chuyển hướng 301 về trang Tin tức.');
    }

    private function fill(Request $request, Post $post): void
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string', 'max:'.Html::MAX_LENGTH],
            'author' => ['nullable', 'string', 'max:100'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'focus_keyword' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:draft,publish,schedule'],
            'published_at' => ['nullable', 'date', 'required_if:status,schedule'],
            'image' => ['nullable', ...Upload::RULE],
            'remove_image' => ['nullable', 'boolean'],
            'faqs' => ['nullable', 'array', 'max:30'],
            'faqs.*.q' => ['nullable', 'string', 'max:500'],
            'faqs.*.a' => ['nullable', 'string', 'max:3000'],
        ], [
            'content.required' => 'Vui lòng nhập nội dung bài viết.',
            'content.max' => 'Nội dung quá dài (tối đa 100.000 ký tự HTML). Hãy chia thành nhiều bài.',
            'published_at.required_if' => 'Chọn ngày giờ đăng khi hẹn giờ.',
            'image.max' => 'Ảnh tối đa 3 MB.',
        ], ['title' => 'tiêu đề']);

        $post->fill([
            'title' => $data['title'],
            'slug' => SeoAudit::uniqueSlug(Post::class, ($data['slug'] ?? null) ?: $data['title'], $post->id),
            'excerpt' => $data['excerpt'] ?? null,
            'content' => Html::clean($data['content']) ?? '',
            'author' => $data['author'] ?? null,
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'focus_keyword' => $data['focus_keyword'] ?? null,
            'faqs' => collect($data['faqs'] ?? [])
                ->filter(fn ($f) => filled($f['q'] ?? null) && filled($f['a'] ?? null))
                ->map(fn ($f) => ['q' => trim($f['q']), 'a' => trim($f['a'])])
                ->values()->all() ?: null,
        ]);

        $at = filled($data['published_at'] ?? null) ? Carbon::parse($data['published_at']) : null;
        $post->published_at = match ($data['status']) {
            'draft' => null,
            'schedule' => $at,
            // Đăng ngay: giữ ngày đăng gốc nếu bài đã đăng trước đó (không làm "mới" giả tạo).
            'publish' => $at && $at->isPast() ? $at : ($post->published_at?->isPast() ? $post->published_at : now()),
        };

        if ($request->boolean('remove_image') || $request->hasFile('image')) {
            Upload::delete($post->image);
            $post->image = null;
        }
        if ($request->hasFile('image')) {
            $post->image = Upload::image($request->file('image'), self::DIR, $post->slug);
        }
    }
}
