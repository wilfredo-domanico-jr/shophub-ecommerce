<x-mail::message>
# Thanks for your order, {{ $order->customer_name }}!

Your order has been received and is being prepared. Here's a summary for your records.

<x-mail::panel>
**Order Number:** {{ $order->order_number }}<br>
**Order Date:** {{ $order->created_at->format('F j, Y') }}<br>
**Payment Method:** {{ $order->payment_method }}
</x-mail::panel>

<x-mail::table>
| Item | Qty | Price | Subtotal |
| :--- | :-: | ----: | -------: |
@foreach ($order->items as $item)
| {{ $item->product_name }} | {{ $item->quantity }} | ₱{{ number_format($item->product_price, 2) }} | ₱{{ number_format($item->subtotal, 2) }} |
@endforeach
| | | **Subtotal** | ₱{{ number_format($order->subtotal, 2) }} |
| | | **Shipping** | ₱{{ number_format($order->shipping_fee, 2) }} |
| | | **Total** | **₱{{ number_format($order->total, 2) }}** |
</x-mail::table>

**Shipping Address**<br>
{{ $order->shipping_address }}

You can track your order anytime using your order number and email address.

Thanks for shopping with us,<br>
{{ config('app.name') }}
</x-mail::message>
