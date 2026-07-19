<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductVariantTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_includes_options_and_variants(): void
    {
        $product = Product::factory()->create([
            'price' => 499,
            'options' => [
                ['name' => 'Color', 'values' => ['Red', 'Blue']],
                ['name' => 'Size', 'values' => ['S', 'M']],
            ],
        ]);
        $product->variants()->createMany([
            ['option_values' => ['Color' => 'Red', 'Size' => 'S'], 'stock_quantity' => 10],
            ['option_values' => ['Color' => 'Blue', 'Size' => 'M'], 'price' => 549, 'stock_quantity' => 5, 'image' => 'https://example.com/blue.jpg'],
        ]);

        $response = $this->getJson("/api/products/{$product->slug}");

        $response->assertOk();
        $response->assertJsonPath('options.0.name', 'Color');
        $response->assertJsonCount(2, 'variants');
        // Overrides are nullable — the frontend falls back to product values.
        $response->assertJsonPath('variants.0.price', null);
        $response->assertJsonPath('variants.0.image', null);
        $response->assertJsonPath('variants.1.price', '549.00');
        $response->assertJsonPath('variants.1.option_values.Color', 'Blue');
    }

    public function test_show_returns_empty_variant_fields_for_flat_products(): void
    {
        $product = Product::factory()->create();

        $response = $this->getJson("/api/products/{$product->slug}");

        $response->assertOk();
        $response->assertJsonPath('options', null);
        $response->assertJsonCount(0, 'variants');
    }

    public function test_index_includes_variants_count(): void
    {
        $product = Product::factory()->create([
            'name' => 'Aaa Variant Tee',
            'options' => [['name' => 'Color', 'values' => ['Red']]],
        ]);
        $product->variants()->create(['option_values' => ['Color' => 'Red'], 'stock_quantity' => 3]);
        Product::factory()->create(['name' => 'Bbb Flat Mug']);

        $response = $this->getJson('/api/products'); // default sort: name

        $response->assertOk();
        $response->assertJsonPath('data.0.variants_count', 1);
        $response->assertJsonPath('data.1.variants_count', 0);
    }
}
