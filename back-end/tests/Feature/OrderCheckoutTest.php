<?php

namespace Tests\Feature;

use App\Mail\OrderConfirmationMail;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrderCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_place_an_order_and_stock_is_decremented(): void
    {
        Mail::fake();

        $product = Product::factory()->create([
            'category_id' => Category::factory(),
            'price' => 500,
            'stock_quantity' => 10,
        ]);

        $response = $this->postJson('/api/orders', [
            'customer_name' => 'Juan Dela Cruz',
            'customer_email' => 'juan@example.com',
            'customer_phone' => '09171234567',
            'shipping_address' => '123 Mabini St, Manila',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('total', '1000.00');

        $product->refresh();
        $this->assertSame(8, $product->stock_quantity);
        $this->assertSame(2, $product->sold_count);

        Mail::assertQueued(OrderConfirmationMail::class);
    }

    public function test_checkout_fails_when_quantity_exceeds_stock(): void
    {
        Mail::fake();

        $product = Product::factory()->create([
            'category_id' => Category::factory(),
            'stock_quantity' => 1,
        ]);

        $response = $this->postJson('/api/orders', [
            'customer_name' => 'Juan Dela Cruz',
            'customer_email' => 'juan@example.com',
            'customer_phone' => '09171234567',
            'shipping_address' => '123 Mabini St, Manila',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5],
            ],
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('items');

        $product->refresh();
        $this->assertSame(1, $product->stock_quantity);

        Mail::assertNothingQueued();
    }
}
