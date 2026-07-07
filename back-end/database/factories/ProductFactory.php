<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);
        $price = fake()->randomFloat(2, 100, 5000);

        return [
            'category_id' => Category::factory(),
            'name' => ucwords($name),
            'slug' => Str::slug($name) . '-' . fake()->unique()->numberBetween(1, 100000),
            'description' => fake()->paragraph(),
            'price' => $price,
            'original_price' => null,
            'stock_quantity' => fake()->numberBetween(0, 200),
            'image' => 'https://source.unsplash.com/400x400/?product',
            'is_featured' => false,
            'is_flash_sale' => false,
            'sold_count' => 0,
            'flash_sale_goal' => null,
            'rating' => fake()->randomFloat(1, 3, 5),
            'is_active' => true,
        ];
    }
}
