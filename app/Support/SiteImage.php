<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * Ảnh "bỏ vào là chạy": chỉ cần chép file vào thư mục public/images, không sửa code.
 *
 *   public/images/logo.png          -> logo ở header (D&T Office)
 *   public/images/logo-footer.png   -> logo trong khối giới thiệu ở footer (trangthietbi.vn)
 *   public/images/banners/*.jpg     -> ảnh banner trang chủ (xếp theo tên file: 01-..., 02-...)
 *   public/images/categories/{slug}.png -> ảnh/biểu tượng danh mục ở lưới danh mục trang chủ
 *   public/images/brands/{slug}.png     -> logo thương hiệu (khối Thương hiệu, trang Thương hiệu)
 *
 * Đuôi file chấp nhận: svg, png, webp, jpg, jpeg.
 */
class SiteImage
{
    private const EXTENSIONS = ['svg', 'png', 'webp', 'jpg', 'jpeg'];

    /** Đường dẫn ảnh theo tên (không đuôi) trong public/images, hoặc null nếu chưa có file. */
    public static function find(string $name, array $extensions = self::EXTENSIONS): ?string
    {
        foreach ($extensions as $ext) {
            $relative = "images/{$name}.{$ext}";
            if (is_file(public_path($relative))) {
                // Thêm thời điểm sửa file để trình duyệt tải lại khi thay logo mới.
                return '/'.$relative.'?v='.filemtime(public_path($relative));
            }
        }

        return null;
    }

    /**
     * Ảnh danh mục: public/images/categories/{slug}.png (hoặc .webp/.jpg).
     * Ảnh anh tự chép vào được ưu tiên hơn biểu tượng .svg mặc định đi kèm web.
     */
    public static function category(string $slug): ?string
    {
        return self::find('categories/'.$slug, ['png', 'webp', 'jpg', 'jpeg', 'svg']);
    }

    /** Logo thương hiệu: public/images/brands/{slug}.png (hoặc .webp/.jpg/.svg), ví dụ thien-long.png. */
    public static function brand(string $slug): ?string
    {
        return self::find('brands/'.$slug, ['png', 'webp', 'jpg', 'jpeg', 'svg']);
    }

    /** Banner lớn đầu trang thương hiệu: public/images/brands/banners/{slug}.jpg|png|webp (tải lên tại Quản trị > Hình ảnh). */
    public static function brandBanner(string $slug): ?string
    {
        return self::find('brands/banners/'.$slug, ['webp', 'jpg', 'jpeg', 'png']);
    }

    /** Logo header: file public/images/logo.* hoặc biến SITE_LOGO. */
    public static function logo(): ?string
    {
        return self::find('logo') ?: (config('site.logo') ?: null);
    }

    /** Logo khối giới thiệu footer: file public/images/logo-footer.* hoặc biến SITE_PARENT_LOGO. */
    public static function footerLogo(): ?string
    {
        return self::find('logo-footer') ?: (config('site.parent_logo') ?: null);
    }

    /**
     * Ảnh banner trong public/images/banners, xếp theo tên file.
     * File tên "...-mobile.*" là ảnh riêng cho điện thoại của banner cùng tên.
     *
     * @return Collection<int, array{image: string, image_mobile: ?string, title: string}>
     */
    public static function banners(): Collection
    {
        $dir = public_path('images/banners');
        if (! is_dir($dir)) {
            return collect();
        }

        $files = collect(scandir($dir))
            ->filter(fn ($f) => in_array(strtolower(pathinfo($f, PATHINFO_EXTENSION)), self::EXTENSIONS, true))
            ->sort()
            ->values();

        $mobile = $files->filter(fn ($f) => str_ends_with(pathinfo($f, PATHINFO_FILENAME), '-mobile'))
            ->keyBy(fn ($f) => substr(pathinfo($f, PATHINFO_FILENAME), 0, -7));

        return $files
            ->reject(fn ($f) => str_ends_with(pathinfo($f, PATHINFO_FILENAME), '-mobile'))
            ->map(fn ($f) => [
                'image' => '/images/banners/'.$f,
                'image_mobile' => ($m = $mobile->get(pathinfo($f, PATHINFO_FILENAME))) ? '/images/banners/'.$m : null,
                'title' => config('site.brand'),
            ])
            ->values();
    }
}
