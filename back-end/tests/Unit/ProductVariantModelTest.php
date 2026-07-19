<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductVariantModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_key_for_is_lowercased_and_sorted_by_option_name(): void
    {
        $key = ProductVariant::keyFor(['Size' => 'M', 'Color' => 'Red']);

        $this->assertSame('color=red|size=m', $key);
        $this->assertSame($key, ProductVariant::keyFor(['Color' => 'Red', 'Size' => 'M']));
        $this->assertSame($key, ProductVariant::keyFor(['color' => 'RED', 'SIZE' => ' m ']));
    }

    public function test_variant_key_is_set_automatically_on_save(): void
    {
        $variant = ProductVariant::factory()->create([
            'option_values' => ['Color' => 'Blue', 'Size' => 'L'],
        ]);

        $this->assertSame('color=blue|size=l', $variant->fresh()->variant_key);
    }

    public function test_label_for_follows_product_option_display_order(): void
    {
        $product = Product::factory()->create([
            'options' => [
                ['name' => 'Size', 'values' => ['S', 'M']],
                ['name' => 'Color', 'values' => ['Red', 'Blue']],
            ],
        ]);
        $variant = ProductVariant::factory()->for($product)->create([
            'option_values' => ['Color' => 'Red', 'Size' => 'M'],
        ]);

        $this->assertSame('M / Red', $variant->labelFor($product));
    }

    public function test_variants_are_deleted_with_their_product(): void
    {
        $variant = ProductVariant::factory()->create();

        $variant->product->delete();

        $this->assertDatabaseMissing('product_variants', ['id' => $variant->id]);
    }

    public function test_product_variants_relationship_returns_variants(): void
    {
        $product = Product::factory()->create();
        ProductVariant::factory()->for($product)->count(2)->create();

        $this->assertCount(2, $product->variants);
    }
}
