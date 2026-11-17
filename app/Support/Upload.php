<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Lưu ảnh tải lên từ trang quản trị vào public/images/... (chạy trên hosting thường, không cần storage:link).
 * Đuôi file lấy theo NỘI DUNG ảnh (không theo tên file người dùng gửi) để không thể lưu file .php.
 */
class Upload
{
    public const RULE = ['file', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:3072'];

    public static function image(UploadedFile $file, string $dir, ?string $nameHint = null): string
    {
        $ext = match ($file->extension()) {
            'jpeg', 'jpg' => 'jpg',
            'png' => 'png',
            'webp' => 'webp',
            default => throw new \InvalidArgumentException('Định dạng ảnh không hợp lệ.'),
        };

        // Tên file có chữ (VD: giay-a4-double-a-x7k2p.jpg) giúp SEO hình ảnh tốt hơn tên ngẫu nhiên.
        $base = Str::limit(Str::slug($nameHint ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)), 60, '');
        $name = ($base ?: now()->format('Ymd-His')).'-'.Str::lower(Str::random(5)).'.'.$ext;

        $dir = trim($dir, '/');
        $file->move(public_path($dir), $name);

        return '/'.$dir.'/'.$name;
    }

    /** Chỉ xóa file nằm trong các thư mục tải lên của trang quản trị. */
    public static function delete(?string $path): void
    {
        if (! $path || ! preg_match('#^/images/(posts|products|categories|editor)/uploads/[^/]+$#', $path)) {
            return;
        }
        $full = public_path(ltrim($path, '/'));
        if (is_file($full)) {
            @unlink($full);
        }
    }
}
