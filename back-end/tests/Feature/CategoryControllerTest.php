<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_only_returns_active_categories(): void
    {
        Category::factory()->create(['name' => 'Visible', 'is_active' => true]);
        Category::factory()->create(['name' => 'Hidden', 'is_active' => false]);

        $response = $this->getJson('/api/categories');

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonPath('0.name', 'Visible');
    }

    public function test_index_includes_active_product_count(): void
    {
        $category = Category::factory()->create();
        \App\Models\Product::factory()->count(2)->create(['category_id' => $category->id, 'is_active' => true]);
        \App\Models\Product::factory()->create(['category_id' => $category->id, 'is_active' => false]);

        $response = $this->getJson('/api/categories');

        $response->assertOk();
        $response->assertJsonPath('0.products_count', 2);
    }

    public function test_show_returns_active_category_by_slug(): void
    {
        $category = Category::factory()->create(['slug' => 'electronics', 'is_active' => true]);

        $response = $this->getJson('/api/categories/electronics');

        $response->assertOk();
        $response->assertJsonPath('id', $category->id);
    }

    public function test_show_returns_404_for_inactive_category(): void
    {
        Category::factory()->create(['slug' => 'hidden', 'is_active' => false]);

        $this->getJson('/api/categories/hidden')->assertNotFound();
    }

    public function test_show_returns_404_for_unknown_slug(): void
    {
        $this->getJson('/api/categories/does-not-exist')->assertNotFound();
    }
}
