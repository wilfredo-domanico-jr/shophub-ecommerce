<?php

namespace App\Services;

use App\Models\Order;
use Stripe\Checkout\Session;
use Stripe\StripeClient;

class StripeCheckoutService
{
    public function createSession(Order $order): Session
    {
        $stripe = new StripeClient(config('services.stripe.secret'));
        $frontendUrl = rtrim(config('app.frontend_url'), '/');

        return $stripe->checkout->sessions->create([
            'mode' => 'payment',
            'customer_email' => $order->customer_email,
            'client_reference_id' => $order->order_number,
            'metadata' => ['order_id' => $order->id],
            // A single consolidated line item: the order total already carries
            // voucher discounts, so itemizing would need Stripe Coupons and
            // could drift from orders.total by rounding.
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => 'php',
                    'unit_amount' => (int) round($order->total * 100),
                    'product_data' => [
                        'name' => "ShopHub Order {$order->order_number}",
                    ],
                ],
            ]],
            'success_url' => $frontendUrl."/checkout/return?order={$order->order_number}&session_id={CHECKOUT_SESSION_ID}",
            'cancel_url' => $frontendUrl."/checkout/return?order={$order->order_number}&cancelled=1",
        ]);
    }
}
