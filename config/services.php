<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    | Sapo (đồng bộ khách hàng và đơn hàng). Tạo "Ứng dụng riêng" trong trang quản trị Sapo, cấp quyền:
    | Khách hàng (Đọc và ghi), Đơn hàng (Đọc và ghi), Đơn hàng nháp (Đọc và ghi, nếu dùng draft_order),
    | Sản phẩm (Chỉ đọc). Điền khóa vào .env trên máy chủ – KHÔNG đưa lên git.
    | Thiếu store/key/secret = tắt đồng bộ, website chạy bình thường.
    */
    'sapo' => [
        'store' => env('SAPO_STORE'),              // VD: ten-cua-hang (=> ten-cua-hang.mysapo.net) hoặc tên miền đầy đủ
        'key' => env('SAPO_API_KEY'),
        'secret' => env('SAPO_API_SECRET'),
        // Công tắc GỬI dữ liệu lên Sapo (tạo/cập nhật khách, tạo đơn). false = chỉ ĐỌC từ Sapo
        // (kéo sản phẩm, ghép SKU vẫn chạy) – đặt false trên máy thử để không đẩy đơn/khách thử sang Sapo thật.
        'sync' => (bool) env('SAPO_SYNC_ENABLED', true),
        'order_mode' => env('SAPO_ORDER_MODE', 'order'), // order (đã xác minh trong tài liệu) | draft_order (chưa xác minh)
        'tags' => env('SAPO_ORDER_TAGS', 'web-vpp'),
        'country' => env('SAPO_COUNTRY', 'Vietnam'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
