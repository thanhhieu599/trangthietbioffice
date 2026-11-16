<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Các trang chính sách hiện ở cột "Chính sách" của footer.
 * Nội dung để trống (trang hiện "đang cập nhật" và không cho Google index) cho đến khi
 * D&T đưa nội dung chính thức vào – không tự soạn văn bản pháp lý thay doanh nghiệp.
 */
class PageSeeder extends Seeder
{
    public function run(): void
    {
        $titles = [
            'Chính sách bảo mật',
            'Chính sách vận chuyển',
            'Chính sách đổi trả',
            'Quy định sử dụng',
            'Hình thức thanh toán',
            'Khách hàng doanh nghiệp',
            'Cung ứng trọn gói văn phòng phẩm cho doanh nghiệp',
            'Quy trình cung ứng sản phẩm – dịch vụ tại D&T',
            'Thư ngỏ hợp tác',
        ];

        foreach ($titles as $order => $title) {
            Page::firstOrCreate(['slug' => Str::slug($title)], [
                'title' => $title,
                'sort_order' => $order,
            ]);
        }
    }
}
