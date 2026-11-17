@extends('layouts.app')

@section('title', ($post->meta_title ?: $post->title).' | '.config('site.brand'))
@section('description', $post->meta_description ?: $post->excerpt)
@section('og_type', 'article')
@if ($post->image)
    @section('og_image', \App\Support\JsonLd::absolute($post->image))
@endif

@push('schema')
{!! \App\Support\JsonLd::script(\App\Support\JsonLd::article($post)) !!}
@if (! empty($post->faqs))
{!! \App\Support\JsonLd::script(\App\Support\JsonLd::faq($post->faqs)) !!}
@endif
@endpush

@php
    $shareUrl = urlencode($post->url());
    $field = 'w-full rounded-lg border border-neutral-300 bg-white px-3 py-2.5 text-[15px] focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15';
    $img = fn ($p) => $p->image ? \App\Support\JsonLd::absolute($p->image) : null;
@endphp

@section('content')
<div class="mx-auto max-w-site px-4 py-5">
    @include('partials.breadcrumb', ['items' => [
        ['name' => 'Tin tức', 'url' => route('posts.index')],
        ['name' => $post->title, 'url' => $post->url()],
    ]])

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px] lg:items-start">
        {{-- ================= BÀI VIẾT ================= --}}
        <article class="min-w-0 rounded-xl bg-white px-5 py-6 shadow-sm sm:px-8 sm:py-8 lg:px-10">
            <header class="border-b border-neutral-200 pb-5">
                <a href="{{ route('posts.index') }}" class="inline-block rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold text-primary-600 hover:bg-primary-100">Tin tức</a>
                <h1 class="mt-3 text-2xl leading-tight font-bold text-ink-900 md:text-[32px]">{{ $post->title }}</h1>
                <ul class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-neutral-500">
                    <li class="flex items-center gap-1.5"><x-icon name="user" class="h-4 w-4 text-primary-600" />{{ $post->authorName() }}</li>
                    <li class="flex items-center gap-1.5"><x-icon name="calendar" class="h-4 w-4 text-primary-600" />
                        <time datetime="{{ $post->published_at->toDateString() }}">{{ $post->published_at->format('d/m/Y') }}</time></li>
                    <li class="flex items-center gap-1.5"><x-icon name="clock" class="h-4 w-4 text-primary-600" />{{ $post->readingMinutes() }} phút đọc</li>
                </ul>
            </header>

            @if ($post->excerpt)
                <p class="mt-6 text-[17px] leading-relaxed font-medium text-ink-800">{{ $post->excerpt }}</p>
            @endif

            @if ($post->image)
                <img src="{{ $img($post) }}" alt="{{ $post->title }}" width="1200" height="630" fetchpriority="high" class="mt-6 aspect-[1200/630] w-full rounded-xl object-cover">
            @endif

            {{-- Mục lục (tự tạo từ các tiêu đề H2/H3) --}}
            @if (count($article['toc']) >= 2)
                <nav class="mt-7 rounded-lg border border-neutral-200 border-l-4 border-l-primary-600 bg-neutral-50 px-5 py-4" aria-label="Nội dung bài viết" x-data="{ open: true }">
                    <button type="button" @click="open = !open" class="flex w-full items-center justify-between text-left font-semibold text-ink-900">
                        Nội dung bài viết
                        <x-icon name="chevron-down" class="h-4 w-4 text-neutral-500 transition" ::class="!open && '-rotate-90'" />
                    </button>
                    <ol class="mt-3 space-y-1.5 text-[15px]" x-show="open" x-transition.opacity>
                        @php($n = 0)
                        @foreach ($article['toc'] as $t)
                            @php($t['level'] === 2 && $n++)
                            <li class="{{ $t['level'] === 3 ? 'pl-6 text-sm' : '' }}">
                                <a href="#{{ $t['id'] }}" class="flex gap-2 text-neutral-700 hover:text-primary-600">
                                    @if ($t['level'] === 2)<span class="w-5 shrink-0 text-right font-semibold text-primary-600">{{ $n }}.</span>@else<span class="text-neutral-400">–</span>@endif
                                    <span>{{ $t['text'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ol>
                </nav>
            @endif

            <div class="article-content mt-7">{!! $article['html'] !!}</div>

            {{-- Câu hỏi thường gặp --}}
            @if (! empty($post->faqs))
                <section class="mt-10" aria-labelledby="faq">
                    <h2 id="faq" class="border-l-4 border-primary-600 pl-3 text-xl font-bold text-ink-900">Câu hỏi thường gặp</h2>
                    <div class="mt-4 divide-y divide-neutral-200 rounded-lg border border-neutral-200">
                        @foreach ($post->faqs as $faq)
                            <details class="group px-5 py-4 [&_summary::-webkit-details-marker]:hidden">
                                <summary class="flex cursor-pointer items-center justify-between gap-4 font-semibold text-ink-900">
                                    {{ $faq['q'] }}
                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary-50 text-primary-600 transition group-open:rotate-45">+</span>
                                </summary>
                                <p class="mt-3 leading-relaxed text-ink-700">{{ $faq['a'] }}</p>
                            </details>
                        @endforeach
                    </div>
                </section>
            @endif

            <p class="mt-10 text-sm font-semibold text-ink-800 italic">{{ config('site.brand') }}</p>

            {{-- Chia sẻ --}}
            <div class="mt-4 flex flex-wrap items-center gap-2 border-y border-neutral-200 py-4 text-sm" x-data="{ copied: false }">
                <span class="mr-1 font-medium text-neutral-600">Chia sẻ bài viết:</span>
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}" target="_blank" rel="noopener nofollow" aria-label="Chia sẻ lên Facebook"
                   class="flex h-8 w-8 items-center justify-center rounded-full bg-[#1877F2] text-white hover:opacity-90">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M14 8h3V4h-3c-2.8 0-4.5 1.8-4.5 4.6V11H7v4h2.5v9h4v-9H17l.5-4h-4V8.8c0-.5.3-.8.8-.8z"/></svg>
                </a>
                <a href="https://twitter.com/intent/tweet?url={{ $shareUrl }}&text={{ urlencode($post->title) }}" target="_blank" rel="noopener nofollow" aria-label="Chia sẻ lên X (Twitter)"
                   class="flex h-8 w-8 items-center justify-center rounded-full bg-[#1DA1F2] text-white hover:opacity-90">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M22 5.9c-.7.3-1.5.5-2.4.6.9-.5 1.5-1.3 1.8-2.3-.8.5-1.7.8-2.6 1a4.1 4.1 0 0 0-7 3.7A11.6 11.6 0 0 1 3.4 4.6a4.1 4.1 0 0 0 1.3 5.5c-.7 0-1.3-.2-1.9-.5 0 2 1.4 3.7 3.3 4.1-.6.2-1.2.2-1.9.1.5 1.6 2 2.8 3.8 2.9A8.3 8.3 0 0 1 2 18.4 11.7 11.7 0 0 0 8.3 20c7.5 0 11.7-6.3 11.7-11.7v-.5c.8-.6 1.5-1.3 2-2z"/></svg>
                </a>
                <button type="button" aria-label="Sao chép liên kết" @click="navigator.clipboard?.writeText(@js($post->url())).then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                        class="flex h-8 w-8 items-center justify-center rounded-full bg-neutral-400 text-white hover:bg-neutral-500">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/></svg>
                </button>
                <span x-show="copied" x-cloak class="text-xs text-green-700">Đã sao chép liên kết</span>
            </div>

            {{-- Hộp tư vấn --}}
            <div class="mt-6 flex flex-wrap items-center justify-between gap-4 rounded-xl bg-[linear-gradient(110deg,#141414_0%,#2b0c0e_60%,#5a0e12_100%)] px-6 py-5 text-white">
                <div class="min-w-0">
                    <p class="text-lg font-bold">Cần tư vấn văn phòng phẩm phù hợp?</p>
                    <p class="mt-0.5 text-sm text-white/75">Gửi danh mục cần mua, nhận báo giá trong 4 giờ làm việc kèm phương án tối ưu chi phí.</p>
                </div>
                <div class="flex shrink-0 flex-wrap gap-2">
                    <a href="{{ route('quote.create') }}" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold hover:bg-primary-700">Gửi yêu cầu báo giá</a>
                    <a href="{{ route('home') }}" class="rounded-lg border border-white/40 px-4 py-2 text-sm font-semibold hover:bg-white/10">Xem sản phẩm</a>
                </div>
            </div>

            {{-- Bài viết liên quan --}}
            @if ($related->isNotEmpty())
                <section class="mt-10" aria-labelledby="lien-quan">
                    <h2 id="lien-quan" class="border-l-4 border-primary-600 pl-3 text-lg font-bold text-ink-900">Bài viết liên quan</h2>
                    <div class="mt-4 grid gap-4 sm:grid-cols-3">
                        @foreach ($related as $r)
                            <a href="{{ $r->url() }}" class="group overflow-hidden rounded-xl border border-neutral-200 bg-white transition hover:-translate-y-0.5 hover:shadow-md">
                                <div class="aspect-[16/10] overflow-hidden bg-neutral-100">
                                    @if ($src = $img($r))
                                        <img src="{{ $src }}" alt="{{ $r->title }}" loading="lazy" width="400" height="250" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                    @else
                                        <span class="flex h-full items-center justify-center text-neutral-300"><x-icon name="image" class="h-10 w-10" /></span>
                                    @endif
                                </div>
                                <div class="p-3.5">
                                    <p class="text-xs text-neutral-500">{{ $r->published_at->format('d/m/Y') }}</p>
                                    <p class="mt-1 line-clamp-2 text-sm leading-snug font-semibold text-ink-900 group-hover:text-primary-600">{{ $r->title }}</p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Bình luận --}}
            <section id="binh-luan" class="mt-10 scroll-mt-24 rounded-xl border border-neutral-200 bg-neutral-50 p-5 sm:p-6" aria-labelledby="bl-title">
                <h2 id="bl-title" class="border-l-4 border-primary-600 pl-3 text-lg font-bold text-ink-900">
                    {{ $comments->isNotEmpty() ? 'Bình luận ('.$comments->count().')' : 'Viết bình luận của bạn' }}
                </h2>

                @if ($comments->isNotEmpty())
                    <ul class="mt-4 space-y-4">
                        @foreach ($comments as $c)
                            <li class="flex gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary-600 text-sm font-bold text-white">{{ mb_strtoupper(mb_substr($c->name, 0, 1)) }}</span>
                                <div class="min-w-0 flex-1 rounded-lg bg-white px-4 py-3 ring-1 ring-neutral-200">
                                    <p class="text-sm"><span class="font-semibold text-ink-900">{{ $c->name }}</span> <span class="text-neutral-400">· {{ $c->created_at->format('d/m/Y') }}</span></p>
                                    <p class="mt-1 text-[15px] leading-relaxed whitespace-pre-line text-ink-700">{{ $c->content }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if (session('comment_ok'))
                    <p class="mt-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800" role="status">Cảm ơn anh/chị! Bình luận sẽ hiển thị sau khi được duyệt.</p>
                @endif
                @if ($errors->any())
                    <p class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">{{ $errors->first() }}</p>
                @endif

                <form method="post" action="{{ route('posts.comment', $post) }}" class="mt-4 grid gap-3 sm:grid-cols-2">
                    @csrf
                    <label class="block"><span class="sr-only">Họ tên</span>
                        <input type="text" name="name" value="{{ old('name', auth()->user()?->name) }}" required maxlength="100" placeholder="Họ tên *" autocomplete="name" class="{{ $field }}"></label>
                    <label class="block"><span class="sr-only">Email</span>
                        <input type="email" name="email" value="{{ old('email', auth()->user()?->email) }}" required maxlength="255" placeholder="Email * (không hiển thị)" autocomplete="email" class="{{ $field }}"></label>
                    <label class="block sm:col-span-2"><span class="sr-only">Nội dung bình luận</span>
                        <textarea name="content" rows="4" required minlength="5" maxlength="2000" placeholder="Nội dung bình luận *" class="{{ $field }}">{{ old('content') }}</textarea></label>
                    <div class="hidden" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
                    <div class="flex flex-wrap items-center gap-3 sm:col-span-2">
                        <button class="rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-primary-700">Gửi bình luận</button>
                        <span class="text-xs text-neutral-500">Bình luận được duyệt trước khi hiển thị.</span>
                    </div>
                </form>
            </section>

            <a href="{{ route('posts.index') }}" class="mt-8 inline-flex items-center gap-2 text-sm font-semibold text-primary-600 hover:underline">← Quay lại Tin tức</a>
        </article>

        {{-- ================= CỘT PHỤ ================= --}}
        <aside class="space-y-5 lg:sticky lg:top-4">
            @if ($latest->isNotEmpty())
                <section class="rounded-xl bg-white p-5 shadow-sm" aria-labelledby="moi-nhat">
                    <h2 id="moi-nhat" class="border-l-4 border-primary-600 pl-3 font-bold text-ink-900">Bài viết mới nhất</h2>
                    <ul class="mt-4 divide-y divide-neutral-100">
                        @foreach ($latest as $l)
                            <li class="py-3 first:pt-0 last:pb-0">
                                <a href="{{ $l->url() }}" class="group flex gap-3">
                                    <span class="h-16 w-20 shrink-0 overflow-hidden rounded-lg bg-neutral-100">
                                        @if ($src = $img($l))
                                            <img src="{{ $src }}" alt="" loading="lazy" width="80" height="64" class="h-full w-full object-cover">
                                        @else
                                            <span class="flex h-full items-center justify-center text-neutral-300"><x-icon name="image" class="h-6 w-6" /></span>
                                        @endif
                                    </span>
                                    <span class="min-w-0">
                                        <span class="line-clamp-2 text-sm leading-snug font-semibold text-ink-800 group-hover:text-primary-600">{{ $l->title }}</span>
                                        <span class="mt-1 flex items-center gap-1 text-xs text-neutral-500"><x-icon name="calendar" class="h-3.5 w-3.5 text-primary-600" />{{ $l->published_at->format('d/m/Y') }}</span>
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <section class="rounded-xl bg-white p-5 shadow-sm" aria-labelledby="dm-sb">
                <h2 id="dm-sb" class="border-l-4 border-primary-600 pl-3 font-bold text-ink-900">Danh mục</h2>
                <ul class="mt-3 space-y-0.5 text-sm">
                    <li><a href="{{ route('posts.index') }}" class="flex items-center justify-between rounded-md px-2 py-1.5 font-medium text-primary-600 hover:bg-primary-50">Tin tức &amp; kiến thức <span class="rounded-full bg-primary-50 px-2 text-xs">{{ $postCount }}</span></a></li>
                    @foreach ($categories as $cat)
                        <li><a href="{{ $cat->url() }}" class="block rounded-md px-2 py-1.5 text-ink-700 hover:bg-neutral-100 hover:text-primary-600">{{ $cat->name }}</a></li>
                    @endforeach
                </ul>
            </section>
        </aside>
    </div>
</div>
@endsection
