@extends('admin.layout')

@section('title', 'Danh mục sản phẩm')
@section('width', 'max-w-7xl')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3">
    <h1 class="adm-h1">Danh mục sản phẩm <span class="text-base font-normal text-slate-500">({{ $rows->count() }})</span></h1>
    <a href="{{ route('admin.categories.create') }}" class="adm-btn adm-btn-primary">+ Thêm danh mục</a>
</div>

<div class="mt-4 adm-card overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="adm-thead">
            <tr>
                <th class="px-4 py-3">Danh mục</th>
                <th class="px-4 py-3 text-right">Sản phẩm</th>
                <th class="px-4 py-3 text-center">Thứ tự</th>
                <th class="px-4 py-3">Hiển thị</th>
                <th class="px-4 py-3">SEO</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($rows as ['category' => $c, 'depth' => $depth])
                @php($issues = \App\Support\SeoAudit::category($c))
                <tr class="align-top transition hover:bg-slate-50/80 {{ $c->is_active ? '' : 'opacity-60' }}">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3" style="padding-left: {{ $depth * 24 }}px">
                            @if ($depth)<span class="text-slate-400">└</span>@endif
                            @if ($img = $c->imageUrl())
                                <img src="{{ $img }}" alt="" class="h-8 w-8 shrink-0 object-contain" loading="lazy">
                            @endif
                            <div>
                                <a href="{{ route('admin.categories.edit', $c) }}" class="font-semibold text-adm-ink hover:text-primary-600">{{ $c->name }}</a>
                                <p class="text-xs text-slate-500">/danh-muc/{{ $c->slug }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('admin.products.index', ['category' => $c->id]) }}" class="hover:text-primary-600">{{ $c->products_count }}</a>
                    </td>
                    <td class="px-4 py-3 text-center">{{ $c->sort_order }}</td>
                    <td class="px-4 py-3 whitespace-nowrap text-xs">
                        <span class="adm-badge {{ $c->is_active ? 'adm-badge-green' : 'adm-badge-gray' }}">{{ $c->is_active ? 'Hiện' : 'Ẩn' }}</span>@if ($c->show_on_home) <span class="adm-badge adm-badge-blue">Trang chủ</span>@endif
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
                    <td class="px-4 py-3 whitespace-nowrap text-right text-xs">
                        <a href="{{ route('admin.categories.create', ['parent' => $c->id]) }}" class="text-slate-500 hover:text-primary-600">+ Danh mục con</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Chưa có danh mục nào.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
