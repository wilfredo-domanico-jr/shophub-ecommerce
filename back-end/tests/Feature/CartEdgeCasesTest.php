<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Additional CartController coverage beyond CartTest: the max:999 quantity
 * boundary and a variant_id that doesn't belong to the product being added.
 */
class CartEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    private function makeProduct(array $attributes = []): Product
    {
        return Product::factory()->create(array_merge([
            'category_id' => Category::factory(),
            'price' => 500,
            'stock_quantity' => 5000,
        ], $attributes));
    }

    public function test_quantity_of_999_is_accepted_on_add(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 999])
            ->assertCreated()
            ->assertJsonPath('items.0.quantity', 999);
    }

    public function test_quantity_over_999_is_rejected_on_add(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 1000])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quantity');

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_quantity_over_999_is_rejected_on_update(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        $line = CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/cart/items/{$line->id}", ['quantity' => 1000])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quantity');

        $this->assertSame(2, $line->fresh()->quantity);
    }

    public function test_quantity_of_999_is_accepted_on_update(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        $line = CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/cart/items/{$line->id}", ['quantity' => 999])
            ->assertOk()
            ->assertJsonPath('items.0.quantity', 999);
    }

    public function test_repeated_adds_are_clamped_to_999_instead_of_accumulating_past_it(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 600])
            ->assertCreated();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 600])
            ->assertCreated()
            ->assertJsonPath('items.0.quantity', 999);

        $this->assertSame(999, CartItem::first()->quantity);
    }

    public function test_variant_id_belonging_to_another_product_is_rejected(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct(['options' => [['name' => 'Size', 'values' => ['M', 'L']]]]);
        $product->variants()->create([
            'option_values' => ['Size' => 'M'],
            'price' => 550,
            'stock_quantity' => 5,
        ]);

        $foreignVariant = ProductVariant::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/cart/items', [
                'product_id' => $product->id,
                'variant_id' => $foreignVariant->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('variant_id');

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_nonexistent_variant_id_is_rejected(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct(['options' => [['name' => 'Size', 'values' => ['M', 'L']]]]);
        $product->variants()->create([
            'option_values' => ['Size' => 'M'],
            'price' => 550,
            'stock_quantity' => 5,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/cart/items', [
                'product_id' => $product->id,
                'variant_id' => 999999,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('variant_id');
    }
}
