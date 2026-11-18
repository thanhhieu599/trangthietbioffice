<?php

/*
| Thông báo kiểm tra dữ liệu tiếng Việt (APP_LOCALE=vi). Mục nào không có ở đây sẽ dùng bản tiếng Anh mặc định.
| Từng form vẫn có thể đặt thông báo riêng (ưu tiên hơn file này).
*/
return [
    'uploaded' => ':Attribute chưa tải lên được – thường do file quá nặng (tối đa 3 MB) hoặc mạng bị gián đoạn. Hãy nén ảnh (ví dụ tinypng.com) rồi thử lại.',
    'max' => [
        'file' => ':Attribute tối đa :max KB.',
        'string' => ':Attribute tối đa :max ký tự.',
        'numeric' => ':Attribute tối đa :max.',
        'array' => ':Attribute tối đa :max mục.',
    ],
    'mimes' => ':Attribute phải là file: :values.',
    'extensions' => ':Attribute phải có đuôi: :values.',
    'image' => ':Attribute phải là ảnh.',
    'dimensions' => ':Attribute có kích thước không phù hợp.',
    'required' => 'Vui lòng nhập :attribute.',

    'attributes' => [
        'new_images.*' => 'ảnh sản phẩm',
        'image' => 'ảnh',
        'cover' => 'ảnh đại diện',
        'banner' => 'banner',
        'banner_mobile' => 'banner điện thoại',
        'page_banner' => 'banner ngang',
        'image_mobile' => 'ảnh điện thoại',
        'file' => 'file',
    ],
];
