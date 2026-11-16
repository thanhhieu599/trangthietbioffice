@extends('layouts.app')

@section('title', 'Yêu cầu báo giá văn phòng phẩm cho doanh nghiệp | '.config('site.brand'))
@section('description', 'Gửi danh mục văn phòng phẩm, vật tư tiêu hao cần mua. Nhận báo giá trong 4 giờ làm việc, hỗ trợ giao định kỳ và công nợ theo hợp đồng.')

@php($input = 'w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand-600 focus:outline-none')

@section('content')
<div class="mx-auto max-w-5xl px-4 py-8" x-data="quotePage(@js(route('quote.lookup')))">
    @include('partials.breadcrumb', ['items' => [['name' => 'Yêu cầu báo giá', 'url' => route('quote.create')]]])
    <h1 class="text-2xl font-bold text-slate-900 md:text-3xl">Yêu cầu báo giá</h1>
    <p class="mt-2">Kiểm tra danh sách sản phẩm, điền thông tin công ty. Bộ phận kinh doanh sẽ gửi báo giá trong 4 giờ làm việc.</p>

    @if ($errors->any())
        <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            <ul class="list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="post" action="{{ route('quote.store') }}" class="mt-6 grid gap-6 lg:grid-cols-5" @submit="$store.quote.save()">
        @csrf
        <section class="rounded-xl bg-white p-5 lg:col-span-3">
            <h2 class="mb-3 font-semibold text-slate-900">Sản phẩm cần báo giá (<span x-text="$store.quote.count">0</span>)</h2>

            <template x-if="$store.quote.count === 0">
                <p class="text-sm text-slate-500">Chưa có sản phẩm nào. Anh/chị có thể chọn từ danh mục, hoặc ghi danh sách cần mua vào ô <strong>Ghi chú</strong> bên cạnh.</p>
            </template>

            <ul class="divide-y divide-slate-100">
                <template x-for="(item, idx) in $store.quote.items" :key="item.sku">
                    <li class="flex items-center gap-3 py-3">
                        <img :src="details[item.sku]?.image || ''" x-show="details[item.sku]?.image" alt="" class="h-12 w-12 rounded object-contain">
                        <div class="min-w-0 flex-1">
                            <a :href="details[item.sku]?.url" class="block truncate text-sm font-medium text-slate-900 hover:text-brand-600" x-text="details[item.sku]?.name || item.sku"></a>
                            <p class="text-xs text-slate-500">Mã: <span x-text="item.sku"></span></p>
                        </div>
                        <input type="hidden" :name="`items[${idx}][sku]`" :value="item.sku">
                        <input type="number" min="1" :name="`items[${idx}][quantity]`" :value="item.qty"
                               @change="$store.quote.setQty(item.sku, $event.target.value)" class="w-20 rounded border border-slate-300 px-2 py-1 text-sm" aria-label="Số lượng">
                        <span class="w-12 text-xs text-slate-500" x-text="details[item.sku]?.unit || ''"></span>
                        <button type="button" @click="$store.quote.remove(item.sku)" class="text-sm text-red-600 hover:underline">Xóa</button>
                    </li>
                </template>
            </ul>
        </section>

        <section class="space-y-3 rounded-xl bg-white p-5 lg:col-span-2">
            <h2 class="font-semibold text-slate-900">Thông tin công ty</h2>
            <input type="text" name="company" value="{{ old('company') }}" required placeholder="Tên công ty *" class="{{ $input }}">
            <input type="text" name="tax_code" value="{{ old('tax_code') }}" placeholder="Mã số thuế (để xuất hóa đơn)" class="{{ $input }}">
            <input type="text" name="contact_name" value="{{ old('contact_name') }}" required placeholder="Người liên hệ *" class="{{ $input }}">
            <input type="tel" name="phone" value="{{ old('phone') }}" required placeholder="Số điện thoại / Zalo *" class="{{ $input }}">
            <input type="email" name="email" value="{{ old('email') }}" required placeholder="Email nhận báo giá *" class="{{ $input }}">
            <input type="text" name="delivery_area" value="{{ old('delivery_area') }}" placeholder="Khu vực giao hàng (KCN, tỉnh/thành)" class="{{ $input }}">
            <select name="frequency" class="{{ $input }}">
                <option value="">Nhu cầu mua</option>
                <option value="once" @selected(old('frequency') === 'once')>Mua một lần</option>
                <option value="monthly" @selected(old('frequency') === 'monthly')>Định kỳ hàng tháng</option>
                <option value="quarterly" @selected(old('frequency') === 'quarterly')>Định kỳ hàng quý</option>
            </select>
            <textarea name="note" rows="4" placeholder="Ghi chú: danh mục cần mua, yêu cầu quy cách, thời gian giao…" class="{{ $input }}">{{ old('note') }}</textarea>
            <input type="hidden" name="source" :value="source()">
            {{-- Bẫy spam: người thật không thấy ô này --}}
            <div class="hidden" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
            <button type="submit" class="w-full rounded-lg bg-accent-500 px-5 py-3 font-semibold text-slate-900 hover:bg-accent-600">Gửi yêu cầu báo giá</button>
            <p class="text-xs text-slate-500">Thông tin chỉ dùng để báo giá và chăm sóc đơn hàng.</p>
        </section>
    </form>
</div>
@endsection
