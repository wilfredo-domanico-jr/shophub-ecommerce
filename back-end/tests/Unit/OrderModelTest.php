<?php

namespace Tests\Unit;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_number_is_auto_generated_on_create(): void
    {
        $order = Order::factory()->create(['order_number' => null]);

        $this->assertNotEmpty($order->order_number);
        $this->assertMatchesRegularExpression('/^SHP-\d{14}-[A-Z0-9]{4}$/', $order->order_number);
    }

    public function test_explicit_order_number_is_not_overwritten(): void
    {
        $order = Order::factory()->create(['order_number' => 'SHP-CUSTOM-0001']);

        $this->assertSame('SHP-CUSTOM-0001', $order->order_number);
    }

    public function test_generated_order_numbers_are_unique(): void
    {
        $numbers = collect(range(1, 5))
            ->map(fn () => Order::factory()->create(['order_number' => null])->order_number);

        $this->assertSame($numbers->count(), $numbers->unique()->count());
    }
}
