<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Tin nhắn khách gửi từ trang Liên hệ (xem trong Quản trị > Liên hệ). */
class ContactMessage extends Model
{
    /** Chủ đề khách chọn ở form (khóa lưu DB => nhãn hiển thị). */
    public const TOPICS = [
        'quote' => 'Yêu cầu báo giá',
        'advice' => 'Tư vấn sản phẩm',
        'partner' => 'Hợp tác doanh nghiệp',
        'order' => 'Hỗ trợ đơn hàng',
    ];

    protected $fillable = ['topic', 'name', 'phone', 'email', 'content', 'is_read', 'ip'];

    protected $casts = ['is_read' => 'boolean'];

    public function topicLabel(): string
    {
        return self::TOPICS[$this->topic] ?? $this->topic;
    }
}
