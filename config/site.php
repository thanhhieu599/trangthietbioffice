<?php

/*
| Thông tin doanh nghiệp dùng chung cho header, footer, trang Liên hệ, dữ liệu có cấu trúc
| (schema.org Organization) và email thông báo.
| Giá trị đơn lẻ sửa trong .env; danh sách (địa chỉ, người liên hệ, chính sách...) sửa tại file này.
*/

return [
    'brand' => env('SITE_BRAND', 'D&T Office'),
    'tagline' => env('SITE_TAGLINE', 'Văn phòng phẩm & vật tư tiêu hao cho doanh nghiệp, nhà máy'),
    'company' => env('SITE_COMPANY', 'CÔNG TY TNHH THƯƠNG MẠI DỊCH VỤ PHÁT TRIỂN KỸ THUẬT D&T'),
    'tax_code' => env('SITE_TAX_CODE'),
    'phone' => env('SITE_PHONE', '0933 568 088'),   // hotline chính (header, nút gọi nổi)
    'zalo' => env('SITE_ZALO', '0933568088'),
    'email' => env('SITE_EMAIL', 'info@dt-safety.com'),
    'address' => env('SITE_ADDRESS', '212/327/5 Nguyễn Văn Nguyễn, P. Tân Định, TP HCM'),
    'parent_site' => env('SITE_PARENT_URL', 'https://trangthietbi.vn'),
    'logo' => env('SITE_LOGO'), // ví dụ /images/logo.png (đặt file trong public/images)
    'parent_logo' => env('SITE_PARENT_LOGO'), // logo trangthietbi.vn cho khối giới thiệu ở footer
    'map_embed' => env('SITE_MAP_EMBED'), // link nhúng Google Maps cho trang Liên hệ
    'working_hours' => env('SITE_WORKING_HOURS', 'Thứ 2 – Thứ 7: 8:00 – 17:30'),

    // Footer – cột Liên hệ
    'addresses' => [
        ['label' => 'Trụ sở', 'text' => '212/327/5 Nguyễn Văn Nguyễn, P. Tân Định, TP HCM'],
        ['label' => 'VPGD', 'text' => '87 Đường 16, KDC Phong Phú 4, Xã Bình Hưng, HCM'],
    ],
    'contacts' => [
        ['name' => 'Mr Lương Bảo Lân', 'phone' => '089 6217 382'],
        ['name' => 'Mr Thụy', 'phone' => '0918 826 446'],
        ['name' => 'Mr Tân', 'phone' => '0934 359 910'],
    ],

    // Mạng xã hội (để trống = ẩn biểu tượng)
    'social' => [
        'facebook' => env('SOCIAL_FACEBOOK'),
        'youtube' => env('SOCIAL_YOUTUBE'),
        'tiktok' => env('SOCIAL_TIKTOK'),
        'instagram' => env('SOCIAL_INSTAGRAM'),
    ],

    // Footer – khối giới thiệu
    'about' => [
        'title' => 'D&T – Đối tác cung ứng bảo hộ lao động, văn phòng phẩm & vật tư công nghiệp toàn diện',
        'text' => 'Trong môi trường sản xuất ngày càng khắt khe về tiêu chuẩn an toàn và minh bạch, D&T tự hào là đối tác chiến lược được tin tưởng lựa chọn trong lĩnh vực <strong>thiết bị bảo hộ lao động (PPE)</strong>, <strong>văn phòng phẩm</strong>, vật tư tiêu hao và giải pháp kỹ thuật cho hàng trăm nhà máy, công trình và doanh nghiệp FDI tại Việt Nam.',
        'badges' => [
            ['icon' => 'shield', 'text' => '100% chính hãng & CO/CQ'],
            ['icon' => 'percent', 'text' => 'Chiết khấu cao cho nhà máy & FDI'],
            ['icon' => 'truck', 'text' => 'Giao nhanh 24h & đầy đủ VAT'],
        ],
    ],

    // Footer – thanh toán. Logo Bộ Công Thương CHỈ hiện khi website này đã được
    // thông báo trên online.gov.vn: điền link hồ sơ vào SITE_BCT_URL.
    'payments' => ['Tiền mặt', 'Chuyển khoản', 'Internet Banking', 'VISA', 'Mastercard', 'JCB'],
    'bct_url' => env('SITE_BCT_URL'),

    // Email nhận thông báo khi có yêu cầu báo giá mới (để trống = không gửi)
    'quote_notify_email' => env('QUOTE_NOTIFY_EMAIL'),

    // Mã đo lường (để trống = không chèn)
    'ga4_id' => env('GA4_ID'),
    'gsc_verification' => env('GSC_VERIFICATION'),
];
