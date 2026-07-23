<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                $request->header('Stripe-Signature', ''),
                config('services.stripe.webhook_secret') ?? ''
            );
        } catch (\UnexpectedValueException|SignatureVerificationException) {
            return response()->json(['message' => 'Invalid signature.'], 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $this->handleCheckoutCompleted($event->data->object);
        }

        return response()->json(['received' => true]);
    }

    private function handleCheckoutCompleted(object $session): void
    {
        $order = Order::find($session->metadata->order_id ?? null)
            ?? Order::where('order_number', $session->client_reference_id ?? '')->first();

        // 200 even when unmatched — a 4xx would make Stripe retry forever
        // (e.g. `stripe trigger` fixtures reference no real order).
        if (! $order) {
            Log::warning('Stripe webhook: no order for checkout session.', ['session_id' => $session->id]);

            return;
        }

        // Hosted card payments confirm synchronously; anything not yet paid
        // (async methods) will arrive later as its own event.
        if ($session->payment_status !== 'paid') {
            return;
        }

        // Stripe redelivers events — the first paid write wins.
        if ($order->payment_status === 'paid') {
            return;
        }

        $order->update([
            'payment_status' => 'paid',
            'paid_at' => now(),
            'stripe_session_id' => $session->id,
            'stripe_payment_intent_id' => $session->payment_intent,
        ]);

        Mail::to($order->customer_email)->queue(new OrderConfirmationMail($order->load('items')));
    }
}
