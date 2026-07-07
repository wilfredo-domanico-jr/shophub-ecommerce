<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductManagementTest extends TestCase
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

    public function test_admin_can_create_a_product(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/admin/products', [
            'category_id' => $this->category->id,
            'name' => 'Wireless Earbuds',
            'price' => 999.99,
            'stock_quantity' => 50,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('slug', 'wireless-earbuds');
        $response->assertJsonPath('is_active', true);
        $this->assertDatabaseHas('products', ['name' => 'Wireless Earbuds']);
    }

    public function test_creating_a_product_requires_a_valid_category(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/admin/products', [
            'category_id' => 99999,
            'name' => 'Orphan Product',
            'price' => 10,
            'stock_quantity' => 1,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('category_id');
    }

    public function test_admin_can_update_a_product(): void
    {
        $product = Product::factory()->create(['category_id' => $this->category->id, 'price' => 100]);

        $response = $this->actingAs($this->admin, 'sanctum')->putJson("/api/admin/products/{$product->id}", [
            'category_id' => $this->category->id,
            'name' => $product->name,
            'price' => 150,
            'stock_quantity' => 5,
        ]);

        $response->assertOk();
        $response->assertJsonPath('price', '150.00');
    }

    public function test_admin_can_delete_a_product(): void
    {
        $product = Product::factory()->create(['category_id' => $this->category->id]);

        $response = $this->actingAs($this->admin, 'sanctum')->deleteJson("/api/admin/products/{$product->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_index_supports_search(): void
    {
        Product::factory()->create(['category_id' => $this->category->id, 'name' => 'Gaming Mouse']);
        Product::factory()->create(['category_id' => $this->category->id, 'name' => 'Office Chair']);

        $response = $this->actingAs($this->admin, 'sanctum')->getJson('/api/admin/products?search=Gaming');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Gaming Mouse');
    }

    public function test_non_admin_cannot_manage_products(): void
    {
        $customer = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($customer, 'sanctum')->postJson('/api/admin/products', [
            'category_id' => $this->category->id,
            'name' => 'Should Not Be Created',
            'price' => 10,
            'stock_quantity' => 1,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('products', ['name' => 'Should Not Be Created']);
    }
}
