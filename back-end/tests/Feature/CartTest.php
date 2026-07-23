<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    private function makeProduct(array $attributes = []): Product
    {
        return Product::factory()->create(array_merge([
            'category_id' => Category::factory(),
            'price' => 500,
            'stock_quantity' => 10,
        ], $attributes));
    }

    public function test_guest_cannot_access_the_cart(): void
    {
        $this->getJson('/api/cart')->assertUnauthorized();
        $this->postJson('/api/cart/items', ['product_id' => 1])->assertUnauthorized();
    }

    public function test_adding_a_product_creates_an_available_cart_line(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 2]);

        $response->assertCreated();
        $response->assertJsonPath('items.0.product_id', $product->id);
        $response->assertJsonPath('items.0.name', $product->name);
        $response->assertJsonPath('items.0.quantity', 2);
        $response->assertJsonPath('items.0.price', 500);
        $response->assertJsonPath('items.0.is_available', true);
    }

    public function test_adding_the_same_product_increments_the_existing_line(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/cart/items', ['product_id' => $product->id]);
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 2]);

        $response->assertJsonCount(1, 'items');
        $response->assertJsonPath('items.0.quantity', 3);
        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_variant_is_required_for_products_with_options(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct(['options' => [['name' => 'Size', 'values' => ['M', 'L']]]]);
        $variant = $product->variants()->create([
            'option_values' => ['Size' => 'M'],
            'price' => 550,
            'stock_quantity' => 5,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/cart/items', ['product_id' => $product->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('variant_id');

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('items.0.variant_id', $variant->id);
        $response->assertJsonPath('items.0.variant_label', 'M');
        $response->assertJsonPath('items.0.price', 550);
    }

    public function test_variant_is_rejected_for_flat_products(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/cart/items', ['product_id' => $product->id, 'variant_id' => 999])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('variant_id');
    }

    public function test_inactive_product_cannot_be_added(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct(['is_active' => false]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/cart/items', ['product_id' => $product->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('product_id');
    }

    public function test_quantity_can_be_updated_but_not_below_one(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        $line = CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/cart/items/{$line->id}", ['quantity' => 5])
            ->assertOk()
            ->assertJsonPath('items.0.quantity', 5);

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/cart/items/{$line->id}", ['quantity' => 0])
            ->assertUnprocessable();
    }

    public function test_cart_lines_are_owner_scoped(): void
    {
        $line = CartItem::factory()->create();
        $intruder = User::factory()->create();

        $this->actingAs($intruder, 'sanctum')
            ->patchJson("/api/cart/items/{$line->id}", ['quantity' => 5])
            ->assertNotFound();

        $this->actingAs($intruder, 'sanctum')
            ->deleteJson("/api/cart/items/{$line->id}")
            ->assertNotFound();

        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_a_line_can_be_removed_and_the_cart_cleared(): void
    {
        $user = User::factory()->create();
        $first = CartItem::factory()->create(['user_id' => $user->id, 'product_id' => $this->makeProduct()->id]);
        CartItem::factory()->create(['user_id' => $user->id, 'product_id' => $this->makeProduct()->id]);
        $otherUsers = CartItem::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/cart/items/{$first->id}")
            ->assertOk()
            ->assertJsonCount(1, 'items');

        $this->actingAs($user, 'sanctum')->deleteJson('/api/cart')->assertNoContent();

        $this->assertDatabaseCount('cart_items', 1);
        $this->assertDatabaseHas('cart_items', ['id' => $otherUsers->id]);
    }

    public function test_inactive_and_out_of_stock_lines_are_flagged_unavailable(): void
    {
        $user = User::factory()->create();
        $inactive = $this->makeProduct(['is_active' => false]);
        $outOfStock = $this->makeProduct(['stock_quantity' => 0]);

        CartItem::factory()->create(['user_id' => $user->id, 'product_id' => $inactive->id]);
        CartItem::factory()->create(['user_id' => $user->id, 'product_id' => $outOfStock->id]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/cart');

        $response->assertOk();
        $response->assertJsonPath('items.0.is_available', false);
        $response->assertJsonPath('items.1.is_available', false);
    }

    public function test_deleted_product_line_falls_back_to_its_snapshot(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'product_name' => 'Vanished Widget',
        ]);

        $product->delete();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/cart');

        $response->assertOk();
        $response->assertJsonPath('items.0.product_id', null);
        $response->assertJsonPath('items.0.name', 'Vanished Widget');
        $response->assertJsonPath('items.0.price', null);
        $response->assertJsonPath('items.0.is_available', false);
    }
}
