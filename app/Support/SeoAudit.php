<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Kiểm tra nhanh các lỗi SEO cơ bản (dùng cho danh sách và trang Tổng quan).
 * Bảng chấm điểm chi tiết khi soạn thảo nằm ở resources/js/admin.js (seoPanel).
 */
class SeoAudit
{
    /** @return list<string> */
    public static function post(Post $p): array
    {
        $issues = [];
        $issues[] = blank($p->focus_keyword) ? 'Chưa có từ khóa chính' : null;
        $issues[] = self::metaDescIssue($p->meta_description);
        $issues[] = self::titleIssue($p->meta_title ?: $p->title);
        $issues[] = Html::wordCount($p->content) < 600 ? 'Nội dung dưới 600 từ' : null;
        $issues[] = blank($p->image) ? 'Chưa có ảnh đại diện' : null;
        $issues[] = ! preg_match('/<h2\b/i', (string) $p->content) ? 'Chưa có tiêu đề phụ H2' : null;
        $issues[] = self::missingAlt($p->content) ? 'Có ảnh trong bài thiếu mô tả (alt)' : null;

        return array_values(array_filter($issues));
    }

    /** @return list<string> */
    public static function product(Product $p): array
    {
        $issues = [];
        $issues[] = blank($p->focus_keyword) ? 'Chưa có từ khóa chính' : null;
        $issues[] = self::metaDescIssue($p->meta_description);
        $issues[] = empty($p->images) ? 'Chưa có ảnh sản phẩm' : null;
        $issues[] = blank($p->short_description) ? 'Chưa có mô tả ngắn' : null;
        $issues[] = Html::wordCount($p->description) < 150 ? 'Mô tả chi tiết dưới 150 từ' : null;
        $issues[] = ! $p->category_id ? 'Chưa chọn danh mục' : null;

        return array_values(array_filter($issues));
    }

    /** @return list<string> */
    public static function category(Category $c): array
    {
        $issues = [];
        $issues[] = blank($c->focus_keyword) ? 'Chưa có từ khóa chính' : null;
        $issues[] = self::metaDescIssue($c->meta_description);
        $issues[] = blank($c->intro) ? 'Chưa có đoạn giới thiệu' : null;
        $issues[] = Html::wordCount($c->content) < 300 ? 'Nội dung SEO cuối trang dưới 300 từ' : null;

        return array_values(array_filter($issues));
    }

    private static function metaDescIssue(?string $desc): ?string
    {
        $len = mb_strlen((string) $desc);

        return match (true) {
            $len === 0 => 'Chưa có mô tả meta',
            $len < 120 => 'Mô tả meta ngắn (< 120 ký tự)',
            $len > 160 => 'Mô tả meta dài (> 160 ký tự)',
            default => null,
        };
    }

    private static function titleIssue(?string $title): ?string
    {
        $len = mb_strlen((string) $title);

        return $len > 65 ? 'Tiêu đề SEO dài (> 65 ký tự)' : ($len < 25 ? 'Tiêu đề SEO ngắn (< 25 ký tự)' : null);
    }

    private static function missingAlt(?string $html): bool
    {
        preg_match_all('/<img\b[^>]*>/i', (string) $html, $m);
        foreach ($m[0] as $img) {
            if (! preg_match('/\balt\s*=\s*("[^"]+"|\'[^\']+\')/i', $img)) {
                return true;
            }
        }

        return false;
    }

    /** Slug không trùng trong bảng (thêm -2, -3... nếu trùng). */
    public static function uniqueSlug(string $modelClass, string $source, ?int $ignoreId = null): string
    {
        $base = Str::slug($source) ?: 'muc';
        $slug = $base;
        $i = 2;
        while ($modelClass::where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    /** Xóa cache để sitemap, menu danh mục cập nhật ngay sau khi sửa. */
    public static function flush(): void
    {
        Cache::forget('sitemap.xml');
        Cache::forget('menu.categories');
    }
}
