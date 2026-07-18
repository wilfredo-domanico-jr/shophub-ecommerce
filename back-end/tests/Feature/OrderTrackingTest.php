<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_track_order_with_matching_email(): void
    {
        $order = Order::factory()->create(['customer_email' => 'juan@example.com']);

        $response = $this->postJson('/api/orders/track', [
            'order_number' => $order->order_number,
            'email' => 'juan@example.com',
        ]);

        $response->assertOk();
        $response->assertJsonPath('order_number', $order->order_number);
    }

    public function test_tracking_response_excludes_personal_details(): void
    {
        $order = Order::factory()->create(['customer_email' => 'juan@example.com']);

        $response = $this->postJson('/api/orders/track', [
            'order_number' => $order->order_number,
            'email' => 'juan@example.com',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['order_number', 'status', 'created_at', 'total', 'items']);
        $response->assertJsonMissingPath('customer_phone');
        $response->assertJsonMissingPath('shipping_address');
        $response->assertJsonMissingPath('customer_email');
        $response->assertJsonMissingPath('notes');
    }

    public function test_tracking_fails_with_wrong_email(): void
    {
        $order = Order::factory()->create(['customer_email' => 'juan@example.com']);

        $response = $this->postJson('/api/orders/track', [
            'order_number' => $order->order_number,
            'email' => 'someone-else@example.com',
        ]);

        $response->assertNotFound();
    }
}
