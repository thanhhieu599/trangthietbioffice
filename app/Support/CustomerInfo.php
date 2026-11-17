<?php

namespace App\Support;

/**
 * Quy tắc kiểm tra thông tin khách hàng, dùng chung cho: đăng ký, sửa tài khoản, bước đặt hàng.
 */
class CustomerInfo
{
    public const PHONE = ['required', 'string', 'max:30', 'regex:/^[0-9+().\s-]{8,}$/'];

    /** Thông tin liên hệ + địa chỉ giao hàng (bắt buộc đủ để giao hàng). */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => self::PHONE,
            'company' => ['nullable', 'string', 'max:255'],
            'tax_code' => ['nullable', 'string', 'max:20', 'regex:/^[0-9-]{10,14}$/'],
            'address' => ['required', 'string', 'max:255'],
            'ward' => ['required', 'string', 'max:255'],
            'province' => ['required', 'string', 'max:255'],
        ];
    }

    public static function messages(): array
    {
        return [
            'required' => 'Vui lòng nhập :attribute.',
            'email' => ':Attribute không hợp lệ.',
            'max' => ':Attribute quá dài (tối đa :max ký tự).',
            'min' => ':Attribute phải có ít nhất :min ký tự.',
            'confirmed' => ':Attribute nhập lại không khớp.',
            'unique' => ':Attribute này đã được đăng ký. Hãy đăng nhập hoặc dùng chức năng quên mật khẩu.',
            'phone.regex' => 'Số điện thoại không hợp lệ.',
            'tax_code.regex' => 'Mã số thuế gồm 10 hoặc 13 chữ số.',
            'accepted' => 'Vui lòng đồng ý với :attribute.',
            'current_password' => 'Mật khẩu hiện tại không đúng.',
        ];
    }

    public static function attributes(): array
    {
        return [
            'name' => 'họ và tên',
            'contact_name' => 'họ và tên người nhận',
            'email' => 'email',
            'phone' => 'số điện thoại',
            'company' => 'tên công ty',
            'tax_code' => 'mã số thuế',
            'address' => 'số nhà, tên đường',
            'ward' => 'phường/xã',
            'province' => 'tỉnh/thành phố',
            'password' => 'mật khẩu',
            'current_password' => 'mật khẩu hiện tại',
            'terms' => 'điều khoản sử dụng',
        ];
    }
}
