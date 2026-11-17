<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Banner extends Model
{
    /**
     * Ba vị trí ở đầu trang chủ. size = kích thước khuyến nghị, ratio = tỉ lệ khung hiển thị (CSS aspect-ratio),
     * max = số ảnh hiển thị tối đa (null = không giới hạn), minWidth = chiều ngang tối thiểu khi tải lên.
     */
    public const POSITIONS = [
        'slider' => ['label' => 'Banner chính (trình chiếu)', 'size' => '1200 × 460', 'ratio' => '1200/460', 'max' => null, 'minWidth' => 800,
            'hint' => 'Khung lớn bên trái, tự chuyển ảnh mỗi 5 giây.'],
        'side' => ['label' => 'Banner bên phải', 'size' => '600 × 230', 'ratio' => '600/230', 'max' => 2, 'minWidth' => 400,
            'hint' => 'Hai ảnh xếp dọc bên phải banner chính (hiện 2 ảnh đầu tiên).'],
        'promo' => ['label' => 'Hàng banner nhỏ bên dưới', 'size' => '600 × 400', 'ratio' => '600/400', 'max' => 4, 'minWidth' => 300,
            'hint' => 'Hàng ảnh nhỏ ngay dưới banner chính, tự chia cột đều: 3 ảnh = 3 cột, 4 ảnh = 4 cột (tối đa 4).'],
    ];

    protected $fillable = ['position', 'title', 'image', 'image_mobile', 'link', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    public function positionInfo(): array
    {
        return self::POSITIONS[$this->position] ?? self::POSITIONS['slider'];
    }

    public function imageUrl(?string $path = null): string
    {
        $path ??= $this->image;

        return Str::startsWith($path, ['http://', 'https://', '/']) ? $path : asset('storage/'.$path);
    }
}
