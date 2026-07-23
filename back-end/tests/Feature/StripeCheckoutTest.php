<?php

namespace Tests\Feature;

use App\Mail\OrderConfirmationMail;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\StripeCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Stripe\Checkout\Session;
use Tests\TestCase;

class StripeCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.stripe.secret' => 'sk_test_fake']);
    }

    private function checkoutPayload(Product $product, string $paymentMethod): array
    {
        return [
            'customer_name' => 'Juan Dela Cruz',
            'customer_email' => 'juan@example.com',
            'customer_phone' => '09171234567',
            'shipping_address' => '123 Mabini St, Manila',
            'payment_method' => $paymentMethod,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ];
    }

    public function test_card_checkout_creates_unpaid_order_without_confirmation_mail(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'category_id' => Category::factory(),
            'price' => 500,
            'stock_quantity' => 10,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/orders', $this->checkoutPayload($product, Order::PAYMENT_CARD));

        $response->assertCreated();
        $response->assertJsonPath('payment_method', Order::PAYMENT_CARD);
        $response->assertJsonPath('payment_status', 'unpaid');

        $this->assertSame(8, $product->fresh()->stock_quantity);
        Mail::assertNothingQueued();
    }

    public function test_cod_checkout_still_queues_confirmation_mail(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'category_id' => Category::factory(),
            'price' => 500,
            'stock_quantity' => 10,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/orders', $this->checkoutPayload($product, Order::PAYMENT_COD));

        $response->assertCreated();
        $response->assertJsonPath('payment_method', Order::PAYMENT_COD);
        Mail::assertQueued(OrderConfirmationMail::class);
    }

    public function test_card_checkout_is_rejected_when_stripe_is_not_configured(): void
    {
        config(['services.stripe.secret' => null]);
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'category_id' => Category::factory(),
            'price' => 500,
            'stock_quantity' => 10,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/orders', $this->checkoutPayload($product, Order::PAYMENT_CARD))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('payment_method');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_pay_endpoint_returns_checkout_url_and_stores_session_id(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->card()->create(['user_id' => $user->id]);

        $this->mock(StripeCheckoutService::class, function ($mock) {
            $mock->shouldReceive('createSession')->once()->andReturn(Session::constructFrom([
                'id' => 'cs_test_123',
                'url' => 'https://checkout.stripe.com/c/pay/cs_test_123',
            ]));
        });

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/orders/{$order->id}/pay")
            ->assertOk()
            ->assertJsonPath('url', 'https://checkout.stripe.com/c/pay/cs_test_123');

        $this->assertSame('cs_test_123', $order->fresh()->stripe_session_id);
    }

    public function test_pay_endpoint_hides_other_users_orders(): void
    {
        $order = Order::factory()->card()->create(['user_id' => User::factory()]);

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->postJson("/api/orders/{$order->id}/pay")
            ->assertNotFound();
    }

    public function test_pay_endpoint_rejects_paid_and_cod_orders(): void
    {
        $user = User::factory()->create();
        $paid = Order::factory()->card()->create(['user_id' => $user->id, 'payment_status' => 'paid']);
        $cod = Order::factory()->create(['user_id' => $user->id]);

        foreach ([$paid, $cod] as $order) {
            $this->actingAs($user, 'sanctum')
                ->postJson("/api/orders/{$order->id}/pay")
                ->assertUnprocessable();
        }
    }

    public function test_payment_status_endpoint_is_owner_scoped(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->card()->create(['user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/my/orders/{$order->order_number}/payment-status")
            ->assertOk()
            ->assertJson([
                'id' => $order->id,
                'order_number' => $order->order_number,
                'payment_status' => 'unpaid',
                'paid_at' => null,
            ]);

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson("/api/my/orders/{$order->order_number}/payment-status")
            ->assertNotFound();
    }
}
