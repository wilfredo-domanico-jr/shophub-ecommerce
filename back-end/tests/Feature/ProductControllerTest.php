<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_only_returns_active_products(): void
    {
        Product::factory()->create(['name' => 'Visible', 'is_active' => true]);
        Product::factory()->create(['name' => 'Hidden', 'is_active' => false]);

        $response = $this->getJson('/api/products');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name');
        $this->assertTrue($names->contains('Visible'));
        $this->assertFalse($names->contains('Hidden'));
    }

    public function test_index_filters_by_search(): void
    {
        Product::factory()->create(['name' => 'Wireless Mouse']);
        Product::factory()->create(['name' => 'Mechanical Keyboard']);

        $response = $this->getJson('/api/products?search=wireless');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Wireless Mouse');
    }

    public function test_index_filters_by_category_slug(): void
    {
        $electronics = Category::factory()->create(['slug' => 'electronics']);
        $fashion = Category::factory()->create(['slug' => 'fashion']);

        Product::factory()->create(['category_id' => $electronics->id, 'name' => 'Laptop']);
        Product::factory()->create(['category_id' => $fashion->id, 'name' => 'Jacket']);

        $response = $this->getJson('/api/products?category=electronics');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Laptop');
    }

    public function test_index_filters_by_featured_and_flash_sale(): void
    {
        Product::factory()->create(['name' => 'Featured Item', 'is_featured' => true]);
        Product::factory()->create(['name' => 'Flash Item', 'is_flash_sale' => true]);
        Product::factory()->create(['name' => 'Plain Item']);

        $featured = $this->getJson('/api/products?featured=1');
        $featured->assertJsonCount(1, 'data');
        $featured->assertJsonPath('data.0.name', 'Featured Item');

        $flashSale = $this->getJson('/api/products?flash_sale=1');
        $flashSale->assertJsonCount(1, 'data');
        $flashSale->assertJsonPath('data.0.name', 'Flash Item');
    }

    public function test_index_sorts_by_price(): void
    {
        Product::factory()->create(['name' => 'Expensive', 'price' => 999]);
        Product::factory()->create(['name' => 'Cheap', 'price' => 10]);

        $ascending = $this->getJson('/api/products?sort=price_asc');
        $ascending->assertJsonPath('data.0.name', 'Cheap');

        $descending = $this->getJson('/api/products?sort=price_desc');
        $descending->assertJsonPath('data.0.name', 'Expensive');
    }

    public function test_show_returns_404_for_inactive_product(): void
    {
        $product = Product::factory()->create(['slug' => 'hidden-product', 'is_active' => false]);

        $this->getJson("/api/products/{$product->slug}")->assertNotFound();
    }

    public function test_show_returns_active_product_with_category(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'slug' => 'visible-product']);

        $response = $this->getJson('/api/products/visible-product');

        $response->assertOk();
        $response->assertJsonPath('id', $product->id);
        $response->assertJsonPath('category.id', $category->id);
    }

    public function test_products_expose_reviews_count(): void
    {
        $product = Product::factory()->create(['slug' => 'reviewed-product']);
        \App\Models\Review::factory()->count(2)->create(['product_id' => $product->id, 'rating' => 4]);

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonPath('data.0.reviews_count', 2);

        $this->getJson('/api/products/reviewed-product')
            ->assertOk()
            ->assertJsonPath('reviews_count', 2)
            ->assertJsonPath('rating', '4.0');
    }
}
