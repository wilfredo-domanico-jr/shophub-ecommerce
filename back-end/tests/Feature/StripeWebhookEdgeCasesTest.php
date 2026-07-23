<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Additional StripeWebhookController coverage beyond StripeWebhookTest:
 * a missing signature header and a malformed JSON body, both of which take
 * a different exception path than the "wrong secret" case already covered.
 */
class StripeWebhookEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    private const WEBHOOK_SECRET = 'whsec_test_secret';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.stripe.webhook_secret' => self::WEBHOOK_SECRET]);
    }

    public function test_missing_webhook_secret_fails_closed_instead_of_verifying_against_empty_string(): void
    {
        Mail::fake();
        config(['services.stripe.webhook_secret' => null]);
        $order = Order::factory()->card()->create();

        $json = json_encode([
            'id' => 'evt_test_no_secret',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_123',
                    'object' => 'checkout.session',
                    'payment_status' => 'paid',
                    'payment_intent' => 'pi_test_456',
                    'client_reference_id' => $order->order_number,
                    'metadata' => ['order_id' => $order->id],
                ],
            ],
        ]);

        // No real secret to sign with — an empty-string signature is exactly
        // what an attacker exploiting a misconfigured secret would send.
        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$json}", '');

        $this->call('POST', '/api/webhooks/stripe', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
            'CONTENT_TYPE' => 'application/json',
        ], $json)->assertStatus(500);

        $this->assertSame('unpaid', $order->fresh()->payment_status);
        Mail::assertNothingQueued();
    }

    public function test_missing_signature_header_is_rejected(): void
    {
        Mail::fake();
        $order = Order::factory()->card()->create();

        $json = json_encode([
            'id' => 'evt_test_missing_sig',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_123',
                    'object' => 'checkout.session',
                    'payment_status' => 'paid',
                    'payment_intent' => 'pi_test_456',
                    'client_reference_id' => $order->order_number,
                    'metadata' => ['order_id' => $order->id],
                ],
            ],
        ]);

        $this->call('POST', '/api/webhooks/stripe', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $json)->assertStatus(400);

        $this->assertSame('unpaid', $order->fresh()->payment_status);
        Mail::assertNothingQueued();
    }

    public function test_malformed_json_body_is_rejected(): void
    {
        Mail::fake();

        $body = 'not-valid-json';
        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$body}", self::WEBHOOK_SECRET);

        $this->call('POST', '/api/webhooks/stripe', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
            'CONTENT_TYPE' => 'application/json',
        ], $body)->assertStatus(400);

        Mail::assertNothingQueued();
    }

    public function test_already_paid_order_is_not_overwritten_by_a_stale_event(): void
    {
        Mail::fake();
        $order = Order::factory()->card()->create([
            'payment_status' => 'paid',
            'stripe_session_id' => 'cs_original',
            'stripe_payment_intent_id' => 'pi_original',
        ]);

        $json = json_encode([
            'id' => 'evt_test_stale',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_new_retry',
                    'object' => 'checkout.session',
                    'payment_status' => 'paid',
                    'payment_intent' => 'pi_new_retry',
                    'client_reference_id' => $order->order_number,
                    'metadata' => ['order_id' => $order->id],
                ],
            ],
        ]);
        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$json}", self::WEBHOOK_SECRET);

        $this->call('POST', '/api/webhooks/stripe', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
            'CONTENT_TYPE' => 'application/json',
        ], $json)->assertOk();

        $order->refresh();
        $this->assertSame('cs_original', $order->stripe_session_id);
        $this->assertSame('pi_original', $order->stripe_payment_intent_id);
        Mail::assertNothingQueued();
    }
}
