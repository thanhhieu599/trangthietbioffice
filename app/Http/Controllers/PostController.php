<?php

namespace App\Http\Controllers;

use App\Models\Post;
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
            'more' => Post::published()->whereKeyNot($post->id)->latest('published_at')->take(3)->get(),
        ]);
    }
}
