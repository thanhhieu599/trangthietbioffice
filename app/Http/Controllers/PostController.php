<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\View\View;

class PostController extends Controller
{
    public function index(): View
    {
        return view('pages.posts.index', [
            'posts' => Post::published()->latest('published_at')->paginate(12),
        ]);
    }

    public function show(Post $post): View
    {
        abort_if($post->published_at === null || $post->published_at->isFuture(), 404);

        return view('pages.posts.show', [
            'post' => $post,
            'more' => Post::published()->whereKeyNot($post->id)->latest('published_at')->take(3)->get(),
        ]);
    }
}
