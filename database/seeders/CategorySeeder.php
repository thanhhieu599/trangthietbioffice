<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Cây danh mục văn phòng phẩm. Danh mục có 'home' => true sẽ thành một khối sản phẩm
 * riêng trên trang chủ (theo thứ tự trong mảng).
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $tree = [
            ['Giấy in – giấy photo', true, ['Giấy A4', 'Giấy A3', 'Giấy in màu, giấy bìa', 'Giấy in nhiệt, giấy in bill']],
            ['Bút viết', true, ['Bút bi', 'Bút gel, bút ký', 'Bút chì, gôm, chuốt']],
            ['Bìa hồ sơ', true, ['Bìa còng, bìa hộp', 'Bìa lá, bìa nút', 'Bìa trình ký, kẹp hồ sơ']],
            ['Mực lông – bút lông', true, ['Bút lông dầu', 'Bút lông bảng', 'Mực bơm bút lông', 'Bút dạ quang']],
            ['Băng keo – đóng gói', true, ['Băng keo trong, đục', 'Băng keo giấy, 2 mặt', 'Màng PE, dây đai']],
            ['Kẹp – ghim – bấm kim', false, ['Kẹp bướm, kẹp giấy', 'Ghim bấm, máy bấm', 'Đục lỗ']],
            ['Sổ – tập – giấy note', false, ['Sổ bìa da, sổ lò xo', 'Giấy note, phân trang']],
            ['Mực in – ruy băng', false, ['Mực laser', 'Mực in phun', 'Ruy băng, mực máy in mã vạch']],
            ['Dụng cụ văn phòng', false, ['Máy tính cầm tay', 'Kéo, dao rọc giấy', 'Thước, hồ dán']],
            ['Vệ sinh văn phòng', false, ['Giấy vệ sinh, khăn giấy', 'Nước lau, hóa chất vệ sinh']],
        ];

        foreach ($tree as $order => [$name, $home, $children]) {
            $parent = Category::updateOrCreate(['slug' => Str::slug($name)], [
                'name' => $name,
                'intro' => 'Cung cấp '.mb_strtolower($name).' cho văn phòng, nhà máy: báo giá theo số lượng, giao định kỳ, hóa đơn VAT.',
                'sort_order' => $order,
                'show_on_home' => $home,
            ]);
            foreach ($children as $i => $child) {
                Category::updateOrCreate(['slug' => Str::slug($child)], [
                    'name' => $child,
                    'parent_id' => $parent->id,
                    'sort_order' => $i,
                ]);
            }
        }
    }
}
