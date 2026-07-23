<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\StripeCheckoutService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function pay(Request $request, Order $order, StripeCheckoutService $stripe)
    {
        // 404 (not 403) so order ids can't be probed for existence.
        abort_unless($order->user_id === $request->user()->id, 404);

        if (! $order->isPayableByCard()) {
            throw ValidationException::withMessages([
                'order' => 'This order can no longer be paid online.',
            ]);
        }

        $session = $stripe->createSession($order);

        // Each call mints a fresh session ("Pay now" retries after an
        // abandoned redirect), so the stored id is always the latest one.
        $order->update(['stripe_session_id' => $session->id]);

        return response()->json(['url' => $session->url]);
    }

    public function paymentStatus(Request $request, string $orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return response()->json([
            'id' => $order->id,
            'order_number' => $order->order_number,
            'payment_status' => $order->payment_status,
            'paid_at' => $order->paid_at,
        ]);
    }
}
