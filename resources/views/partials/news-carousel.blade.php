{{-- Tin tức mới: băng chuyền 4 bài / màn hình (máy tính), 2 bài (máy tính bảng), 1 bài (điện thoại) --}}
@if ($posts->isNotEmpty())
<section class="rounded-md bg-white px-4 pt-4 pb-5 md:px-5" aria-labelledby="home-news" x-data="carousel">
    <div class="mb-4 flex items-center justify-between">
        <h2 id="home-news" class="text-lg font-bold text-ink-900">Tin tức mới</h2>
        <a href="{{ route('posts.index') }}" class="flex items-center gap-1 text-sm font-semibold text-ink-900 hover:text-primary-600">
            Xem tất cả <x-icon name="chevron-right" class="h-4 w-4" />
        </a>
    </div>

    <div class="relative">
        <div x-ref="track" @scroll.debounce.50ms="onScroll()"
             class="no-scrollbar flex snap-x snap-mandatory gap-5 overflow-x-auto scroll-smooth">
            @foreach ($posts as $post)
                <article class="w-[85%] shrink-0 snap-start sm:w-[calc((100%-1.25rem)/2)] lg:w-[calc((100%-3.75rem)/4)]">
                    <a href="{{ $post->url() }}" class="block aspect-[16/9] overflow-hidden bg-neutral-100" aria-label="{{ $post->title }}" tabindex="-1">
                        @if ($post->image)
                            <img src="{{ \App\Support\JsonLd::absolute($post->image) }}" alt="{{ $post->title }}" loading="lazy"
                                 width="400" height="225" class="h-full w-full object-cover transition duration-300 hover:scale-105">
                        @else
                            <span class="flex h-full items-center justify-center text-neutral-300"><x-icon name="image" class="h-12 w-12" /></span>
                        @endif
                    </a>
                    <h3 class="mt-4 line-clamp-3 text-[17px] font-semibold leading-snug text-ink-900">
                        <a href="{{ $post->url() }}" class="hover:text-primary-600">{{ $post->title }}</a>
                    </h3>
                    <p class="mt-2 text-[13px] text-neutral-500">
                        Đăng bởi <span class="font-semibold text-neutral-600">{{ $post->authorName() }}</span>
                        – <time datetime="{{ $post->published_at->toDateString() }}">{{ $post->published_at->format('d/m/Y') }}</time>
                    </p>
                    <p class="mt-2 line-clamp-3 text-[14px] leading-relaxed text-ink-700">{{ $post->summary() }}</p>
                </article>
            @endforeach
        </div>

        {{-- Nút trái / phải nằm ngang giữa ảnh --}}
        <template x-if="pages > 1">
            <div>
                <button type="button" @click="go(page - 1)" aria-label="Tin trước" :style="{ top: arrowTop + 'px' }"
                        class="absolute left-0 z-10 -translate-x-1/3 -translate-y-1/2 rounded border border-neutral-200 bg-white p-2 text-ink-800 shadow-sm hover:text-primary-600">
                    <x-icon name="chevron-left" class="h-4 w-4" />
                </button>
                <button type="button" @click="go(page + 1)" aria-label="Tin sau" :style="{ top: arrowTop + 'px' }"
                        class="absolute right-0 z-10 translate-x-1/3 -translate-y-1/2 rounded border border-neutral-200 bg-white p-2 text-ink-800 shadow-sm hover:text-primary-600">
                    <x-icon name="chevron-right" class="h-4 w-4" />
                </button>
            </div>
        </template>
    </div>

    <div class="mt-5 flex justify-center gap-2" x-show="pages > 1" x-cloak>
        <template x-for="p in pages" :key="p">
            <button type="button" @click="go(p - 1)" :aria-label="'Trang tin ' + p"
                    class="h-2.5 w-2.5 rounded-full border border-ink-800 transition"
                    :class="page === p - 1 ? 'bg-ink-800' : 'bg-white'"></button>
        </template>
    </div>
</section>
@endif
