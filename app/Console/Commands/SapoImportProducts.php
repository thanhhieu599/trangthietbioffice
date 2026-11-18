<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Models\Redirect;
use App\Services\Sapo\SapoClient;
use App\Support\Html;
use App\Support\SeoAudit;
use App\Support\Upload;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Kéo sản phẩm của các danh mục Sapo Web về web (CHỈ ĐỌC từ Sapo, không sửa gì bên Sapo):
 *   php artisan sapo:import-products "Dụng cụ văn phòng" "Thiết bị văn phòng" "Văn phòng phẩm" --dry-run
 *   php artisan sapo:import-products "Dụng cụ văn phòng" "Thiết bị văn phòng" "Văn phòng phẩm"
 *
 * - Sản phẩm mới luôn ở trạng thái ẨN (is_active = false) để quản trị viên tự bật.
 * - Mỗi sản phẩm Sapo = 1 sản phẩm web. Tùy chọn (Màu sắc, Dung lượng...) -> "Thuộc tính"; phiên bản (SKU riêng) lưu kèm.
 * - Đã có trên web (trùng SKU) -> không ghi đè nội dung; chỉ cập nhật thương hiệu + thuộc tính, gộp bản cũ bị tách theo màu.
 * - Chỉ lấy ẢNH ĐẦU TIÊN làm ảnh đại diện (tải về public/images/products/uploads).
 * - Giá 0 trên Sapo -> để trống (web hiện "Liên hệ báo giá").
 * - Danh mục web: dùng danh mục cùng tên nếu có, chưa có thì tạo mới ở trạng thái ẩn.
 *   Sản phẩm nằm ở nhiều danh mục -> xếp vào danh mục đứng trước trong danh sách tham số.
 */
class SapoImportProducts extends Command
{
    protected $signature = 'sapo:import-products {collections* : Tên hoặc alias danh mục trên Sapo Web}
        {--dry-run : Chỉ thống kê, không ghi gì}';

    protected $description = 'Kéo sản phẩm theo danh mục Sapo Web về web (ẩn sẵn, bỏ qua SKU trùng, lấy ảnh đầu tiên)';

    private const IMAGE_DIR = 'images/products/uploads';

    public function handle(SapoClient $sapo): int
    {
        if (! $sapo->enabled()) {
            $this->error('Chưa cấu hình Sapo trong .env (SAPO_STORE, SAPO_API_KEY, SAPO_API_SECRET).');

            return self::FAILURE;
        }
        $dry = (bool) $this->option('dry-run');

        // 1) Tìm danh mục Sapo theo tên / alias
        $all = collect($sapo->collections())->map(fn ($c) => $c + ['title' => $c['name'] ?? '']); // Sapo dùng "name"
        $targets = [];
        foreach ($this->argument('collections') as $wanted) {
            $key = Str::slug($wanted);
            $c = $all->first(fn ($c) => Str::slug($c['title'] ?? '') === $key || ($c['alias'] ?? '') === $key);
            if (! $c) {
                $this->error("Không thấy danh mục \"{$wanted}\" trên Sapo.");

                return self::FAILURE;
            }
            $targets[] = $c;
        }

        // 2) Gom sản phẩm (mỗi sản phẩm Sapo chỉ tính 1 lần, theo danh mục đứng trước)
        $products = [];
        foreach ($targets as $c) {
            $n = $sapo->eachProductInCollection((int) $c['id'], function (array $p) use (&$products, $c) {
                $products[$p['id']] ??= ['product' => $p, 'collection' => $c];
            });
            $this->line("Sapo – {$c['title']}: {$n} sản phẩm");
        }

        $stats = ['tao_moi' => 0, 'cap_nhat' => 0, 'gop' => 0, 'khong_sku' => 0, 'loi_anh' => 0];
        $categoryIds = [];
        $all = Product::get(); // để tìm theo SKU chính lẫn SKU phiên bản

        foreach ($products as ['product' => $p, 'collection' => $c]) {
            [$attrs, $variants] = $this->attributesOf($p);
            $skus = array_column($variants, 'sku');
            if (! $skus) {
                $stats['khong_sku']++;

                continue;
            }
            $lower = array_map('mb_strtolower', $skus);
            // Sản phẩm web đang dùng 1 trong các SKU này (kể cả bản cũ bị tách mỗi màu 1 sản phẩm)
            $matches = $all->filter(fn (Product $w) => array_intersect(array_map('mb_strtolower', $w->allSkus()), $lower))->sortBy('id')->values();
            $brand = $p['vendor'] ? Str::limit(trim($p['vendor']), 250, '') : null; // "Nhà cung cấp" trên Sapo
            $first = $variants[0];
            $multi = count($variants) > 1;

            if ($matches->isNotEmpty()) {
                // Đã có trên web: KHÔNG ghi đè nội dung; chỉ cập nhật thương hiệu + thuộc tính/phiên bản theo Sapo,
                // gộp các sản phẩm cũ bị tách theo màu về 1 sản phẩm.
                $main = $matches->first();
                if ($dry) {
                    $matches->count() > 1 ? $stats['gop'] += $matches->count() - 1 : $stats['cap_nhat']++;

                    continue;
                }
                $main->fill(['brand' => $brand ?? $main->brand, 'attrs' => $attrs ?: null, 'variants' => $multi ? $variants : null]);
                if ($matches->count() > 1) {
                    $main->fill(['sku' => $first['sku'], 'name' => Str::limit(trim($p['name']), 250, ''),
                        'slug' => SeoAudit::uniqueSlug(Product::class, $p['alias'] ?: $p['name'], $main->id),
                        'is_active' => $matches->contains('is_active', true)]);
                    foreach ($matches->slice(1) as $dup) {
                        $this->mergeInto($dup, $main);
                        $stats['gop']++;
                    }
                }
                if ($main->isDirty()) {
                    $main->save();
                    $stats['cap_nhat']++;
                }

                continue;
            }

            $stats['tao_moi']++;
            if ($dry) {
                continue;
            }
            $categoryIds[$c['id']] ??= $this->category($c['title']);
            $imageUrl = collect($p['images'] ?? [])->sortBy('position')->first()['src'] ?? ($p['image']['src'] ?? null);
            $image = $imageUrl ? $this->downloadImage($imageUrl, $p['name']) : null;
            if ($imageUrl && ! $image) {
                $stats['loi_anh']++;
            }

            $all->push(Product::create([
                'category_id' => $categoryIds[$c['id']],
                'sku' => $first['sku'],
                'name' => Str::limit(trim($p['name']), 250, ''),
                'slug' => SeoAudit::uniqueSlug(Product::class, $p['alias'] ?: $p['name']),
                'brand' => $brand,
                'unit' => $first['unit'] ?: null,
                'attrs' => $attrs ?: null,
                'variants' => $multi ? $variants : null,
                'description' => Html::clean($p['content'] ?? null),
                'short_description' => $p['summary'] ? Str::limit(trim(strip_tags($p['summary'])), 990) : null,
                'images' => $image ? [$image] : [],
                'price' => $first['price'],
                'compare_price' => $first['compare_price'],
                'stock_status' => collect($variants)->contains('stock_status', 'in_stock') ? 'in_stock' : 'out_of_stock',
                'meta_title' => $p['meta_title'] ? Str::limit($p['meta_title'], 250, '') : null,
                'meta_description' => $p['meta_description'] ? Str::limit($p['meta_description'], 295, '') : null,
                'is_featured' => false,
                'is_active' => false, // ẨN – quản trị viên tự bật
            ]));
            $this->output->write('.');
        }

        if (! $dry) {
            $this->newLine();
            SeoAudit::flush();
        }
        $this->info(($dry ? '[Chạy thử – chưa ghi] ' : '').
            "Tạo mới: {$stats['tao_moi']} · Đã có (cập nhật thuộc tính/thương hiệu): {$stats['cap_nhat']} · Gộp sản phẩm tách theo màu: {$stats['gop']} · Bỏ qua không có SKU: {$stats['khong_sku']}".
            ($dry ? '' : " · Ảnh tải lỗi: {$stats['loi_anh']}"));

        return self::SUCCESS;
    }

    /**
     * Thuộc tính + phiên bản từ sản phẩm Sapo.
     *   Thuộc tính: tùy chọn của Sapo (Màu sắc, Dung lượng...) – bỏ "Title / Default Title".
     *   Phiên bản: mỗi phiên bản có SKU -> ['sku', 'options' => [tên => giá trị], 'price', 'compare_price', 'stock_status', 'unit'].
     *
     * @return array{0: list<array>, 1: list<array>}
     */
    private function attributesOf(array $p): array
    {
        $names = collect($p['options'] ?? [])->sortBy('position')->values()
            ->map(fn ($o) => trim((string) ($o['name'] ?? '')))->all();

        $variants = collect($p['variants'] ?? [])->filter(fn ($v) => trim((string) ($v['sku'] ?? '')) !== '')->map(function ($v) use ($names) {
            $options = [];
            foreach ($names as $i => $name) {
                $value = trim((string) ($v['option'.($i + 1)] ?? ''));
                if ($name !== '' && $name !== 'Title' && $value !== '' && $value !== 'Default Title') {
                    $options[$name] = $value;
                }
            }
            $price = (int) round((float) ($v['price'] ?? 0));
            $compare = (int) round((float) ($v['compare_at_price'] ?? 0));
            $tracked = filled($v['inventory_management'] ?? null);

            return [
                'sku' => trim($v['sku']),
                'options' => $options,
                'price' => $price > 0 ? $price : null,
                'compare_price' => $price > 0 && $compare > $price ? $compare : null,
                'stock_status' => $tracked && (int) ($v['inventory_quantity'] ?? 0) <= 0 ? 'out_of_stock' : 'in_stock',
                'unit' => $v['unit'] ?? null,
            ];
        })->values()->all();

        $attrs = collect($variants)->flatMap(fn ($v) => array_keys($v['options']))->unique()->values()
            ->map(fn ($name) => ['name' => $name, 'values' => collect($variants)->pluck('options.'.$name)->filter()->unique()->values()->all()])
            ->filter(fn ($a) => $a['values'])->values()->all();

        return [$attrs, $variants];
    }

    /** Gộp sản phẩm trùng (cách cũ: mỗi màu 1 sản phẩm) vào sản phẩm chính: chuyển đơn hàng / flash sale, xóa bản trùng. */
    private function mergeInto(Product $dup, Product $main): void
    {
        DB::table('quote_request_items')->where('product_id', $dup->id)->update(['product_id' => $main->id]);
        DB::table('flash_sale_items')->where('product_id', $dup->id)->whereNotExists(fn ($q) => $q->from('flash_sale_items as f2')
            ->whereColumn('f2.flash_sale_id', 'flash_sale_items.flash_sale_id')->where('f2.product_id', $main->id))
            ->update(['product_id' => $main->id]);
        DB::table('flash_sale_items')->where('product_id', $dup->id)->delete();
        foreach ($dup->images ?? [] as $img) {
            if (! in_array($img, $main->images ?? [], true)) {
                Upload::delete($img);
            }
        }
        Redirect::record($dup->url(), $main->url()); // link cũ (nếu đã chia sẻ) chuyển 301 sang sản phẩm gộp
        $dup->delete();
    }

    /** Danh mục web cùng tên (không phân biệt hoa thường / dấu cách) hoặc tạo mới ở trạng thái ẩn. */
    private function category(string $title): int
    {
        $found = Category::get(['id', 'name'])->first(fn ($c) => Str::slug($c->name) === Str::slug($title));
        if ($found) {
            return $found->id;
        }

        return Category::create([
            'name' => $title,
            'slug' => SeoAudit::uniqueSlug(Category::class, $title),
            'is_active' => false,
            'sort_order' => 100,
        ])->id;
    }

    /** Tải ảnh về máy chủ web; lỗi thì trả null (sản phẩm vẫn được tạo, không có ảnh). */
    private function downloadImage(string $url, string $name): ?string
    {
        try {
            $r = Http::timeout(30)->retry(2, 1000, throw: false)->get($url);
            if (! $r->successful()) {
                return null;
            }
            $ext = match (true) {
                str_contains($r->header('Content-Type'), 'png') => 'png',
                str_contains($r->header('Content-Type'), 'webp') => 'webp',
                str_contains($r->header('Content-Type'), 'jpeg'), str_contains($r->header('Content-Type'), 'jpg') => 'jpg',
                default => null,
            };
            if (! $ext || ! @getimagesizefromstring($r->body())) {
                return null; // không phải ảnh thật
            }
            $file = Str::limit(Str::slug($name), 60, '').'-'.Str::lower(Str::random(5)).'.'.$ext;
            if (! is_dir(public_path(self::IMAGE_DIR))) {
                mkdir(public_path(self::IMAGE_DIR), 0755, true);
            }
            file_put_contents(public_path(self::IMAGE_DIR.'/'.$file), $r->body());

            return '/'.self::IMAGE_DIR.'/'.$file;
        } catch (\Throwable) {
            return null;
        }
    }
}
