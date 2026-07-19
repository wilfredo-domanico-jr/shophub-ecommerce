<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class VariantCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->user = User::factory()->create();
    }

    private function payload(array $items): array
    {
        return [
            'customer_name' => 'Juan Dela Cruz',
            'customer_email' => 'juan@example.com',
            'customer_phone' => '09171234567',
            'shipping_address' => '123 Mabini St, Manila',
            'items' => $items,
        ];
    }

    private function createShirt(): Product
    {
        $product = Product::factory()->create([
            'category_id' => Category::factory(),
            'name' => 'Classic Tee',
            'price' => 500,
            'stock_quantity' => 15,
            'options' => [
                ['name' => 'Color', 'values' => ['Red', 'Blue']],
                ['name' => 'Size', 'values' => ['S', 'M']],
            ],
        ]);

        $product->variants()->createMany([
            ['option_values' => ['Color' => 'Red', 'Size' => 'S'], 'stock_quantity' => 10],
            ['option_values' => ['Color' => 'Blue', 'Size' => 'M'], 'price' => 650, 'stock_quantity' => 5],
        ]);

        return $product->fresh(['variants']);
    }

    public function test_variant_checkout_decrements_both_stocks_and_snapshots_the_variant(): void
    {
        $product = $this->createShirt();
        $redS = $product->variants->first();

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/orders', $this->payload([
            ['product_id' => $product->id, 'variant_id' => $redS->id, 'quantity' => 2],
        ]));

        $response->assertCreated();
        $response->assertJsonPath('total', '1000.00'); // inherits product price
        $response->assertJsonPath('items.0.variant_label', 'Red / S');
        $response->assertJsonPath('items.0.product_variant_id', $redS->id);

        $this->assertSame(8, $redS->fresh()->stock_quantity);
        $product->refresh();
        $this->assertSame(13, $product->stock_quantity);
        $this->assertSame(2, $product->sold_count);
    }

    public function test_variant_price_override_is_used(): void
    {
        $product = $this->createShirt();
        $blueM = $product->variants->last();

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/orders', $this->payload([
            ['product_id' => $product->id, 'variant_id' => $blueM->id, 'quantity' => 1],
        ]));

        $response->assertCreated();
        $response->assertJsonPath('total', '650.00');
        $response->assertJsonPath('items.0.product_price', '650.00');
    }

    public function test_variant_product_requires_a_variant_id(): void
    {
        $product = $this->createShirt();

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/orders', $this->payload([
            ['product_id' => $product->id, 'quantity' => 1],
        ]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('items');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_variant_from_another_product_is_rejected(): void
    {
        $product = $this->createShirt();
        $foreign = ProductVariant::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/orders', $this->payload([
            ['product_id' => $product->id, 'variant_id' => $foreign->id, 'quantity' => 1],
        ]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('items');
    }

    public function test_variant_id_on_flat_product_is_rejected(): void
    {
        $flat = Product::factory()->create([
            'category_id' => Category::factory(),
            'stock_quantity' => 10,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/orders', $this->payload([
            ['product_id' => $flat->id, 'variant_id' => 999, 'quantity' => 1],
        ]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('items');
    }

    public function test_out_of_stock_variant_fails_and_rolls_back(): void
    {
        $product = $this->createShirt();
        $blueM = $product->variants->last(); // stock 5

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/orders', $this->payload([
            ['product_id' => $product->id, 'variant_id' => $blueM->id, 'quantity' => 6],
        ]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('items');

        $this->assertSame(5, $blueM->fresh()->stock_quantity);
        $this->assertSame(15, $product->fresh()->stock_quantity);
        $this->assertDatabaseCount('orders', 0);
        Mail::assertNothingQueued();
    }

    public function test_mixed_cart_with_flat_and_variant_products_works(): void
    {
        $product = $this->createShirt();
        $redS = $product->variants->first();
        $flat = Product::factory()->create([
            'category_id' => Category::factory(),
            'price' => 100,
            'stock_quantity' => 10,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/orders', $this->payload([
            ['product_id' => $product->id, 'variant_id' => $redS->id, 'quantity' => 1],
            ['product_id' => $flat->id, 'quantity' => 2],
        ]));

        $response->assertCreated();
        $response->assertJsonPath('total', '700.00');
        $response->assertJsonPath('items.1.variant_label', null);
        $this->assertSame(8, $flat->fresh()->stock_quantity);
    }
}
