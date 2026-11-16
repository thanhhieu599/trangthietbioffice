@extends('layouts.app')

@section('title', 'Liên hệ báo giá văn phòng phẩm | '.config('site.brand'))
@section('description', 'Liên hệ '.config('site.company').' để nhận báo giá văn phòng phẩm, vật tư tiêu hao cho doanh nghiệp, nhà máy.')

@php
    $phone = config('site.phone');
    $input = 'w-full rounded border border-neutral-300 px-3 py-2.5 focus:border-primary-600 focus:outline-none';
@endphp

@section('content')
<div class="mx-auto max-w-site px-4 py-5">
    @include('partials.breadcrumb', ['items' => [['name' => 'Liên hệ', 'url' => route('contact')]]])

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-md bg-white p-6">
            <h1 class="text-xl font-bold uppercase text-ink-900">Liên hệ</h1>
            <p class="mt-1 font-semibold text-ink-800">{{ config('site.company') }}</p>
            <ul class="mt-4 space-y-3">
                @if (config('site.address'))
                    <li class="flex gap-3"><x-icon name="pin" class="h-5 w-5 shrink-0 text-primary-600" />{{ config('site.address') }}</li>
                @endif
                @if ($phone)
                    <li class="flex gap-3"><x-icon name="phone" class="h-5 w-5 shrink-0 text-primary-600" />
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="font-bold text-primary-600">{{ $phone }}</a></li>
                @endif
                @if (config('site.email'))
                    <li class="flex gap-3"><x-icon name="mail" class="h-5 w-5 shrink-0 text-primary-600" />
                        <a href="mailto:{{ config('site.email') }}" class="hover:text-primary-600">{{ config('site.email') }}</a></li>
                @endif
                @if (config('site.tax_code'))
                    <li class="flex gap-3"><x-icon name="receipt" class="h-5 w-5 shrink-0 text-primary-600" />MST: {{ config('site.tax_code') }}</li>
                @endif
                <li class="flex gap-3"><x-icon name="clock" class="h-5 w-5 shrink-0 text-primary-600" />{{ config('site.working_hours') }}</li>
            </ul>

            @if (config('site.map_embed'))
                <iframe src="{{ config('site.map_embed') }}" title="Bản đồ" class="mt-6 h-72 w-full rounded border-0" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            @endif
        </section>

        <section class="rounded-md bg-white p-6">
            <h2 class="text-xl font-bold uppercase text-ink-900">Gửi yêu cầu</h2>
            <p class="mt-1 text-sm text-neutral-600">Để lại thông tin và nhu cầu, bộ phận kinh doanh sẽ liên hệ trong giờ làm việc.</p>

            @if ($errors->any())
                <div class="mt-4 rounded border border-primary-100 bg-primary-50 p-3 text-sm text-primary-700">
                    <ul class="list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif

            <form method="post" action="{{ route('quote.store') }}" class="mt-4 grid gap-3 sm:grid-cols-2">
                @csrf
                <input type="hidden" name="source" value="lien-he">
                <input type="text" name="company" value="{{ old('company') }}" required placeholder="Tên công ty *" class="{{ $input }} sm:col-span-2">
                <input type="text" name="contact_name" value="{{ old('contact_name') }}" required placeholder="Người liên hệ *" class="{{ $input }}">
                <input type="tel" name="phone" value="{{ old('phone') }}" required placeholder="Số điện thoại *" class="{{ $input }}">
                <input type="email" name="email" value="{{ old('email') }}" required placeholder="Email *" class="{{ $input }} sm:col-span-2">
                <textarea name="note" rows="5" required placeholder="Nội dung / danh mục cần báo giá *" class="{{ $input }} sm:col-span-2">{{ old('note') }}</textarea>
                <div class="hidden" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
                <button type="submit" class="rounded bg-primary-600 px-6 py-3 font-semibold text-white hover:bg-primary-700 sm:col-span-2">Gửi yêu cầu</button>
            </form>
        </section>
    </div>
</div>
@endsection
