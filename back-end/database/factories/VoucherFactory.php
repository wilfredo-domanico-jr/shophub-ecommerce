<?php

namespace Database\Factories;

use App\Models\Voucher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Voucher>
 */
class VoucherFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('??##??')),
            'description' => null,
            'type' => Voucher::TYPE_PERCENT,
            'value' => 10,
            'max_discount' => null,
            'min_spend' => null,
            'starts_at' => null,
            'expires_at' => null,
            'usage_limit' => null,
            'per_customer_limit' => null,
            'is_active' => true,
        ];
    }
}
