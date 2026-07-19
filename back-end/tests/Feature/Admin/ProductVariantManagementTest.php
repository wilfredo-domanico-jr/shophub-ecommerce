<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductVariantManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->category = Category::factory()->create();
    }

    private function basePayload(array $overrides = []): array
    {
        return array_merge([
            'category_id' => $this->category->id,
            'name' => 'Classic Tee',
            'price' => 499,
            'stock_quantity' => 0,
        ], $overrides);
    }

    private function shirtOptions(): array
    {
        return [
            ['name' => 'Color', 'values' => ['Red', 'Blue']],
            ['name' => 'Size', 'values' => ['S', 'M']],
        ];
    }

    public function test_admin_can_create_a_product_with_variants(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/admin/products', $this->basePayload([
            'options' => $this->shirtOptions(),
            'variants' => [
                ['option_values' => ['Color' => 'Red', 'Size' => 'S'], 'price' => null, 'stock_quantity' => 10, 'image' => null],
                ['option_values' => ['Color' => 'Blue', 'Size' => 'M'], 'price' => 549, 'stock_quantity' => 5, 'image' => 'https://example.com/blue.jpg'],
            ],
        ]));

        $response->assertCreated();
        $response->assertJsonCount(2, 'variants');
        $response->assertJsonPath('stock_quantity', 15); // recomputed to variant sum
        $response->assertJsonPath('variants.1.price', '549.00');

        $this->assertDatabaseHas('product_variants', ['variant_key' => 'color=red|size=s']);
        $this->assertDatabaseHas('product_variants', ['variant_key' => 'color=blue|size=m']);
    }

    public function test_update_syncs_variants_by_id(): void
    {
        $product = $this->createVariantProduct();
        [$redS, $blueM] = $product->variants->all();

        $response = $this->actingAs($this->admin, 'sanctum')->putJson("/api/admin/products/{$product->id}", $this->basePayload([
            'name' => $product->name,
            'options' => $this->shirtOptions(),
            'variants' => [
                // kept + updated
                ['id' => $redS->id, 'option_values' => ['Color' => 'Red', 'Size' => 'S'], 'stock_quantity' => 20],
                // new combination
                ['option_values' => ['Color' => 'Blue', 'Size' => 'S'], 'stock_quantity' => 3],
                // $blueM omitted -> deleted
            ],
        ]));

        $response->assertOk();
        $response->assertJsonCount(2, 'variants');
        $response->assertJsonPath('stock_quantity', 23);

        $this->assertSame(20, $redS->fresh()->stock_quantity);
        $this->assertDatabaseMissing('product_variants', ['id' => $blueM->id]);
        $this->assertDatabaseHas('product_variants', ['product_id' => $product->id, 'variant_key' => 'color=blue|size=s']);
    }

    public function test_removing_all_options_deletes_variants_and_restores_flat_stock(): void
    {
        $product = $this->createVariantProduct();

        $response = $this->actingAs($this->admin, 'sanctum')->putJson("/api/admin/products/{$product->id}", $this->basePayload([
            'name' => $product->name,
            'stock_quantity' => 42,
        ]));

        $response->assertOk();
        $response->assertJsonPath('options', null);
        $response->assertJsonCount(0, 'variants');
        $response->assertJsonPath('stock_quantity', 42);
        $this->assertDatabaseMissing('product_variants', ['product_id' => $product->id]);
    }

    public function test_variant_missing_an_option_value_is_rejected(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/admin/products', $this->basePayload([
            'options' => $this->shirtOptions(),
            'variants' => [
                ['option_values' => ['Color' => 'Red'], 'stock_quantity' => 5],
            ],
        ]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('variants');
    }

    public function test_variant_with_unlisted_value_is_rejected(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/admin/products', $this->basePayload([
            'options' => $this->shirtOptions(),
            'variants' => [
                ['option_values' => ['Color' => 'Green', 'Size' => 'S'], 'stock_quantity' => 5],
            ],
        ]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('variants');
    }

    public function test_duplicate_combinations_are_rejected(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/admin/products', $this->basePayload([
            'options' => $this->shirtOptions(),
            'variants' => [
                ['option_values' => ['Color' => 'Red', 'Size' => 'S'], 'stock_quantity' => 5],
                ['option_values' => ['Color' => 'red', 'Size' => 's'], 'stock_quantity' => 3],
            ],
        ]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('variants');
    }

    public function test_options_without_variants_are_rejected(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/admin/products', $this->basePayload([
            'options' => $this->shirtOptions(),
            'variants' => [],
        ]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('variants');
    }

    public function test_variants_without_options_are_rejected(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/admin/products', $this->basePayload([
            'variants' => [
                ['option_values' => ['Color' => 'Red'], 'stock_quantity' => 5],
            ],
        ]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('variants');
    }

    public function test_variant_id_from_another_product_is_rejected(): void
    {
        $product = $this->createVariantProduct();
        $foreign = ProductVariant::factory()->create();

        $response = $this->actingAs($this->admin, 'sanctum')->putJson("/api/admin/products/{$product->id}", $this->basePayload([
            'name' => $product->name,
            'options' => $this->shirtOptions(),
            'variants' => [
                ['id' => $foreign->id, 'option_values' => ['Color' => 'Red', 'Size' => 'S'], 'stock_quantity' => 5],
            ],
        ]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('variants');
        $this->assertNotNull($foreign->fresh());
    }

    public function test_deleting_a_variant_keeps_order_history_via_null_fk_and_label(): void
    {
        $product = $this->createVariantProduct();
        $variant = $product->variants->first();

        $order = Order::factory()->create();
        $item = $order->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'product_name' => $product->name,
            'variant_label' => 'Red / S',
            'product_price' => 499,
            'quantity' => 1,
            'subtotal' => 499,
        ]);

        $variant->delete();

        $item->refresh();
        $this->assertNull($item->product_variant_id);
        $this->assertSame('Red / S', $item->variant_label);
    }

    public function test_admin_index_includes_variants_and_count(): void
    {
        $this->createVariantProduct();

        $response = $this->actingAs($this->admin, 'sanctum')->getJson('/api/admin/products');

        $response->assertOk();
        $response->assertJsonPath('data.0.variants_count', 2);
        $response->assertJsonCount(2, 'data.0.variants');
    }

    private function createVariantProduct(): Product
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'name' => 'Classic Tee',
            'price' => 499,
            'options' => $this->shirtOptions(),
        ]);

        $product->variants()->createMany([
            ['option_values' => ['Color' => 'Red', 'Size' => 'S'], 'stock_quantity' => 10],
            ['option_values' => ['Color' => 'Blue', 'Size' => 'M'], 'stock_quantity' => 5],
        ]);

        $product->update(['stock_quantity' => 15]);

        return $product->fresh(['variants']);
    }
}
