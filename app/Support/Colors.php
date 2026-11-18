<?php

namespace App\Support;

use Illuminate\Support\Str;

/** Đổi tên màu tiếng Việt (từ Sapo / admin) sang mã màu để vẽ chấm màu. */
class Colors
{
    /** Khóa = tên màu dạng slug. Tên ghép xếp trước tên đơn ("xanh-la" trước "xanh"). */
    private const MAP = [
        'xanh-la-cay' => '#2e9e4f', 'xanh-la' => '#2e9e4f', 'xanh-reu' => '#5b6b2f', 'xanh-ngoc' => '#1fa5a0',
        'xanh-duong' => '#1e63c6', 'xanh-nuoc-bien' => '#1e63c6', 'xanh-da-troi' => '#3d9be9', 'xanh-navy' => '#1f2f5c',
        'xanh-den' => '#1f2f5c', 'xanh-lam' => '#1e63c6', 'xanh-mint' => '#8fd8c4', 'xanh' => '#1e63c6',
        'do-do' => '#8e1b22', 'do-dun' => '#8e1b22', 'do' => '#d1262d', 'hong-dam' => '#d63b7b', 'hong-nhat' => '#f6b8cf',
        'hong' => '#f2a2bf', 'cam' => '#f28c28', 'vang-dong' => '#c9a227', 'vang' => '#f5c518', 'tim' => '#7b4bb7',
        'nau' => '#7a4a2a', 'be' => '#e8d9bf', 'kem' => '#f3ead2', 'trang' => '#ffffff', 'den' => '#1b1b1b',
        'xam-dam' => '#4b5563', 'xam-nhat' => '#c7ccd3', 'xam' => '#8a929c', 'bac' => '#c0c4c9', 'ghi' => '#8a929c',
        'trong-suot' => '#eef2f5',
    ];

    /** Bảng màu cho JS (xem trước chấm màu trong admin, chọn màu trên trang sản phẩm). */
    public static function all(): array
    {
        return self::MAP;
    }

    public static function hex(?string $name): ?string
    {
        $slug = Str::slug((string) $name);
        if ($slug === '') {
            return null;
        }
        foreach (self::MAP as $key => $hex) {
            if ($slug === $key || str_starts_with($slug, $key.'-')) {
                return $hex;
            }
        }

        return null;
    }
}
