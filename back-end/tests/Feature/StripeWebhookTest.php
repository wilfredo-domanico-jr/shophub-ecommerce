<?php

namespace Tests\Feature;

use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const WEBHOOK_SECRET = 'whsec_test_secret';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.stripe.webhook_secret' => self::WEBHOOK_SECRET]);
    }

    /**
     * Posts a payload signed the way Stripe signs deliveries, so the real
     * signature-verification path runs.
     */
    private function postWebhook(array $payload, ?string $secret = self::WEBHOOK_SECRET): TestResponse
    {
        $json = json_encode($payload);
        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$json}", $secret ?? '');

        return $this->call('POST', '/api/webhooks/stripe', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
            'CONTENT_TYPE' => 'application/json',
        ], $json);
    }

    private function completedSessionEvent(Order $order): array
    {
        return [
            'id' => 'evt_test_1',
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
        ];
    }

    public function test_completed_checkout_session_marks_order_paid_and_queues_mail(): void
    {
        Mail::fake();
        $order = Order::factory()->card()->create();

        $this->postWebhook($this->completedSessionEvent($order))->assertOk();

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertNotNull($order->paid_at);
        $this->assertSame('cs_test_123', $order->stripe_session_id);
        $this->assertSame('pi_test_456', $order->stripe_payment_intent_id);
        Mail::assertQueued(OrderConfirmationMail::class);
    }

    public function test_duplicate_delivery_is_idempotent(): void
    {
        Mail::fake();
        $order = Order::factory()->card()->create();
        $event = $this->completedSessionEvent($order);

        $this->postWebhook($event)->assertOk();
        $this->postWebhook($event)->assertOk();

        Mail::assertQueuedCount(1);
    }

    public function test_invalid_signature_is_rejected(): void
    {
        Mail::fake();
        $order = Order::factory()->card()->create();

        $this->postWebhook($this->completedSessionEvent($order), secret: 'whsec_wrong')
            ->assertStatus(400);

        $this->assertSame('unpaid', $order->fresh()->payment_status);
        Mail::assertNothingQueued();
    }

    public function test_unpaid_session_does_not_mark_order_paid(): void
    {
        Mail::fake();
        $order = Order::factory()->card()->create();
        $event = $this->completedSessionEvent($order);
        $event['data']['object']['payment_status'] = 'unpaid';

        $this->postWebhook($event)->assertOk();

        $this->assertSame('unpaid', $order->fresh()->payment_status);
        Mail::assertNothingQueued();
    }

    public function test_unmatched_order_and_unhandled_events_return_200(): void
    {
        Mail::fake();

        $this->postWebhook([
            'id' => 'evt_test_2',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => ['object' => ['id' => 'cs_none', 'object' => 'checkout.session', 'payment_status' => 'paid', 'metadata' => []]],
        ])->assertOk();

        $this->postWebhook([
            'id' => 'evt_test_3',
            'object' => 'event',
            'type' => 'payment_intent.created',
            'data' => ['object' => ['id' => 'pi_x', 'object' => 'payment_intent']],
        ])->assertOk();

        Mail::assertNothingQueued();
    }
}
