<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Dữ liệu khởi tạo: cây danh mục VPP + sản phẩm / banner / bài viết DEMO để xem giao diện.
 * Sản phẩm thật nhập bằng: php artisan products:import duong-dan-file.csv
 * Xóa dữ liệu demo: php artisan migrate:fresh (rồi chỉ chạy CategorySeeder nếu cần).
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([CategorySeeder::class, PageSeeder::class]);

        if (app()->environment('production')) {
            return; // Không đưa dữ liệu demo lên site thật.
        }

        $this->demoProducts();
        $this->demoPosts();

        Cache::forget('menu.categories');
        Cache::forget('sitemap.xml');
    }

    private function demoProducts(): void
    {
        $demo = [
            'giay-a4' => [
                ['Giấy in A4 70gsm – ram 500 tờ', 'Double A', 'ram', 72000, 85000],
                ['Giấy in A4 80gsm – ram 500 tờ', 'Double A', 'ram', 89000, 99000],
                ['Giấy in A4 70gsm IK Plus – ram 500 tờ', 'IK Plus', 'ram', null, null],
                ['Giấy in A4 Paper One 80gsm – ram 500 tờ', 'Paper One', 'ram', null, null],
                ['Giấy in A4 Excel 70gsm – ram 500 tờ', 'Excel', 'ram', null, null],
            ],
            'giay-a3' => [
                ['Giấy in A3 70gsm – ram 500 tờ', 'Double A', 'ram', null, null],
                ['Giấy in A3 80gsm – ram 500 tờ', 'Paper One', 'ram', null, null],
            ],
            'but-bi' => [
                ['Bút bi Thiên Long TL-027 mực xanh – hộp 20 cây', 'Thiên Long', 'hộp', null, null],
                ['Bút bi Thiên Long TL-027 mực đỏ – hộp 20 cây', 'Thiên Long', 'hộp', null, null],
                ['Bút bi bấm Deli 0.7mm mực xanh – hộp 12 cây', 'Deli', 'hộp', null, null],
                ['Bút gel ký tên 0.5mm mực đen – hộp 12 cây', 'Deli', 'hộp', null, null],
                ['Bút bi Bến Nghé B-36 – hộp 20 cây', 'Bến Nghé', 'hộp', null, null],
            ],
            'bia-cong-bia-hop' => [
                ['Bìa còng bật 7cm khổ A4 – Kingjim', 'Kingjim', 'cái', null, null],
                ['Bìa còng bật 5cm khổ F4 – Plus', 'Plus', 'cái', null, null],
                ['Hộp đựng hồ sơ 10cm nhựa – Deli', 'Deli', 'cái', null, null],
                ['Bìa còng nhẫn 3.5cm khổ A4', 'Deli', 'cái', null, null],
            ],
            'bia-la-bia-nut' => [
                ['Bìa lá A4 nhựa trong – xấp 100 cái', 'Plus', 'xấp', null, null],
                ['Bìa nút A4 nhựa – xấp 12 cái', 'Deli', 'xấp', null, null],
                ['Bìa trình ký A4 có kẹp', 'Kingjim', 'cái', null, null],
            ],
            'but-long-dau' => [
                ['Bút lông dầu Thiên Long PM-09 xanh – hộp 10 cây', 'Thiên Long', 'hộp', null, null],
                ['Bút lông dầu 2 đầu Zebra Mckee đen', 'Zebra', 'cây', null, null],
                ['Bút lông bảng Thiên Long WB-03 – hộp 10 cây', 'Thiên Long', 'hộp', null, null],
            ],
            'muc-bom-but-long' => [
                ['Mực bơm bút lông bảng Thiên Long WBI-01 xanh', 'Thiên Long', 'chai', null, null],
                ['Mực bơm bút lông dầu Thiên Long PMI-01 đen', 'Thiên Long', 'chai', null, null],
            ],
            'bang-keo-trong-duc' => [
                ['Băng keo trong 48mm x 100 yard – cây 6 cuộn', null, 'cây', 96000, 120000],
                ['Băng keo đục 48mm x 100 yard – cây 6 cuộn', null, 'cây', null, null],
                ['Băng keo trong 24mm x 50 yard – cây 12 cuộn', null, 'cây', null, null],
            ],
            'bang-keo-giay-2-mat' => [
                ['Băng keo giấy 24mm x 20 yard', null, 'cuộn', null, null],
                ['Băng keo 2 mặt mỏng 24mm', null, 'cuộn', null, null],
            ],
        ];

        $i = 0;
        foreach ($demo as $slug => $items) {
            $category = Category::where('slug', $slug)->first();
            foreach ($items as [$name, $brand, $unit, $price, $compare]) {
                $sku = 'DEMO-'.str_pad((string) ++$i, 3, '0', STR_PAD_LEFT);
                Product::updateOrCreate(['sku' => $sku], [
                    'category_id' => $category?->id,
                    'name' => $name,
                    'slug' => Str::slug($name),
                    'brand' => $brand,
                    'unit' => $unit,
                    'price' => $price,
                    'compare_price' => $compare,
                    'short_description' => "{$name}. Báo giá sỉ theo số lượng cho doanh nghiệp, giao định kỳ, hóa đơn VAT.",
                    'is_featured' => $i % 4 === 1,
                ]);
            }
        }
    }

    private function demoPosts(): void
    {
        $posts = [
            ['dinh-muc-van-phong-pham-theo-dau-nguoi', 'Định mức văn phòng phẩm theo đầu người: cách lập và kiểm soát chi phí'],
            ['chon-giay-in-70gsm-hay-80gsm', 'Giấy in 70gsm hay 80gsm: chọn loại nào cho văn phòng?'],
            ['quy-trinh-mua-vpp-dinh-ky-cho-nha-may', 'Quy trình mua văn phòng phẩm định kỳ cho nhà máy'],
            ['cach-luu-tru-ho-so-iso', 'Cách sắp xếp, lưu trữ hồ sơ ISO với bìa còng và hộp hồ sơ'],
            ['chon-bang-keo-dong-goi-cho-kho', 'Chọn băng keo đóng gói cho kho hàng: trong, đục hay giấy?'],
            ['but-long-dau-va-but-long-bang', 'Bút lông dầu và bút lông bảng: khác nhau thế nào, dùng ở đâu?'],
            ['kiem-soat-chi-phi-muc-in', 'Kiểm soát chi phí mực in cho văn phòng 50 – 200 người'],
            ['danh-muc-vpp-cho-van-phong-moi', 'Danh mục văn phòng phẩm cần có cho văn phòng mới thành lập'],
        ];
        foreach ($posts as $k => [$slug, $title]) {
            Post::updateOrCreate(['slug' => $slug], [
                'title' => $title,
                'excerpt' => 'Bài viết mẫu để xem bố cục khối Tin tức mới. Nội dung thật sẽ được đăng từ trang quản trị, kèm ảnh đại diện và mục hỏi đáp.',
                'content' => '<p>Bài viết mẫu. Thay nội dung thật trong trang quản trị.</p>',
                'faqs' => $k === 0 ? [['q' => 'Nên đặt VPP theo tháng hay theo quý?', 'a' => 'Hàng tiêu hao nhanh (giấy, bút) nên giao hàng tháng; hàng chậm luân chuyển có thể gom theo quý.']] : null,
                'published_at' => now()->subDays($k),
            ]);
        }
    }
}
