<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Redirect extends Model
{
    protected $fillable = ['from_path', 'to_path', 'hits', 'last_hit_at'];

    protected $casts = ['last_hit_at' => 'datetime'];

    /** Chuẩn hóa về dạng "/a/b": bỏ domain, query, dấu / cuối. */
    public static function normalize(string $path): string
    {
        $path = parse_url(trim($path), PHP_URL_PATH) ?: '/';

        return '/'.trim($path, '/');
    }

    /**
     * Ghi một chuyển hướng 301 từ $from sang $to.
     * - Các chuyển hướng cũ đang trỏ tới $from được trỏ thẳng tới $to (tránh chuỗi 301 nối nhau).
     * - Xóa chuyển hướng nào đang đi từ $to (đường dẫn này nay đã có nội dung thật, tránh vòng lặp).
     */
    public static function record(string $from, string $to): void
    {
        $from = self::normalize($from);
        $to = self::normalize($to);
        if ($from === $to) {
            return;
        }

        static::where('from_path', $to)->delete();
        static::where('to_path', $from)->update(['to_path' => $to]);
        static::whereColumn('from_path', 'to_path')->delete();
        static::updateOrCreate(['from_path' => $from], ['to_path' => $to]);
    }

    /** Đường dẫn đã có nội dung thật (vừa tạo / khôi phục) -> bỏ chuyển hướng đi từ nó. */
    public static function release(string $path): void
    {
        static::where('from_path', self::normalize($path))->delete();
    }
}
