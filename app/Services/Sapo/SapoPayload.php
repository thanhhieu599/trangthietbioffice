<?php

namespace App\Services\Sapo;

use App\Models\QuoteRequest;
use App\Models\SapoVariant;

/**
 * Chuyển dữ liệu web sang định dạng Sapo (tên trường theo tài liệu support.sapo.vn/customer và order API).
 * Sapo không có trường phường/xã riêng: phường/xã đặt ở address2, tỉnh/thành ở province và city.
 */
class SapoPayload
{
    public static function customer(array $c): array
    {
        $note = collect([
            filled($c['company'] ?? null) ? 'Công ty: '.$c['company'] : null,
            filled($c['tax_code'] ?? null) ? 'MST: '.$c['tax_code'] : null,
            'Nguồn: website văn phòng phẩm',
        ])->filter()->implode(' | ');

        return array_filter([
            // Họ tên tiếng Việt để nguyên trong first_name (không tách họ/tên để tránh đảo thứ tự khi hiển thị).
            'first_name' => $c['name'],
            'email' => $c['email'],
            'phone' => $c['phone'] ?? null,
            'note' => $note,
            'tags' => config('services.sapo.tags'),
            'addresses' => filled($c['address'] ?? null) ? [self::address($c)] : null,
        ], fn ($v) => $v !== null && $v !== '');
    }

    public static function address(array $c): array
    {
        return array_filter([
            'first_name' => $c['name'],
            'company' => $c['company'] ?? null,
            'phone' => $c['phone'] ?? null,
            'address1' => $c['address'] ?? null,
            'address2' => $c['ward'] ?? null,
            'city' => $c['province'] ?? null,
            'province' => $c['province'] ?? null,
            'country' => config('services.sapo.country', 'Vietnam'),
        ], fn ($v) => filled($v));
    }

    /** Thông tin liên hệ của đơn (dùng tạo/cập nhật khách bên Sapo). */
    public static function customerFromQuote(QuoteRequest $q): array
    {
        return [
            'name' => $q->contact_name, 'email' => $q->email, 'phone' => $q->phone,
            'company' => $q->company, 'tax_code' => $q->tax_code,
            'address' => $q->address, 'ward' => $q->ward, 'province' => $q->province,
        ];
    }

    /**
     * Đơn hàng Sapo từ đơn trên web. Mã đã ghép được -> variant_id; chưa ghép -> dòng tự do (tên + giá web, 0 nếu "Liên hệ").
     *
     * @return array{order: array, unmatched: list<string>}
     */
    public static function order(QuoteRequest $q, int $customerId): array
    {
        $variants = SapoVariant::whereIn('sku', $q->items->pluck('sku')->filter())->pluck('variant_id', 'sku');
        $unmatched = [];

        $lines = $q->items->map(function ($item) use ($variants, &$unmatched) {
            if ($id = $variants[$item->sku] ?? null) {
                return ['variant_id' => (int) $id, 'quantity' => (int) $item->quantity];
            }
            $unmatched[] = (string) $item->sku;

            return [
                'title' => trim($item->name.($item->sku ? ' ('.$item->sku.')' : '')),
                'price' => (int) ($item->unit_price ?? $item->product?->price ?? 0),
                'quantity' => (int) $item->quantity,
            ];
        })->values()->all();

        if (! $lines) { // Đơn chỉ ghi danh sách trong ô ghi chú
            $lines[] = ['title' => 'Yêu cầu báo giá theo ghi chú '.$q->code(), 'price' => 0, 'quantity' => 1];
        }

        $frequency = ['once' => 'Mua một lần', 'monthly' => 'Định kỳ hàng tháng', 'quarterly' => 'Định kỳ hàng quý'][$q->frequency] ?? null;
        $note = collect([
            'Đơn từ website: '.$q->code().($q->user_id ? ' (khách có tài khoản)' : ' (khách vãng lai)'),
            $q->company ? 'Công ty: '.$q->company : null,
            $q->tax_code ? 'MST: '.$q->tax_code : null,
            $frequency ? 'Nhu cầu: '.$frequency : null,
            $unmatched ? 'Mã chưa ghép với Sapo (dòng tự do, cần kiểm tra giá): '.implode(', ', $unmatched) : null,
            ($flash = $q->items->where('is_flash_sale', true))->isNotEmpty()
                ? 'GIÁ FLASH SALE: '.$flash->map(fn ($i) => $i->sku.' = '.number_format((int) $i->unit_price, 0, ',', '.').'đ x '.$i->quantity)->implode('; ')
                : null,
            $q->note ? 'Ghi chú khách: '.$q->note : null,
        ])->filter()->implode("\n");

        $address = self::address(self::customerFromQuote($q));

        return ['unmatched' => $unmatched, 'order' => array_filter([
            'customer' => ['id' => $customerId],
            'email' => $q->email,
            'line_items' => $lines,
            'shipping_address' => $address ?: null,
            'billing_address' => $address ?: null,
            'note' => $note,
            'tags' => config('services.sapo.tags'),
            'send_receipt' => false,
            'send_fulfillment_receipt' => false,
        ], fn ($v) => $v !== null)];
    }
}
