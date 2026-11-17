@extends('layouts.app')

@section('title', 'Không tìm thấy trang | '.config('site.brand'))
@section('description', 'Trang bạn tìm không tồn tại hoặc đã được chuyển đi.')
@section('robots', 'noindex, follow')
@section('canonical', 'none')

@section('content')
<div class="mx-auto max-w-site px-4 py-12">
    <div class="mx-auto max-w-2xl rounded-md bg-white p-8 text-center">
        <p class="text-5xl font-bold text-primary-600">404</p>
        <h1 class="mt-3 text-2xl font-bold text-ink-900">Không tìm thấy trang</h1>
        <p class="mt-2 text-neutral-600">Trang anh/chị tìm không tồn tại, đã đổi địa chỉ hoặc sản phẩm đã ngừng kinh doanh.</p>

        <form action="{{ route('search') }}" method="get" role="search" class="mx-auto mt-6 flex max-w-md">
            <label for="q404" class="sr-only">Tìm sản phẩm</label>
            <input id="q404" name="q" type="search" placeholder="Tìm giấy in, bút, bìa hồ sơ…" class="min-w-0 flex-1 rounded-l-md border border-r-0 border-neutral-300 px-4 py-2.5 text-sm focus:border-primary-600 focus:outline-none">
            <button type="submit" class="rounded-r-md bg-primary-600 px-5 font-semibold text-white hover:bg-primary-700">Tìm</button>
        </form>

        <div class="mt-6 flex flex-wrap justify-center gap-3 text-sm">
            <a href="{{ route('home') }}" class="rounded-md border border-neutral-300 px-4 py-2 hover:border-primary-600 hover:text-primary-600">Về trang chủ</a>
            <a href="{{ route('quote.create') }}" class="rounded-md border border-neutral-300 px-4 py-2 hover:border-primary-600 hover:text-primary-600">Gửi yêu cầu báo giá</a>
            <a href="{{ route('contact') }}" class="rounded-md border border-neutral-300 px-4 py-2 hover:border-primary-600 hover:text-primary-600">Liên hệ</a>
        </div>

        @if (! empty($menuCategories) && $menuCategories->isNotEmpty())
            <h2 class="mt-8 text-sm font-semibold uppercase text-neutral-500">Danh mục sản phẩm</h2>
            <ul class="mt-3 flex flex-wrap justify-center gap-2 text-sm">
                @foreach ($menuCategories as $cat)
                    <li><a href="{{ $cat['url'] }}" class="inline-block rounded bg-neutral-100 px-3 py-1.5 hover:text-primary-600">{{ $cat['name'] }}</a></li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
@endsection
