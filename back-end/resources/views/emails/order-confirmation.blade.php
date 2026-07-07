<x-mail::message>
# Thanks for your order, {{ $order->customer_name }}!

Your order **{{ $order->order_number }}** has been received and is being prepared.

<x-mail::table>
| Item | Qty | Price | Subtotal |
| :--- | :-: | ----: | -------: |
@foreach ($order->items as $item)
| {{ $item->product_name }} | {{ $item->quantity }} | ₱{{ number_format($item->product_price, 2) }} | ₱{{ number_format($item->subtotal, 2) }} |
@endforeach
</x-mail::table>

**Subtotal:** ₱{{ number_format($order->subtotal, 2) }}
**Shipping:** ₱{{ number_format($order->shipping_fee, 2) }}
**Total:** ₱{{ number_format($order->total, 2) }}

**Payment Method:** {{ $order->payment_method }}
**Shipping Address:** {{ $order->shipping_address }}

You can track your order anytime using your order number and email address.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
