<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'option_values' => [
                'Color' => fake()->unique()->colorName(),
                'Size' => fake()->randomElement(['S', 'M', 'L', 'XL']),
            ],
            'price' => null,
            'stock_quantity' => fake()->numberBetween(1, 50),
            'image' => null,
        ];
    }
}
