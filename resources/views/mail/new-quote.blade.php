<h2>Yêu cầu báo giá mới #{{ $quote->id }}</h2>
<table cellpadding="4">
    <tr><td><b>Công ty</b></td><td>{{ $quote->company }}</td></tr>
    <tr><td><b>MST</b></td><td>{{ $quote->tax_code }}</td></tr>
    <tr><td><b>Liên hệ</b></td><td>{{ $quote->contact_name }}</td></tr>
    <tr><td><b>Điện thoại</b></td><td>{{ $quote->phone }}</td></tr>
    <tr><td><b>Email</b></td><td>{{ $quote->email }}</td></tr>
    <tr><td><b>Khu vực giao</b></td><td>{{ $quote->delivery_area }}</td></tr>
    <tr><td><b>Nhu cầu</b></td><td>{{ ['once' => 'Một lần', 'monthly' => 'Hàng tháng', 'quarterly' => 'Hàng quý'][$quote->frequency] ?? '' }}</td></tr>
    <tr><td><b>Nguồn</b></td><td>{{ $quote->source }}</td></tr>
</table>

@if ($quote->items->isNotEmpty())
<h3>Sản phẩm</h3>
<table border="1" cellpadding="4" cellspacing="0">
    <tr><th>SKU</th><th>Tên</th><th>SL</th></tr>
    @foreach ($quote->items as $item)
        <tr><td>{{ $item->sku }}</td><td>{{ $item->name }}</td><td>{{ $item->quantity }}</td></tr>
    @endforeach
</table>
@endif

@if ($quote->note)
<h3>Ghi chú</h3>
<p>{!! nl2br(e($quote->note)) !!}</p>
@endif
