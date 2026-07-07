<?php

namespace Tests\Unit;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_scope_only_returns_active_products(): void
    {
        Product::factory()->create(['is_active' => true]);
        Product::factory()->create(['is_active' => false]);

        $this->assertSame(1, Product::active()->count());
    }

    public function test_featured_scope_only_returns_featured_products(): void
    {
        Product::factory()->create(['is_featured' => true]);
        Product::factory()->create(['is_featured' => false]);

        $this->assertSame(1, Product::featured()->count());
    }

    public function test_flash_sale_scope_only_returns_flash_sale_products(): void
    {
        Product::factory()->create(['is_flash_sale' => true]);
        Product::factory()->create(['is_flash_sale' => false]);

        $this->assertSame(1, Product::flashSale()->count());
    }

    public function test_product_belongs_to_a_category(): void
    {
        $product = Product::factory()->create();

        $this->assertInstanceOf(\App\Models\Category::class, $product->category);
    }
}
