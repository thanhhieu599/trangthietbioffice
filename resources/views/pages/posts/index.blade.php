@extends('layouts.app')

@section('title', 'Tin tức & kiến thức văn phòng phẩm cho doanh nghiệp'.($posts->currentPage() > 1 ? ' – Trang '.$posts->currentPage() : '').' | '.config('site.brand'))
{{-- Trang 2, 3... canonical về chính nó (không gộp về trang 1) --}}
@section('canonical', route('posts.index').($posts->currentPage() > 1 ? '?page='.$posts->currentPage() : ''))
@section('description', 'Tin tức, hướng dẫn chọn, định mức và kiểm soát chi phí văn phòng phẩm, vật tư tiêu hao cho văn phòng và nhà máy.')

@section('content')
<div class="mx-auto max-w-site px-4 py-5">
    @include('partials.breadcrumb', ['items' => [['name' => 'Tin tức', 'url' => route('posts.index')]]])

    <div class="mb-5 border-b-2 border-primary-600">
        <h1 class="inline-block bg-primary-600 px-4 py-2 text-lg font-bold uppercase text-white">Tin tức &amp; kiến thức</h1>
    </div>

    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
        @forelse ($posts as $post)
            <article class="overflow-hidden rounded-md bg-white">
                <a href="{{ $post->url() }}" class="block aspect-[16/10] bg-neutral-200" aria-label="{{ $post->title }}" tabindex="-1">
                    @if ($post->image)
                        <img src="{{ \App\Support\JsonLd::absolute($post->image) }}" alt="{{ $post->title }}" loading="lazy" decoding="async" width="400" height="250" class="h-full w-full object-cover">
                    @else
                        <span class="flex h-full items-center justify-center text-neutral-400"><x-icon name="image" class="h-10 w-10" /></span>
                    @endif
                </a>
                <div class="p-4">
                    <h2 class="line-clamp-2 font-semibold leading-snug text-ink-900"><a href="{{ $post->url() }}" class="hover:text-primary-600">{{ $post->title }}</a></h2>
                    <p class="mt-1 text-xs text-neutral-500">{{ $post->published_at->format('d/m/Y') }}</p>
                    <p class="mt-2 line-clamp-3 text-sm">{{ $post->excerpt }}</p>
                </div>
            </article>
        @empty
            <p>Bài viết đang được cập nhật.</p>
        @endforelse
    </div>
    @if ($posts->hasPages())
        <div class="mt-8">{{ $posts->links() }}</div>
    @endif
</div>
@endsection
