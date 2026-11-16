<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Dữ liệu khởi tạo: cây danh mục VPP B2B + vài sản phẩm, bài viết mẫu để xem giao diện.
 * Sản phẩm thật nhập bằng: php artisan products:import duong-dan-file.csv
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $tree = [
            'Giấy in' => ['Giấy A4', 'Giấy A3', 'Giấy in màu, giấy bìa'],
            'Bút viết' => ['Bút bi', 'Bút dạ quang, bút lông', 'Bút chì, gôm'],
            'File & lưu trữ hồ sơ' => ['Bìa còng, bìa hộp', 'Bìa lá, bìa nút', 'Kẹp, ghim, bấm kim'],
            'Băng keo & đóng gói' => ['Băng keo trong, đục', 'Băng keo giấy, 2 mặt', 'Màng PE, dây đai'],
            'Mực in' => ['Mực laser', 'Mực in phun'],
            'Vệ sinh văn phòng' => ['Giấy vệ sinh, khăn giấy', 'Nước lau, hóa chất vệ sinh'],
            'Thiết bị văn phòng nhỏ' => ['Máy tính cầm tay', 'Máy bấm, đục lỗ'],
        ];

        $order = 0;
        foreach ($tree as $root => $children) {
            $parent = Category::updateOrCreate(['slug' => Str::slug($root)], [
                'name' => $root,
                'intro' => "Cung cấp {$this->lower($root)} cho văn phòng, nhà máy: báo giá theo số lượng, giao định kỳ, hóa đơn VAT.",
                'sort_order' => $order++,
            ]);
            foreach ($children as $i => $child) {
                Category::updateOrCreate(['slug' => Str::slug($child)], [
                    'name' => $child,
                    'parent_id' => $parent->id,
                    'sort_order' => $i,
                ]);
            }
        }

        $a4 = Category::where('slug', 'giay-a4')->first();
        $pen = Category::where('slug', 'but-bi')->first();
        $tape = Category::where('slug', 'bang-keo-trong-duc')->first();

        $samples = [
            [$a4, 'VPP-GA4-70', 'Giấy in A4 70gsm – ram 500 tờ', 'ram', '5 ram/thùng', ['Khổ' => 'A4 (210 x 297 mm)', 'Định lượng' => '70gsm', 'Số tờ' => '500 tờ/ram']],
            [$a4, 'VPP-GA4-80', 'Giấy in A4 80gsm – ram 500 tờ', 'ram', '5 ram/thùng', ['Khổ' => 'A4 (210 x 297 mm)', 'Định lượng' => '80gsm', 'Số tờ' => '500 tờ/ram']],
            [$pen, 'VPP-BB-XANH', 'Bút bi mực xanh ngòi 0.5mm – hộp 20 cây', 'hộp', '20 cây/hộp', ['Màu mực' => 'Xanh', 'Ngòi' => '0.5mm']],
            [$tape, 'VPP-BK-TRONG-48', 'Băng keo trong 48mm x 100 yard', 'cuộn', '6 cuộn/cây', ['Bản rộng' => '48mm', 'Chiều dài' => '100 yard']],
        ];

        foreach ($samples as [$cat, $sku, $name, $unit, $pack, $specs]) {
            Product::updateOrCreate(['sku' => $sku], [
                'category_id' => $cat->id,
                'name' => $name,
                'slug' => Str::slug($name),
                'unit' => $unit,
                'pack_size' => $pack,
                'specs' => $specs,
                'short_description' => "{$name}, quy cách {$pack}. Báo giá sỉ theo số lượng cho doanh nghiệp.",
                'is_featured' => true,
            ]);
        }

        Post::updateOrCreate(['slug' => 'dinh-muc-van-phong-pham-theo-dau-nguoi'], [
            'title' => 'Định mức văn phòng phẩm theo đầu người: cách lập và kiểm soát chi phí',
            'excerpt' => 'Cách xây định mức VPP theo phòng ban, theo đầu người và theo tháng để kiểm soát ngân sách mua sắm.',
            'content' => '<p>Bài viết mẫu. Thay nội dung thật trong trang quản trị hoặc seeder.</p>',
            'faqs' => [
                ['q' => 'Nên đặt VPP theo tháng hay theo quý?', 'a' => 'Hàng tiêu hao nhanh (giấy, bút) nên giao hàng tháng; hàng chậm luân chuyển (file, thiết bị nhỏ) có thể gom theo quý.'],
            ],
            'published_at' => now(),
        ]);
    }

    private function lower(string $s): string
    {
        return mb_strtolower($s);
    }
}
