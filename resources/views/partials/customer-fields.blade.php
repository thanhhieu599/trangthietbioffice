{{--
    Ô nhập thông tin khách hàng + địa chỉ giao hàng. Dùng cho: đăng ký, trang tài khoản, bước đặt hàng.
    Biến: $v (mảng giá trị mặc định), $nameField ('name' | 'contact_name'), $withEmail (bool), $emailReadonly (bool)
--}}
@php
    $v = $v ?? [];
    $nameField = $nameField ?? 'name';
    $val = fn ($k) => old($k, $v[$k] ?? '');
    $field = 'w-full rounded-lg border px-3 py-2.5 text-[15px] focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15';
    $cls = fn ($k) => $field.' '.($errors->has($k) ? 'border-red-400 bg-red-50/40' : 'border-neutral-300');
    $label = 'mb-1 block text-sm font-medium text-ink-800';
    $err = fn ($k) => $errors->has($k) ? '<p class="mt-1 text-xs text-red-600">'.e($errors->first($k)).'</p>' : '';
@endphp

<fieldset class="grid gap-4 sm:grid-cols-2">
    <legend class="sr-only">Thông tin liên hệ</legend>
    <div class="sm:col-span-2">
        <label for="f-{{ $nameField }}" class="{{ $label }}">Họ và tên <span class="text-primary-600">*</span></label>
        <input id="f-{{ $nameField }}" type="text" name="{{ $nameField }}" value="{{ $val($nameField) }}" required autocomplete="name" maxlength="255" class="{{ $cls($nameField) }}">
        {!! $err($nameField) !!}
    </div>
    <div>
        <label for="f-phone" class="{{ $label }}">Số điện thoại / Zalo <span class="text-primary-600">*</span></label>
        <input id="f-phone" type="tel" name="phone" value="{{ $val('phone') }}" required autocomplete="tel" inputmode="tel" maxlength="30" placeholder="VD: 0933 568 088" class="{{ $cls('phone') }}">
        {!! $err('phone') !!}
    </div>
    @if ($withEmail ?? true)
        <div>
            <label for="f-email" class="{{ $label }}">Email <span class="text-primary-600">*</span></label>
            <input id="f-email" type="email" name="email" value="{{ $val('email') }}" required autocomplete="email" maxlength="255"
                   @if ($emailReadonly ?? false) readonly class="{{ $cls('email') }} bg-neutral-100 text-neutral-600" @else class="{{ $cls('email') }}" @endif>
            {!! $err('email') !!}
        </div>
    @endif
    <div>
        <label for="f-company" class="{{ $label }}">Tên công ty <span class="font-normal text-neutral-500">(nếu mua cho doanh nghiệp)</span></label>
        <input id="f-company" type="text" name="company" value="{{ $val('company') }}" autocomplete="organization" maxlength="255" class="{{ $cls('company') }}">
        {!! $err('company') !!}
    </div>
    <div>
        <label for="f-tax" class="{{ $label }}">Mã số thuế <span class="font-normal text-neutral-500">(để xuất hóa đơn VAT)</span></label>
        <input id="f-tax" type="text" name="tax_code" value="{{ $val('tax_code') }}" inputmode="numeric" maxlength="20" class="{{ $cls('tax_code') }}">
        {!! $err('tax_code') !!}
    </div>
</fieldset>

<fieldset class="mt-6 grid gap-4 sm:grid-cols-2">
    <legend class="mb-3 text-base font-semibold text-ink-900">Địa chỉ giao hàng</legend>
    <div class="sm:col-span-2">
        <label for="f-address" class="{{ $label }}">Số nhà, tên đường / tòa nhà, KCN <span class="text-primary-600">*</span></label>
        <input id="f-address" type="text" name="address" value="{{ $val('address') }}" required autocomplete="street-address" maxlength="255" placeholder="VD: 87 Đường 16, KDC Phong Phú 4" class="{{ $cls('address') }}">
        {!! $err('address') !!}
    </div>
    <div>
        <label for="f-ward" class="{{ $label }}">Phường / Xã <span class="text-primary-600">*</span></label>
        <input id="f-ward" type="text" name="ward" value="{{ $val('ward') }}" required autocomplete="address-level3" maxlength="255" placeholder="VD: Xã Bình Hưng" class="{{ $cls('ward') }}">
        {!! $err('ward') !!}
    </div>
    <div>
        <label for="f-province" class="{{ $label }}">Tỉnh / Thành phố <span class="text-primary-600">*</span></label>
        <input id="f-province" type="text" name="province" value="{{ $val('province') }}" required autocomplete="address-level1" maxlength="255" list="dt-provinces" placeholder="Chọn hoặc gõ tên tỉnh/thành" class="{{ $cls('province') }}">
        <datalist id="dt-provinces">
            @foreach (config('site.provinces', []) as $p)<option value="{{ $p }}"></option>@endforeach
        </datalist>
        {!! $err('province') !!}
    </div>
</fieldset>
