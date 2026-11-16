@extends('layouts.app')

@section('title', ($post->meta_title ?: $post->title).' | '.config('site.brand'))
@section('description', $post->meta_description ?: $post->excerpt)
@section('og_type', 'article')

@push('schema')
{!! \App\Support\JsonLd::script(\App\Support\JsonLd::article($post)) !!}
@if (! empty($post->faqs))
{!! \App\Support\JsonLd::script(\App\Support\JsonLd::faq($post->faqs)) !!}
@endif
@endpush

@section('content')
<div class="mx-auto max-w-3xl px-4 py-8">
    @include('partials.breadcrumb', ['items' => [
        ['name' => 'Kiến thức', 'url' => route('posts.index')],
        ['name' => $post->title, 'url' => $post->url()],
    ]])

    <article class="rounded-xl bg-white p-6 md:p-8">
        <h1 class="text-2xl font-bold leading-tight text-slate-900 md:text-3xl">{{ $post->title }}</h1>
        <p class="mt-2 text-sm text-slate-500">
            Cập nhật <time datetime="{{ $post->updated_at->toDateString() }}">{{ $post->updated_at->format('d/m/Y') }}</time> · {{ config('site.brand') }}
        </p>
        <div class="prose-content mt-6">{!! $post->content !!}</div>

        @if (! empty($post->faqs))
            <section class="mt-10">
                <h2 class="mb-4 text-xl font-semibold text-slate-900">Câu hỏi thường gặp</h2>
                <div class="space-y-2">
                    @foreach ($post->faqs as $faq)
                        <details class="rounded-lg border border-slate-200 p-4">
                            <summary class="cursor-pointer font-medium text-slate-900">{{ $faq['q'] }}</summary>
                            <p class="mt-2">{{ $faq['a'] }}</p>
                        </details>
                    @endforeach
                </div>
            </section>
        @endif

        <div class="mt-10 rounded-xl bg-brand-50 p-6">
            <p class="font-semibold text-slate-900">Cần báo giá VPP cho văn phòng / nhà máy?</p>
            <p class="mt-1 text-sm">Gửi danh mục đang dùng, nhận báo giá trong 4 giờ làm việc kèm phương án tối ưu chi phí.</p>
            <a href="{{ route('quote.create') }}" class="mt-3 inline-block rounded-lg bg-accent-500 px-5 py-2.5 font-semibold text-slate-900">Gửi yêu cầu báo giá</a>
        </div>
    </article>

    @if ($more->isNotEmpty())
        <section class="mt-10">
            <h2 class="mb-4 text-lg font-semibold text-slate-900">Bài viết liên quan</h2>
            <ul class="space-y-2">
                @foreach ($more as $m)
                    <li><a href="{{ $m->url() }}" class="text-brand-600 hover:underline">{{ $m->title }}</a></li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
@endsection
