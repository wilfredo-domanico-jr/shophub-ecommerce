<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Services\StripeCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\Exception\ApiConnectionException;
use Tests\TestCase;

/**
 * Additional PaymentController coverage beyond StripeCheckoutTest: auth
 * guards on the pay/payment-status endpoints, and orders that are unpayable
 * because their status has already moved past "pending".
 */
class PaymentControllerEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.stripe.secret' => 'sk_test_fake']);
    }

    public function test_guest_cannot_pay_for_an_order(): void
    {
        $order = Order::factory()->card()->create();

        $this->postJson("/api/orders/{$order->id}/pay")->assertUnauthorized();
    }

    public function test_guest_cannot_view_payment_status(): void
    {
        $order = Order::factory()->card()->create();

        $this->getJson("/api/my/orders/{$order->order_number}/payment-status")->assertUnauthorized();
    }

    public function test_pay_endpoint_rejects_orders_that_are_no_longer_pending(): void
    {
        $user = User::factory()->create();

        foreach (['processing', 'shipped', 'delivered', 'cancelled'] as $status) {
            $order = Order::factory()->card()->create([
                'user_id' => $user->id,
                'status' => $status,
            ]);

            $this->actingAs($user, 'sanctum')
                ->postJson("/api/orders/{$order->id}/pay")
                ->assertUnprocessable()
                ->assertJsonValidationErrors('order');

            $this->assertNull($order->fresh()->stripe_session_id);
        }
    }

    public function test_pay_endpoint_returns_a_clean_error_when_stripe_api_call_fails(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->card()->create(['user_id' => $user->id]);

        $this->mock(StripeCheckoutService::class, function ($mock) {
            $mock->shouldReceive('createSession')
                ->once()
                ->andThrow(ApiConnectionException::factory('Could not connect to Stripe.'));
        });

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/orders/{$order->id}/pay")
            ->assertStatus(502)
            ->assertJsonPath('message', 'Could not start the payment. Please try again in a moment.');

        $this->assertNull($order->fresh()->stripe_session_id);
    }

    public function test_pay_endpoint_returns_404_for_a_nonexistent_order(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/orders/999999/pay')
            ->assertNotFound();
    }

    public function test_payment_status_returns_404_for_an_unknown_order_number(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/my/orders/SHP-DOES-NOT-EXIST/payment-status')
            ->assertNotFound();
    }
}
