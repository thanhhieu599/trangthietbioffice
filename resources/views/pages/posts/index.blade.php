@extends('layouts.app')

@section('title', 'Kiến thức mua sắm văn phòng phẩm cho doanh nghiệp | '.config('site.brand'))
@section('description', 'Hướng dẫn chọn, định mức và kiểm soát chi phí văn phòng phẩm, vật tư tiêu hao cho văn phòng và nhà máy.')

@section('content')
<div class="mx-auto max-w-5xl px-4 py-8">
    @include('partials.breadcrumb', ['items' => [['name' => 'Kiến thức', 'url' => route('posts.index')]]])
    <h1 class="text-2xl font-bold text-slate-900 md:text-3xl">Kiến thức mua sắm VPP cho doanh nghiệp</h1>
    <div class="mt-6 grid gap-4 md:grid-cols-2">
        @forelse ($posts as $post)
            <a href="{{ $post->url() }}" class="rounded-xl border border-slate-200 bg-white p-5 hover:shadow-sm">
                <h2 class="font-semibold text-slate-900">{{ $post->title }}</h2>
                <p class="mt-1 text-xs text-slate-500">{{ $post->published_at->format('d/m/Y') }}</p>
                <p class="mt-2 line-clamp-3 text-sm">{{ $post->excerpt }}</p>
            </a>
        @empty
            <p>Bài viết đang được cập nhật.</p>
        @endforelse
    </div>
    <div class="mt-8">{{ $posts->links() }}</div>
</div>
@endsection
