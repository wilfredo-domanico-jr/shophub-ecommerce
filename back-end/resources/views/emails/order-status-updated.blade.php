@php
$statusColor = match ($order->status) {
    'delivered' => '#16a34a',
    'cancelled' => '#dc2626',
    default => '#ff6b35',
};

$statusMessage = match ($order->status) {
    'processing' => 'We\'re getting your order ready.',
    'shipped' => 'Your order is on its way!',
    'delivered' => 'Your order has been delivered. Enjoy!',
    'cancelled' => 'This order has been cancelled.',
    default => 'We\'ll notify you again as soon as it progresses.',
};
@endphp
<x-mail::message>
# Order {{ $order->order_number }} update

Hi {{ $order->customer_name }}, your order status has changed to:

## <span style="color: {{ $statusColor }};">{{ ucfirst($order->status) }}</span>

{{ $statusMessage }}

<x-mail::table>
| Item | Qty | Subtotal |
| :--- | :-: | -------: |
@foreach ($order->items as $item)
| {{ $item->product_name }} | {{ $item->quantity }} | ₱{{ number_format($item->subtotal, 2) }} |
@endforeach
| | **Total** | **₱{{ number_format($order->total, 2) }}** |
</x-mail::table>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
