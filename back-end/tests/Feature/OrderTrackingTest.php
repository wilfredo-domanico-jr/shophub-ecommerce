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
