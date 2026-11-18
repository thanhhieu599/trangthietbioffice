@extends('layouts.app')

@section('title', 'Yêu cầu báo giá văn phòng phẩm cho doanh nghiệp | '.config('site.brand'))
@section('description', 'Gửi danh mục văn phòng phẩm, vật tư tiêu hao cần mua. Nhận báo giá trong 4 giờ làm việc, hỗ trợ giao định kỳ và công nợ theo hợp đồng.')

@php
    $input = 'w-full rounded-lg border border-neutral-300 px-3 py-2.5 focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15';
    // Khách đã đăng nhập: điền sẵn từ tài khoản (dữ liệu vừa nhập lỗi vẫn ưu tiên qua old()).
    $prefill = $user ? ['contact_name' => $user->name] + $user->only(['email', 'phone', 'company', 'tax_code', 'address', 'ward', 'province']) : [];
@endphp

@section('content')
<div class="mx-auto max-w-site px-4 py-6" x-data="quotePage(@js(route('quote.lookup')))">
    @include('partials.breadcrumb', ['items' => [['name' => 'Giỏ hàng – Đặt hàng', 'url' => route('quote.create')]]])
    <h1 class="text-2xl font-bold text-ink-900 md:text-3xl">Giỏ hàng – Đặt hàng &amp; yêu cầu báo giá</h1>
    <p class="mt-1 text-neutral-600">Kiểm tra sản phẩm, điền thông tin giao hàng. Bộ phận kinh doanh xác nhận đơn và gửi báo giá trong 4 giờ làm việc.</p>

    @if ($errors->any())
        <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700" role="alert">
            Vui lòng kiểm tra lại {{ count($errors->all()) }} thông tin được đánh dấu bên dưới.
            @if ($errors->has('note'))<br>{{ $errors->first('note') }}@endif
        </div>
    @endif

    <form method="post" action="{{ route('quote.store') }}" class="mt-6 grid items-start gap-6 lg:grid-cols-[1fr_380px]" @submit="$store.quote.save()">
        @csrf
        <div class="min-w-0 space-y-6">
            {{-- 1. Sản phẩm --}}
            <section class="rounded-md bg-white p-5">
                <h2 class="mb-3 text-lg font-semibold text-ink-900">1. Sản phẩm (<span x-text="$store.quote.count">0</span>)</h2>

                <template x-if="$store.quote.count === 0">
                    <p class="text-sm text-neutral-500">Giỏ hàng đang trống. Anh/chị có thể <a href="{{ route('home') }}" class="text-primary-600 underline">chọn sản phẩm</a>, hoặc ghi danh sách cần mua vào ô <strong>Ghi chú</strong>.</p>
                </template>

                <ul class="divide-y divide-neutral-100">
                    <template x-for="(item, idx) in $store.quote.items" :key="$store.quote.key(item)">
                        <li class="flex items-center gap-3 py-3">
                            <img :src="details[item.sku]?.image || ''" x-show="details[item.sku]?.image" alt="" class="h-12 w-12 rounded object-contain">
                            <div class="min-w-0 flex-1">
                                <a :href="details[item.sku]?.url" class="block truncate text-sm font-medium text-ink-900 hover:text-primary-600" x-text="details[item.sku]?.name || item.sku"></a>
                                <p class="text-xs text-neutral-500">Mã: <span x-text="item.sku"></span>
                                    <template x-if="details[item.sku]?.variant || item.opts"><span> · <span x-text="[details[item.sku]?.variant, item.opts].filter(Boolean).join(' · ')"></span></span></template></p>
                            </div>
                            <input type="hidden" :name="`items[${idx}][sku]`" :value="item.sku">
                            <input type="hidden" :name="`items[${idx}][opts]`" :value="item.opts || ''">
                            <input type="number" min="1" :name="`items[${idx}][quantity]`" :value="item.qty"
                                   @change="$store.quote.setQty($store.quote.key(item), $event.target.value)" class="w-20 rounded border border-neutral-300 px-2 py-1 text-sm" aria-label="Số lượng">
                            <span class="w-12 text-xs text-neutral-500" x-text="details[item.sku]?.unit || ''"></span>
                            <button type="button" @click="$store.quote.remove($store.quote.key(item))" class="text-sm text-red-600 hover:underline">Xóa</button>
                        </li>
                    </template>
                </ul>
            </section>

            {{-- 2. Thông tin giao hàng --}}
            <section class="rounded-md bg-white p-5">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-lg font-semibold text-ink-900">2. Thông tin người nhận</h2>
                    @auth
                        <span class="text-sm text-neutral-600">Đặt hàng với tài khoản <strong>{{ $user->email }}</strong></span>
                    @endauth
                </div>

                @guest
                    <div class="mb-5 flex flex-wrap items-center gap-x-4 gap-y-2 rounded-lg border border-primary-100 bg-primary-50 px-4 py-3 text-sm">
                        <x-icon name="user" class="h-5 w-5 text-primary-600" />
                        <span class="flex-1">Đã có tài khoản? Đăng nhập để tự điền thông tin và theo dõi đơn hàng.</span>
                        <a href="{{ route('login', ['quay-lai' => '/bao-gia']) }}" class="font-semibold text-primary-600 hover:underline">Đăng nhập</a>
                        <a href="{{ route('register', ['quay-lai' => '/bao-gia']) }}" class="font-semibold text-primary-600 hover:underline">Đăng ký</a>
                    </div>
                @endguest

                @include('partials.customer-fields', ['v' => $prefill, 'nameField' => 'contact_name'])

                @auth
                    <label class="mt-4 flex items-center gap-2 text-sm">
                        <input type="checkbox" name="save_to_account" value="1" @checked(old('save_to_account', ! $user->hasDeliveryInfo())) class="h-4 w-4 accent-primary-600">
                        Lưu thông tin này vào tài khoản cho lần đặt sau
                    </label>
                @endauth
            </section>
        </div>

        {{-- 3. Tóm tắt & gửi --}}
        <aside class="space-y-4 rounded-md bg-white p-5 lg:sticky lg:top-4">
            <h2 class="text-lg font-semibold text-ink-900">3. Xác nhận đơn</h2>
            <p class="flex justify-between text-sm"><span class="text-neutral-600">Số mã sản phẩm</span><strong x-text="$store.quote.count">0</strong></p>
            <div>
                <label for="note" class="mb-1 block text-sm font-medium text-ink-800">Ghi chú</label>
                <textarea id="note" name="note" rows="4" placeholder="Danh mục cần mua thêm, quy cách, thời gian giao mong muốn…" class="{{ $input }} {{ $errors->has('note') ? 'border-red-400' : '' }}">{{ old('note') }}</textarea>
            </div>
            <input type="hidden" name="source" :value="source()">
            {{-- Bẫy spam: người thật không thấy ô này --}}
            <div class="hidden" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
            <label class="flex items-start gap-2 text-sm {{ $errors->has('consent') ? 'text-red-700' : '' }}">
                <input type="checkbox" name="consent" value="1" required @checked(old('consent', (bool) $user)) class="mt-0.5 h-4 w-4 shrink-0 accent-primary-600">
                <span>Tôi đồng ý để {{ config('site.brand') }} xử lý thông tin cá nhân trên để giao hàng, báo giá và chăm sóc đơn hàng.</span>
            </label>
            <button type="submit" class="w-full rounded-lg bg-primary-600 px-5 py-3 font-semibold text-white hover:bg-primary-700">Đặt hàng / Gửi yêu cầu báo giá</button>
            <ul class="space-y-1.5 text-xs text-neutral-600">
                <li class="flex gap-2"><x-icon name="clipboard" class="h-4 w-4 shrink-0 text-primary-600" />Xác nhận và báo giá trong 4 giờ làm việc</li>
                <li class="flex gap-2"><x-icon name="receipt" class="h-4 w-4 shrink-0 text-primary-600" />Thanh toán khi nhận hàng, chuyển khoản hoặc công nợ theo hợp đồng</li>
                <li class="flex gap-2"><x-icon name="shield" class="h-4 w-4 shrink-0 text-primary-600" />Thông tin chỉ dùng để giao hàng và chăm sóc đơn</li>
            </ul>
        </aside>
    </form>
</div>
@endsection
