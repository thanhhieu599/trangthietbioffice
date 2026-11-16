@extends('layouts.app')

@section('title', $page->title.' | '.config('site.brand'))
@section('description', $page->meta_description ?: $page->title.' – '.config('site.company'))
{{-- Trang chưa có nội dung thì không cho Google lập chỉ mục --}}
@unless ($page->hasContent())
    @section('robots', 'noindex, follow')
@endunless

@section('content')
<div class="mx-auto max-w-4xl px-4 py-5">
    @include('partials.breadcrumb', ['items' => [['name' => $page->title, 'url' => $page->url()]]])

    <article class="rounded-md bg-white p-6 md:p-8">
        <h1 class="border-b-2 border-primary-600 pb-3 text-2xl font-bold uppercase text-ink-900">{{ $page->title }}</h1>
        @if ($page->hasContent())
            <div class="prose-content mt-6">{!! $page->content !!}</div>
        @else
            <p class="mt-6">Nội dung đang được cập nhật. Vui lòng liên hệ
                <a href="tel:{{ preg_replace('/[^0-9+]/', '', config('site.phone')) }}" class="font-semibold text-primary-600">{{ config('site.phone') }}</a>
                hoặc <a href="mailto:{{ config('site.email') }}" class="text-primary-600 underline">{{ config('site.email') }}</a> để được hỗ trợ.</p>
        @endif
    </article>
</div>
@endsection
