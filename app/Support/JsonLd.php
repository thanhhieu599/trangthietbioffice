<?php

namespace App\Support;

use App\Models\Post;
use App\Models\Product;
use Illuminate\Support\Str;

/**
 * Dựng dữ liệu có cấu trúc schema.org (JSON-LD) cho Google và các công cụ AI.
 * Đặt ở PHP thay vì Blade vì Blade hiểu "@context" là một chỉ thị.
 */
class JsonLd
{
    public static function script(array $data): string
    {
        $json = json_encode(self::clean($data), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);

        return '<script type="application/ld+json">'.$json.'</script>';
    }

    public static function organization(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => config('site.company'),
            'alternateName' => config('site.brand'),
            'url' => url('/'),
            'logo' => ($logo = SiteImage::logo()) ? url(strtok($logo, '?')) : null,
            'email' => config('site.email'),
            'telephone' => config('site.phone'),
            'address' => config('site.address'),
            'taxID' => config('site.tax_code'),
            'sameAs' => array_values(array_filter([config('site.parent_site')])),
        ];
    }

    /** WebSite (chỉ ở trang chủ): giúp Google hiển thị đúng tên website trên kết quả tìm kiếm. */
    public static function website(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => config('site.brand'),
            'alternateName' => config('site.company'),
            'url' => route('home'),
            'inLanguage' => 'vi-VN',
            'publisher' => ['@type' => 'Organization', 'name' => config('site.company')],
        ];
    }

    /** @param array<int, array{name: string, url: string}> $trail */
    public static function breadcrumb(array $trail): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($trail)->values()->map(fn ($c, $i) => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $c['name'],
                'item' => $c['url'],
            ])->all(),
        ];
    }

    public static function product(Product $product): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'sku' => $product->sku,
            'image' => collect($product->images ?? [])->map(fn ($i) => self::absolute($i))->all(),
            'description' => strip_tags($product->short_description ?: $product->name),
            'brand' => $product->brand ? ['@type' => 'Brand', 'name' => $product->brand] : null,
            'category' => $product->category?->name,
            'additionalProperty' => collect($product->specs ?? [])
                ->map(fn ($v, $k) => ['@type' => 'PropertyValue', 'name' => $k, 'value' => $v])
                ->values()->all(),
            // Chỉ có Offer khi sản phẩm có giá công khai (không bao giờ đặt giá 0 / giá giả cho hàng "Liên hệ").
            // Đang flash sale: khai báo giá flash + hạn giá (priceValidUntil) = lúc chương trình kết thúc.
            'offers' => $product->price ? [
                '@type' => 'Offer',
                'priceCurrency' => 'VND',
                'price' => $product->currentPrice(),
                'priceValidUntil' => $product->flashItem()?->flashSale?->ends_at?->toDateString(),
                // Trang ghi "Giá tham khảo chưa VAT" -> khai báo đúng như vậy cho Google.
                'priceSpecification' => [
                    '@type' => 'UnitPriceSpecification',
                    'price' => $product->currentPrice(),
                    'priceCurrency' => 'VND',
                    'valueAddedTaxIncluded' => false,
                ],
                // Theo ô "Tình trạng" của từng sản phẩm trong trang quản trị ("Liên hệ" = không khai báo).
                'availability' => $product->stockSchema() ? 'https://schema.org/'.$product->stockSchema() : null,
                'url' => $product->url(),
                'seller' => ['@type' => 'Organization', 'name' => config('site.company')],
            ] : null,
        ];
    }

    public static function article(Post $post): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $post->title,
            'description' => $post->meta_description ?: $post->excerpt,
            'datePublished' => $post->published_at?->toIso8601String(),
            'dateModified' => $post->updated_at?->toIso8601String(),
            'image' => $post->image ? self::absolute($post->image) : null,
            'mainEntityOfPage' => $post->url(),
            'author' => ['@type' => 'Organization', 'name' => config('site.company')],
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('site.company'),
                'logo' => ($logo = SiteImage::logo()) ? ['@type' => 'ImageObject', 'url' => url(strtok($logo, '?'))] : null,
            ],
        ];
    }

    public static function faq(array $faqs): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => collect($faqs)->map(fn ($f) => [
                '@type' => 'Question',
                'name' => $f['q'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']],
            ])->all(),
        ];
    }

    public static function absolute(string $path): string
    {
        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return url(Str::startsWith($path, '/') ? $path : 'storage/'.$path);
    }

    /** Bỏ các trường rỗng để JSON-LD gọn và hợp lệ. */
    private static function clean(array $data): array
    {
        $out = [];
        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $v = self::clean($v);
            }
            if ($v === null || $v === '' || $v === []) {
                continue;
            }
            $out[$k] = $v;
        }

        return $out;
    }
}
