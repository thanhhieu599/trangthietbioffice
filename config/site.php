<?php

/*
| Thông tin doanh nghiệp dùng chung cho header, footer, dữ liệu có cấu trúc
| (schema.org Organization) và email thông báo. Sửa trong file .env.
*/

return [
    'brand' => env('SITE_BRAND', 'D&T Office'),
    'tagline' => env('SITE_TAGLINE', 'Văn phòng phẩm & vật tư tiêu hao cho doanh nghiệp, nhà máy'),
    'company' => env('SITE_COMPANY', 'CÔNG TY TNHH TMDV PHÁT TRIỂN KỸ THUẬT D & T'),
    'tax_code' => env('SITE_TAX_CODE'),
    'phone' => env('SITE_PHONE', ''),
    'zalo' => env('SITE_ZALO', ''),
    'email' => env('SITE_EMAIL', ''),
    'address' => env('SITE_ADDRESS', ''),
    'parent_site' => env('SITE_PARENT_URL', 'https://trangthietbi.vn'),
    'logo' => env('SITE_LOGO'), // ví dụ /images/logo.png (đặt file trong public/images)
    'map_embed' => env('SITE_MAP_EMBED'), // link nhúng Google Maps cho trang Liên hệ
    'working_hours' => env('SITE_WORKING_HOURS', 'Thứ 2 – Thứ 7: 8:00 – 17:30'),

    // Email nhận thông báo khi có yêu cầu báo giá mới (để trống = không gửi)
    'quote_notify_email' => env('QUOTE_NOTIFY_EMAIL'),

    // Mã đo lường (để trống = không chèn)
    'ga4_id' => env('GA4_ID'),
    'gsc_verification' => env('GSC_VERIFICATION'),

    // Thế mạnh B2B hiển thị trên trang chủ
    'usps' => [
        ['title' => 'Báo giá theo danh mục', 'text' => 'Gửi danh sách cần mua, nhận báo giá trong 4 giờ làm việc.'],
        ['title' => 'Giao định kỳ hàng tháng', 'text' => 'Lên lịch giao cố định, không lo hết hàng giữa tháng.'],
        ['title' => 'Công nợ & hóa đơn VAT', 'text' => 'Thanh toán công nợ theo hợp đồng, xuất hóa đơn đầy đủ.'],
        ['title' => 'Một đầu mối', 'text' => 'VPP, vật tư tiêu hao và PPE cho cả nhà máy từ một nhà cung cấp.'],
    ],
];
