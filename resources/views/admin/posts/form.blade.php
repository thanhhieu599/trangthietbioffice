@extends('admin.layout')

@section('title', $post->exists ? 'Sửa bài viết' : 'Viết bài mới')
@section('width', 'max-w-7xl')

@php
    $input = 'adm-input';
    $status = old('status', match (true) {
        ! $post->published_at => $post->exists ? 'draft' : 'publish',
        $post->published_at->isFuture() => 'schedule',
        default => 'publish',
    });
    $faqs = old('faqs', $post->faqs ?: []);
    $config = [
        'type' => 'post',
        'brand' => config('site.brand'),
        'baseUrl' => url('/'),
        'prefix' => '/tin-tuc/',
        'uploadUrl' => route('admin.upload'),
        'exists' => $post->exists,
        'hasImage' => (bool) $post->image,
        'rows' => array_values(array_map(fn ($f) => ['q' => $f['q'] ?? '', 'a' => $f['a'] ?? ''], $faqs)),
        'f' => [
            'title' => old('title', $post->title),
            'slug' => old('slug', $post->slug),
            'summary' => old('excerpt', $post->excerpt),
            'content' => old('content', $post->content),
            'meta_title' => old('meta_title', $post->meta_title),
            'meta_description' => old('meta_description', $post->meta_description),
            'focus_keyword' => old('focus_keyword', $post->focus_keyword),
            'status' => $status,
        ],
    ];
@endphp

@section('content')
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <div>
        <a href="{{ route('admin.posts.index') }}" class="text-sm text-slate-500 hover:text-primary-600">← Danh sách bài viết</a>
        <h1 class="adm-h1">{{ $post->exists ? 'Sửa bài viết' : 'Viết bài mới' }}</h1>
    </div>
    @if ($post->exists && $post->published_at && $post->published_at->isPast())
        <a href="{{ $post->url() }}" target="_blank" class="text-sm text-primary-600 hover:underline">Xem bài trên website ↗</a>
    @endif
</div>

<form method="post" enctype="multipart/form-data"
      action="{{ $post->exists ? route('admin.posts.update', $post) : route('admin.posts.store') }}"
      x-data="adminForm(@js($config))" class="grid gap-5 lg:grid-cols-[1fr_380px]">
    @csrf

    {{-- ===== Cột chính ===== --}}
    <div class="min-w-0 space-y-4">
        <section class="space-y-4 adm-card p-5">
            <label class="block">
                <span class="mb-1 block text-sm font-medium">Tiêu đề bài viết (H1) *</span>
                <input type="text" name="title" x-model="f.title" required maxlength="255" class="{{ $input }} text-base font-semibold"
                       placeholder="VD: Cách chọn giấy in A4 phù hợp cho văn phòng">
            </label>
            @include('admin.partials.slug-field')
            <label class="block">
                <span class="mb-1 flex justify-between text-sm font-medium">Đoạn tóm tắt (sapo)
                    <span class="text-xs font-normal text-slate-500" x-text="(f.summary || '').length + '/500'"></span></span>
                <textarea name="excerpt" x-model="f.summary" rows="3" maxlength="500" class="{{ $input }}"
                          placeholder="2–3 câu tóm tắt, hiện ở danh sách tin tức và dùng làm mô tả nếu bỏ trống mô tả meta."></textarea>
            </label>
        </section>

        <section class="adm-card p-5">
            @include('admin.partials.editor', [
                'name' => 'content',
                'label' => 'Nội dung bài viết *',
                'hint' => 'Mẹo: chia bài bằng H2/H3, đoạn văn ngắn, chèn link tới danh mục/sản phẩm liên quan. Nhấp đúp vào ảnh để sửa mô tả (alt). Dán từ Word/Google Docs sẽ tự làm sạch định dạng.',
            ])
        </section>

        {{-- FAQ -> FAQPage schema (có thể hiện câu hỏi ngay trên Google) --}}
        <section class="adm-card p-5">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-adm-ink">Câu hỏi thường gặp (FAQ)</h2>
                    <p class="text-xs text-slate-500">Hiện cuối bài và gửi cho Google dạng dữ liệu có cấu trúc FAQPage.</p>
                </div>
                <button type="button" @click="rows.push({ q: '', a: '' })" class="adm-btn adm-btn-ghost">+ Thêm câu hỏi</button>
            </div>
            <div class="mt-3 space-y-3">
                <template x-for="(row, i) in rows" :key="i">
                    <div class="rounded border border-slate-200 p-3">
                        <div class="flex gap-2">
                            <input type="text" :name="`faqs[${i}][q]`" x-model="row.q" placeholder="Câu hỏi" class="{{ $input }} font-medium">
                            <button type="button" @click="rows.splice(i, 1)" class="shrink-0 px-2 text-primary-600" title="Xóa">✕</button>
                        </div>
                        <textarea :name="`faqs[${i}][a]`" x-model="row.a" rows="2" placeholder="Câu trả lời" class="{{ $input }} mt-2"></textarea>
                    </div>
                </template>
                <p x-show="!rows.length" class="text-sm text-slate-500">Chưa có câu hỏi nào.</p>
            </div>
        </section>
    </div>

    {{-- ===== Cột phải ===== --}}
    <aside class="space-y-4 lg:sticky lg:top-4 lg:self-start">
        <section class="adm-card p-5">
            <h2 class="font-semibold text-adm-ink">Đăng bài</h2>
            <div class="mt-3 space-y-2 text-sm">
                <label class="flex items-center gap-2"><input type="radio" name="status" value="draft" x-model="f.status" class="accent-primary-600"> Lưu nháp (chưa hiện trên web)</label>
                <label class="flex items-center gap-2"><input type="radio" name="status" value="publish" x-model="f.status" class="accent-primary-600"> Đăng ngay</label>
                <label class="flex items-center gap-2"><input type="radio" name="status" value="schedule" x-model="f.status" class="accent-primary-600"> Hẹn giờ đăng</label>
            </div>
            <label class="mt-3 block" x-show="f.status !== 'draft'" x-cloak>
                <span class="mb-1 block text-xs font-medium text-slate-600" x-text="f.status === 'schedule' ? 'Thời điểm đăng *' : 'Ngày đăng (để trống = bây giờ)'"></span>
                <input type="datetime-local" name="published_at" value="{{ old('published_at', $post->published_at?->format('Y-m-d\TH:i')) }}" class="{{ $input }}">
            </label>
            <label class="mt-3 block">
                <span class="mb-1 block text-xs font-medium text-slate-600">Tác giả (để trống = Admin)</span>
                <input type="text" name="author" value="{{ old('author', $post->author) }}" maxlength="100" class="{{ $input }}">
            </label>
            <div class="mt-4 flex gap-2">
                <button class="flex-1 adm-btn adm-btn-primary"
                        x-text="f.status === 'draft' ? 'Lưu nháp' : (f.status === 'schedule' ? 'Lưu & hẹn giờ' : '{{ $post->exists ? 'Cập nhật' : 'Đăng bài' }}')"></button>
            </div>
        </section>

        {{-- Ảnh bài viết: ô tải ảnh kiểu khung nét đứt --}}
        <section class="adm-card p-5" x-data="{ preview: @js($post->image), removed: false, drag: false,
                pick(file) { if (!file) return; const dt = new DataTransfer(); dt.items.add(file); $refs.file.files = dt.files;
                    preview = URL.createObjectURL(file); removed = false; hasImage = true; $refs.remove && ($refs.remove.value = ''); dirty = true; },
                clear() { $refs.file.value = ''; preview = null; removed = true; hasImage = false; $refs.remove && ($refs.remove.value = '1'); dirty = true; } }">
            <h2 class="font-semibold text-adm-ink">Ảnh bài viết</h2>
            <input type="file" name="image" x-ref="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="pick($event.target.files[0])">
            @if ($post->image)<input type="hidden" name="remove_image" x-ref="remove" value="">@endif

            <div class="mt-3 overflow-hidden rounded-lg border border-dashed transition"
                 :class="drag ? 'border-primary-600 bg-primary-50' : 'border-slate-300 bg-slate-50'"
                 @dragover.prevent="drag = true" @dragleave.prevent="drag = false" @drop.prevent="drag = false; pick($event.dataTransfer.files[0])">
                <template x-if="preview">
                    <div class="group relative">
                        <img :src="preview" alt="" class="aspect-[1200/630] w-full object-cover">
                        <div class="absolute inset-0 flex items-center justify-center gap-2 bg-slate-950/50 opacity-0 transition group-hover:opacity-100">
                            <button type="button" class="adm-btn bg-white text-adm-ink hover:bg-slate-100" @click="$refs.file.click()">Đổi ảnh</button>
                            <button type="button" class="adm-btn adm-btn-primary" @click="clear()">Xóa</button>
                        </div>
                    </div>
                </template>
                <template x-if="!preview">
                    <div class="flex flex-col items-center px-4 py-8 text-center">
                        <svg class="h-24 w-28 text-slate-300" viewBox="0 0 120 100" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" aria-hidden="true">
                            <rect x="8" y="22" width="62" height="62" rx="3" transform="rotate(-8 39 53)" class="fill-white"/>
                            <rect x="14" y="30" width="50" height="40" transform="rotate(-8 39 53)"/>
                            <rect x="42" y="12" width="66" height="70" rx="3" transform="rotate(6 75 47)" class="fill-white"/>
                            <rect x="49" y="19" width="52" height="46" transform="rotate(6 75 47)"/>
                            <path d="M56 58c6-9 11-12 16-6 4-7 9-9 15 2" transform="rotate(6 75 47)"/>
                            <circle cx="86" cy="32" r="5" transform="rotate(6 75 47)"/>
                        </svg>
                        <button type="button" class="adm-btn adm-btn-primary mt-4 px-5" @click="$refs.file.click()">Upload ảnh</button>
                        <p class="mt-2 text-xs text-slate-500">hoặc kéo thả ảnh vào đây</p>
                    </div>
                </template>
            </div>
            <p class="mt-2 text-xs text-slate-500">JPG, PNG, WebP · tối đa 3 MB · khuyến nghị 1200 × 630 px (hiện đẹp khi chia sẻ Facebook/Zalo).</p>
        </section>

        @include('admin.partials.seo-panel')
    </aside>
</form>

@if ($post->exists)
    <form method="post" action="{{ route('admin.posts.destroy', $post) }}" class="mt-6"
          onsubmit="return confirm('Xóa vĩnh viễn bài viết này?')">
        @csrf
        <button class="text-sm text-primary-600 hover:underline">Xóa bài viết</button>
    </form>
@endif
@endsection
