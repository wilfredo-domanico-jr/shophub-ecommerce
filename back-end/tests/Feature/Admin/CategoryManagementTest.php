<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    public function test_admin_can_create_a_category(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/admin/categories', [
            'name' => 'Electronics',
            'icon' => 'M0 0h1v1H0z',
            'color_class' => 'gradient-primary',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('slug', 'electronics');
        $this->assertDatabaseHas('categories', ['name' => 'Electronics', 'slug' => 'electronics']);
    }

    public function test_creating_a_category_with_a_duplicate_name_gets_a_unique_slug(): void
    {
        Category::factory()->create(['name' => 'Electronics', 'slug' => 'electronics']);

        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/admin/categories', [
            'name' => 'Electronics',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('slug', 'electronics-1');
    }

    public function test_admin_can_update_a_category(): void
    {
        $category = Category::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($this->admin, 'sanctum')->putJson("/api/admin/categories/{$category->id}", [
            'name' => 'New Name',
        ]);

        $response->assertOk();
        $response->assertJsonPath('name', 'New Name');
        $response->assertJsonPath('slug', 'new-name');
    }

    public function test_admin_can_delete_an_empty_category(): void
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->admin, 'sanctum')->deleteJson("/api/admin/categories/{$category->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_admin_cannot_delete_a_category_with_products(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['category_id' => $category->id]);

        $response = $this->actingAs($this->admin, 'sanctum')->deleteJson("/api/admin/categories/{$category->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_category_name_is_required(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/admin/categories', []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('name');
    }

    public function test_index_supports_search_and_pagination(): void
    {
        Category::factory()->create(['name' => 'Electronics']);
        Category::factory()->create(['name' => 'Fashion']);

        $response = $this->actingAs($this->admin, 'sanctum')->getJson('/api/admin/categories?search=Elec');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Electronics');
    }
}
