@extends('layouts.app')

@section('title', 'Thương hiệu văn phòng phẩm phân phối | '.config('site.brand'))
@section('description', 'Danh sách thương hiệu văn phòng phẩm, vật tư tiêu hao '.config('site.brand').' cung cấp cho doanh nghiệp, nhà máy.')

@section('content')
<div class="mx-auto max-w-site px-4 py-5">
    @include('partials.breadcrumb', ['items' => [['name' => 'Thương hiệu', 'url' => route('brands.index')]]])

    <div class="mb-5 border-b-2 border-primary-600">
        <h1 class="inline-block bg-primary-600 px-4 py-2 text-lg font-bold uppercase text-white">Thương hiệu</h1>
    </div>

    @if ($brands->isEmpty())
        <p class="rounded-md bg-white p-6">Danh sách thương hiệu đang được cập nhật.</p>
    @else
        <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
            @foreach ($brands as $brand)
                <li>@include('partials.brand-card')</li>
            @endforeach
        </ul>
    @endif
</div>
@endsection
