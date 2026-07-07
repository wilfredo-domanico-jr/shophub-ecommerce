<x-mail::message>
# Order {{ $order->order_number }} update

Hi {{ $order->customer_name }}, your order status has changed to:

## {{ ucfirst($order->status) }}

<x-mail::table>
| Item | Qty | Subtotal |
| :--- | :-: | -------: |
@foreach ($order->items as $item)
| {{ $item->product_name }} | {{ $item->quantity }} | ₱{{ number_format($item->subtotal, 2) }} |
@endforeach
</x-mail::table>

**Total:** ₱{{ number_format($order->total, 2) }}

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
