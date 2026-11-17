@extends('admin.layout')

@section('title', 'Bài viết')
@section('width', 'max-w-7xl')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3">
    <h1 class="adm-h1">Bài viết</h1>
    <a href="{{ route('admin.posts.create') }}" class="adm-btn adm-btn-primary">+ Viết bài mới</a>
</div>

@php($tabs = ['' => ['Tất cả', $counts['all']], 'published' => ['Đã đăng', $counts['published']], 'scheduled' => ['Hẹn giờ', $counts['scheduled']], 'draft' => ['Nháp', $counts['draft']]])
<div class="mt-4 flex flex-wrap items-center justify-between gap-3">
    <nav class="flex flex-wrap gap-1 rounded-xl bg-slate-200/60 p-1 text-sm">
        @foreach ($tabs as $key => [$label, $n])
            <a href="{{ route('admin.posts.index', array_filter(['status' => $key, 'q' => request('q')])) }}"
               class="adm-tab {{ request('status', '') === $key ? 'adm-tab-active' : '' }}">{{ $label }} ({{ $n }})</a>
        @endforeach
    </nav>
    <form class="flex gap-2">
        <input type="hidden" name="status" value="{{ request('status') }}">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Tìm theo tiêu đề…" class="w-56 adm-input w-auto py-1.5">
        <button class="adm-btn adm-btn-dark">Tìm</button>
    </form>
</div>

<div class="mt-4 adm-card overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="adm-thead">
            <tr>
                <th class="px-4 py-3">Bài viết</th>
                <th class="px-4 py-3">Trạng thái</th>
                <th class="px-4 py-3">SEO</th>
                <th class="px-4 py-3 whitespace-nowrap">Cập nhật</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($posts as $post)
                @php($issues = \App\Support\SeoAudit::post($post))
                <tr class="align-top transition hover:bg-slate-50/80">
                    <td class="px-4 py-3">
                        <div class="flex gap-3">
                            @if ($post->image)
                                <img src="{{ $post->image }}" alt="" class="h-12 w-20 shrink-0 rounded object-cover" loading="lazy">
                            @else
                                <div class="h-12 w-20 shrink-0 rounded bg-slate-100"></div>
                            @endif
                            <div class="min-w-0">
                                <a href="{{ route('admin.posts.edit', $post) }}" class="font-semibold text-adm-ink hover:text-primary-600">{{ $post->title }}</a>
                                <p class="truncate text-xs text-slate-500">/tin-tuc/{{ $post->slug }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        @if (! $post->published_at)
                            <span class="adm-badge adm-badge-gray">Nháp</span>
                        @elseif ($post->published_at->isFuture())
                            <span class="adm-badge adm-badge-blue">Hẹn {{ $post->published_at->format('d/m H:i') }}</span>
                        @else
                            <span class="adm-badge adm-badge-green">Đã đăng {{ $post->published_at->format('d/m/Y') }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if ($issues)
                            <details class="text-xs">
                                <summary class="cursor-pointer list-none"><span class="adm-badge adm-badge-amber">{{ count($issues) }} việc cần sửa ▾</span></summary>
                                <ul class="mt-1 list-disc pl-4 text-slate-600">@foreach ($issues as $i)<li>{{ $i }}</li>@endforeach</ul>
                            </details>
                        @else
                            <span class="adm-badge adm-badge-green">✓ Tốt</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-500">{{ $post->updated_at->format('d/m/Y H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">Chưa có bài viết nào.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $posts->links() }}</div>
@endsection
