@extends('admin.layout')

@section('title', 'Hình ảnh – Liên hệ')
@section('width', 'max-w-6xl')

@php($positions = \App\Models\Banner::CONTACT_POSITIONS)

@section('content')
@include('admin.media.tabs')

@foreach ($positions as $key => $pinfo)
    @php($list = $images[$key] ?? collect())
    <section class="adm-card mt-8 p-5" id="anh-{{ $key }}" x-data="{ preview: null }">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h2 class="font-semibold text-adm-ink">{{ $pinfo['label'] }} <span class="text-sm font-normal text-adm-muted">({{ $list->count() }}/{{ $pinfo['max'] }} ảnh)</span></h2>
            <span class="text-xs text-adm-muted">Khuyến nghị {{ $pinfo['size'] }} px</span>
        </div>

        @if ($list->isNotEmpty())
            <ul class="mt-4 grid gap-4 {{ $key === 'contact_hero' ? '' : 'md:grid-cols-3' }}">
                @foreach ($list as $img)
                    <li class="rounded-xl border border-adm-line p-3 {{ $img->is_active ? '' : 'opacity-60' }}">
                        <div class="overflow-hidden rounded-lg bg-slate-100" @style(['aspect-ratio: '.$pinfo['ratio']])>
                            <img src="{{ $img->imageUrl() }}" alt="" class="h-full w-full object-cover" loading="lazy">
                        </div>
                        <form method="post" action="{{ route('admin.banners.update', $img) }}" enctype="multipart/form-data" class="mt-3 grid gap-2 {{ $key === 'contact_hero' ? 'sm:grid-cols-[1fr_1fr_auto]' : '' }}">
                            @csrf
                            <input type="hidden" name="position" value="{{ $key }}">
                            <input type="hidden" name="sort_order" value="{{ $img->sort_order }}">
                            <input type="text" name="title" value="{{ $img->title }}" placeholder="Mô tả ảnh (tốt cho SEO)" class="adm-input">
                            <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="adm-file" title="Thay ảnh">
                            <div class="flex items-center gap-3">
                                <label class="flex items-center gap-1.5 text-sm"><input type="checkbox" name="is_active" value="1" @checked($img->is_active) class="h-4 w-4 accent-primary-600"> Hiện</label>
                                <button class="adm-btn adm-btn-dark py-1.5">Lưu</button>
                            </div>
                        </form>
                        <form method="post" action="{{ route('admin.banners.destroy', $img) }}" class="mt-1" onsubmit="return confirm('Xóa ảnh này khỏi máy chủ?')">@csrf
                            <button class="text-sm text-primary-600 hover:underline">Xóa ảnh</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($list->count() < $pinfo['max'])
            <form method="post" action="{{ route('admin.banners.store') }}" enctype="multipart/form-data" class="mt-4 grid gap-3 rounded-xl border border-dashed border-slate-300 bg-slate-50/60 p-4 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
                @csrf
                <input type="hidden" name="position" value="{{ $key }}">
                <label class="block">
                    <span class="mb-1 block text-sm font-medium">Chọn ảnh *</span>
                    <input type="file" name="image" accept="image/jpeg,image/png,image/webp" required class="adm-file"
                           @change="preview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null">
                </label>
                <label class="block">
                    <span class="mb-1 block text-sm font-medium">Mô tả ảnh</span>
                    <input type="text" name="title" placeholder="VD: Kho hàng D&T tại Bình Chánh" class="adm-input">
                </label>
                <button class="adm-btn adm-btn-primary">Tải lên</button>
                <template x-if="preview">
                    <div class="sm:col-span-3">
                        <div class="max-w-xl overflow-hidden rounded-lg bg-slate-100" @style(['aspect-ratio: '.$pinfo['ratio']])>
                            <img :src="preview" alt="" class="h-full w-full object-cover">
                        </div>
                    </div>
                </template>
            </form>
        @elseif ($list->isEmpty() === false)
        @endif
    </section>
@endforeach
@endsection
