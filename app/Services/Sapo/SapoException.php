<?php

namespace App\Services\Sapo;

use RuntimeException;

class SapoException extends RuntimeException
{
    public function __construct(string $action, public readonly int $status, string $body)
    {
        $hint = match (true) {
            $status === 401 => 'Sai API Key / Secret.',
            $status === 403 => 'Ứng dụng riêng chưa được cấp quyền cho chức năng này.',
            $status === 404 => 'Không tìm thấy (sai tên cửa hàng hoặc đường dẫn API).',
            $status === 422 => 'Dữ liệu không hợp lệ theo Sapo.',
            $status === 429 => 'Gọi API quá nhiều, sẽ thử lại sau.',
            $status >= 500 => 'Máy chủ Sapo lỗi, sẽ thử lại sau.',
            default => '',
        };
        parent::__construct(trim("{$action} thất bại (HTTP {$status}). {$hint} ".mb_substr($body, 0, 500)));
    }
}
