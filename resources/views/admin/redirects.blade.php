@extends('admin.layout')

@section('title', 'Chuyển hướng 301')

@php($input = 'adm-input')

@section('content')
<h1 class="adm-h1">Chuyển hướng 301</h1>
<p class="mt-1 text-sm text-slate-600">Khi khách hoặc Google vào link cũ, website tự chuyển sang link mới và giữ thứ hạng tìm kiếm.
    Website <strong>tự tạo</strong> chuyển hướng khi bạn đổi đường dẫn hoặc xóa bài viết / sản phẩm / danh mục. Bạn chỉ cần thêm tay khi chuyển từ website cũ sang.</p>

<form method="post" action="{{ route('admin.redirects.store') }}" class="mt-4 grid gap-3 adm-card p-5 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
    @csrf
    <label class="block">
        <span class="mb-1 block text-sm font-medium">Link cũ</span>
        <input type="text" name="from_path" value="{{ old('from_path') }}" required placeholder="/san-pham/ten-cu" class="{{ $input }}">
    </label>
    <label class="block">
        <span class="mb-1 block text-sm font-medium">Link mới (trong website)</span>
        <input type="text" name="to_path" value="{{ old('to_path') }}" required placeholder="/san-pham/ten-moi" class="{{ $input }}">
    </label>
    <button class="adm-btn adm-btn-primary">Thêm</button>
</form>

<div class="mt-4 adm-card overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="adm-thead">
            <tr><th class="px-4 py-3">Link cũ</th><th class="px-4 py-3">→ Link mới</th><th class="px-4 py-3 text-right">Lượt</th><th class="px-4 py-3"></th></tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($redirects as $r)
                <tr>
                    <td class="px-4 py-2 break-all">{{ $r->from_path }}</td>
                    <td class="px-4 py-2 break-all"><a href="{{ $r->to_path }}" target="_blank" class="text-primary-600 hover:underline">{{ $r->to_path }}</a></td>
                    <td class="px-4 py-2 text-right text-slate-500" title="{{ $r->last_hit_at ? 'Lần cuối: '.$r->last_hit_at->format('d/m/Y H:i') : '' }}">{{ $r->hits }}</td>
                    <td class="px-4 py-2 text-right">
                        <form method="post" action="{{ route('admin.redirects.destroy', $r) }}" onsubmit="return confirm('Xóa chuyển hướng này? Link cũ sẽ báo lỗi 404.')">
                            @csrf <button class="text-xs text-primary-600 hover:underline">Xóa</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">Chưa có chuyển hướng nào.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $redirects->links() }}</div>
@endsection
