@extends('admin.layout')

@section('title', 'Tổng quan')
@section('width', 'max-w-7xl')

@section('content')
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="adm-h1">Tổng quan</h1>
        <p class="adm-sub">Tình hình nội dung website hôm nay, {{ now()->format('d/m/Y') }}.</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admin.categories.create') }}" class="adm-btn adm-btn-ghost">+ Danh mục</a>
        <a href="{{ route('admin.products.create') }}" class="adm-btn adm-btn-dark">+ Sản phẩm</a>
        <a href="{{ route('admin.posts.create') }}" class="adm-btn adm-btn-primary">+ Viết bài mới</a>
    </div>
</div>

@php
    $tiles = [
        ['Bài viết đã đăng', $stats['posts_published'], route('admin.posts.index', ['status' => 'published']), $stats['posts_draft'].' nháp · '.$stats['posts_scheduled'].' hẹn giờ', 'bg-sky-50 text-sky-600', '<path d="M6 3h8l4 4v14H6z"/><path d="M14 3v4h4M9 12h6M9 16h6"/>'],
        ['Sản phẩm đang bán', $stats['products_active'], route('admin.products.index', ['status' => 'active']), $stats['products_hidden'].' đang ẩn', 'bg-emerald-50 text-emerald-600', '<path d="m12 3 8 4.5v9L12 21l-8-4.5v-9z"/><path d="m4 7.5 8 4.5 8-4.5M12 12v9"/>'],
        ['Danh mục', $stats['categories'], route('admin.categories.index'), 'Gồm cả danh mục con', 'bg-violet-50 text-violet-600', '<rect x="4" y="4" width="6.5" height="6.5" rx="1.5"/><rect x="13.5" y="4" width="6.5" height="6.5" rx="1.5"/><rect x="4" y="13.5" width="6.5" height="6.5" rx="1.5"/><rect x="13.5" y="13.5" width="6.5" height="6.5" rx="1.5"/>'],
        ['Yêu cầu báo giá mới', $stats['quotes_new'], null, 'Chưa xử lý', 'bg-primary-50 text-primary-600', '<rect x="6" y="4" width="12" height="17" rx="2"/><path d="M9 4h6v3H9zM9 12h6M9 16h4"/>'],
    ];
@endphp
<div class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
    @foreach ($tiles as [$label, $value, $link, $sub, $tone, $icon])
        <a @if ($link) href="{{ $link }}" @endif class="adm-card group block p-5 transition {{ $link ? 'hover:-translate-y-0.5 hover:shadow-lg hover:shadow-slate-900/5' : '' }}">
            <div class="flex items-start justify-between gap-3">
                <p class="text-sm font-medium text-adm-muted">{{ $label }}</p>
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $tone }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">{!! $icon !!}</svg>
                </span>
            </div>
            <p class="mt-2 text-3xl font-semibold tracking-tight text-adm-ink">{{ number_format($value, 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-adm-muted">{{ $sub }}</p>
        </a>
    @endforeach
</div>

<div class="mt-4 flex flex-wrap items-center gap-2 text-sm">
    <span class="mr-1 text-adm-muted">Công cụ SEO:</span>
    <a href="{{ route('sitemap') }}" target="_blank" class="adm-btn adm-btn-ghost py-1.5">Sitemap.xml ↗</a>
    <a href="{{ route('admin.redirects.index') }}" class="adm-btn adm-btn-ghost py-1.5">Chuyển hướng 301</a>
    <a href="{{ route('admin.banners.index') }}" class="adm-btn adm-btn-ghost py-1.5">Banner trang chủ</a>
</div>

<div class="mt-6 grid gap-5 lg:grid-cols-[1fr_360px]">
    {{-- Việc cần làm SEO --}}
    <section class="adm-card p-5">
        <h2 class="font-semibold text-adm-ink">Việc cần làm để tối ưu SEO</h2>
        <p class="text-xs text-slate-500">Các nội dung đang hiển thị còn thiếu sót, nhiều lỗi nhất xếp trước. Bấm vào để sửa.</p>

        @foreach ([['Bài viết', $postTodo], ['Sản phẩm', $productTodo], ['Danh mục', $categoryTodo]] as [$label, $list])
            <div x-data="{ all: false }" class="mt-4">
                <h3 class="text-sm font-semibold">{{ $label }} <span class="font-normal text-slate-500">({{ $list->count() }} cần sửa)</span></h3>
                @if ($list->isEmpty())
                    <p class="mt-1 text-sm text-green-700">✓ Không có lỗi cơ bản.</p>
                @else
                    <ul class="mt-2 divide-y divide-slate-100 text-sm">
                        @foreach ($list->values() as $i => $row)
                            <li class="py-2" @if ($i >= 5) x-show="all" x-cloak @endif>
                                <a href="{{ $row['route'] }}" class="font-medium text-adm-ink hover:text-primary-600">{{ $row['name'] }}</a>
                                <p class="text-xs text-slate-500">{{ implode(' · ', $row['issues']) }}</p>
                            </li>
                        @endforeach
                    </ul>
                    @if ($list->count() > 5)
                        <button type="button" @click="all = !all" class="text-xs text-primary-600 hover:underline" x-text="all ? 'Thu gọn' : 'Xem tất cả {{ $list->count() }}'"></button>
                    @endif
                @endif
            </div>
        @endforeach
    </section>

    <aside class="space-y-5">
        <section class="adm-card p-5">
            <h2 class="font-semibold text-adm-ink">Bài viết sửa gần đây</h2>
            <ul class="mt-2 space-y-2 text-sm">
                @forelse ($recentPosts as $p)
                    <li><a href="{{ route('admin.posts.edit', $p) }}" class="hover:text-primary-600">{{ $p->title }}</a>
                        <span class="block text-xs text-slate-500">{{ $p->updated_at->diffForHumans() }}{{ $p->published_at ? '' : ' · nháp' }}</span></li>
                @empty
                    <li class="text-slate-500">Chưa có bài viết.</li>
                @endforelse
            </ul>
        </section>

        <section class="adm-card p-5">
            <h2 class="font-semibold text-adm-ink">Yêu cầu báo giá gần đây</h2>
            <ul class="mt-2 space-y-2 text-sm">
                @forelse ($recentQuotes as $q)
                    <li>
                        <span class="font-medium">{{ $q->company }}</span>
                        <span class="block text-xs text-slate-500">{{ $q->contact_name }} · {{ $q->phone }} · {{ $q->created_at->format('d/m H:i') }}</span>
                    </li>
                @empty
                    <li class="text-slate-500">Chưa có yêu cầu nào.</li>
                @endforelse
            </ul>
        </section>

        <section class="adm-card p-5 text-sm">
            <h2 class="font-semibold text-adm-ink">Quy tắc SEO website tự áp dụng</h2>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-slate-600">
                <li>Đổi đường dẫn hoặc xóa nội dung → tự tạo chuyển hướng 301.</li>
                <li>Sitemap.xml cập nhật ngay sau mỗi lần lưu.</li>
                <li>Bài nháp / hẹn giờ không hiện trên web và sitemap.</li>
                <li>Dữ liệu có cấu trúc: Article, FAQPage, Product, Breadcrumb.</li>
                <li>Tên file ảnh tải lên đặt theo tên nội dung.</li>
            </ul>
        </section>
    </aside>
</div>
@endsection
