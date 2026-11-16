<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Nhập / cập nhật sản phẩm hàng loạt từ file CSV (Excel -> Lưu thành "CSV UTF-8").
 * Khóa đối chiếu là cột "sku": mã đã có thì cập nhật, chưa có thì thêm mới.
 *
 * Cột hỗ trợ (dòng đầu là tiêu đề, xem docs/mau-nhap-san-pham.csv):
 *   sku*, name*, category (dạng "Giấy in > Giấy A4"), brand, unit, pack_size, price, compare_price,
 *   min_order_qty, short_description, description, specs ("Khổ: A4 | Định lượng: 80gsm"),
 *   images (ngăn bằng dấu phẩy hoặc "|"), is_featured (1/0), is_active (1/0),
 *   meta_title, meta_description
 */
class ImportProducts extends Command
{
    protected $signature = 'products:import {file : Đường dẫn file CSV} {--dry-run : Chỉ kiểm tra, không ghi vào DB}';

    protected $description = 'Nhập/cập nhật sản phẩm từ file CSV theo mã SKU';

    public function handle(): int
    {
        $path = $this->argument('file');
        if (! is_readable($path)) {
            $this->error("Không đọc được file: {$path}");

            return self::FAILURE;
        }

        $handle = fopen($path, 'r');
        $firstLine = fgets($handle);
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
        rewind($handle);

        $headers = fgetcsv($handle, 0, $delimiter, '"', '');
        $headers = array_map(fn ($h) => Str::of($h)->replace("\u{FEFF}", '')->trim()->lower()->toString(), $headers ?: []);

        foreach (['sku', 'name'] as $required) {
            if (! in_array($required, $headers, true)) {
                $this->error("Thiếu cột bắt buộc: {$required}");

                return self::FAILURE;
            }
        }

        $created = $updated = $skipped = 0;
        $line = 1;
        $dry = (bool) $this->option('dry-run');

        while (($row = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
            $line++;
            if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }
            $data = array_combine($headers, array_pad(array_slice($row, 0, count($headers)), count($headers), ''));
            $data = array_map(fn ($v) => trim((string) $v), $data);

            if ($data['sku'] === '' || $data['name'] === '') {
                $this->warn("Dòng {$line}: thiếu sku hoặc name, bỏ qua.");
                $skipped++;

                continue;
            }

            $attributes = $this->mapRow($data, $dry);
            $existing = Product::where('sku', $data['sku'])->first();

            if ($dry) {
                $existing ? $updated++ : $created++;

                continue;
            }

            if ($existing) {
                $existing->update($attributes);
                $updated++;
            } else {
                $attributes['slug'] = $this->uniqueSlug($data['name'], $data['sku']);
                Product::create(['sku' => $data['sku']] + $attributes);
                $created++;
            }
        }
        fclose($handle);

        Cache::forget('sitemap.xml');
        Cache::forget('menu.categories');

        $this->info(($dry ? '[Chạy thử] ' : '')."Thêm mới: {$created} · Cập nhật: {$updated} · Bỏ qua: {$skipped}");

        return self::SUCCESS;
    }

    private function mapRow(array $d, bool $dry): array
    {
        $attrs = ['name' => $d['name']];

        // Chỉ ghi các cột có trong file, để file cập nhật giá không xóa mô tả cũ.
        $plain = ['brand', 'unit', 'pack_size', 'short_description', 'description', 'meta_title', 'meta_description'];
        foreach ($plain as $col) {
            if (array_key_exists($col, $d)) {
                $attrs[$col] = $d[$col] !== '' ? $d[$col] : null;
            }
        }

        foreach (['price', 'compare_price'] as $col) {
            if (array_key_exists($col, $d)) {
                $digits = preg_replace('/\D/', '', $d[$col]);
                $attrs[$col] = $digits !== '' && (int) $digits > 0 ? (int) $digits : null;
            }
        }
        if (array_key_exists('min_order_qty', $d)) {
            $attrs['min_order_qty'] = max(1, (int) $d['min_order_qty']);
        }
        foreach (['is_featured', 'is_active'] as $flag) {
            if (array_key_exists($flag, $d) && $d[$flag] !== '') {
                $attrs[$flag] = in_array(Str::lower($d[$flag]), ['1', 'true', 'x', 'yes', 'có'], true);
            }
        }
        if (array_key_exists('specs', $d)) {
            $specs = [];
            foreach (preg_split('/\s*\|\s*/', $d['specs'], -1, PREG_SPLIT_NO_EMPTY) as $pair) {
                [$k, $v] = array_pad(explode(':', $pair, 2), 2, '');
                if (trim($k) !== '') {
                    $specs[trim($k)] = trim($v);
                }
            }
            $attrs['specs'] = $specs ?: null;
        }
        if (array_key_exists('images', $d)) {
            $images = preg_split('/\s*[|,]\s*/', $d['images'], -1, PREG_SPLIT_NO_EMPTY);
            $attrs['images'] = $images ?: null;
        }
        if (array_key_exists('category', $d) && $d['category'] !== '' && ! $dry) {
            $attrs['category_id'] = $this->resolveCategory($d['category'])->id;
        }

        return $attrs;
    }

    /** "Giấy in > Giấy A4" -> tạo cây danh mục nếu chưa có. */
    private function resolveCategory(string $path): Category
    {
        $parent = null;
        foreach (preg_split('/\s*>\s*/', $path, -1, PREG_SPLIT_NO_EMPTY) as $name) {
            $parent = Category::firstOrCreate(
                ['name' => $name, 'parent_id' => $parent?->id],
                ['slug' => $this->uniqueCategorySlug($name)]
            );
        }

        return $parent;
    }

    private function uniqueSlug(string $name, string $sku): string
    {
        $slug = Str::slug($name);
        if (Product::where('slug', $slug)->exists()) {
            $slug = Str::slug($name.'-'.$sku);
        }

        return $slug;
    }

    private function uniqueCategorySlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 2;
        while (Category::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
