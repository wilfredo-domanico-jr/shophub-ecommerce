<?php

namespace Database\Factories;

use App\Models\FlashSale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FlashSale>
 */
class FlashSaleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => ucwords(fake()->words(2, true)).' Sale',
            'starts_at' => now()->subHour(), // live by default
            'ends_at' => now()->addHours(5),
            'is_active' => true,
        ];
    }
}
