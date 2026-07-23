<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 100, 5000);

        return [
            'customer_name' => fake()->name(),
            'customer_email' => fake()->unique()->safeEmail(),
            'customer_phone' => fake()->phoneNumber(),
            'shipping_address' => fake()->address(),
            'status' => 'pending',
            'payment_method' => 'Cash on Delivery',
            'payment_status' => 'unpaid',
            'subtotal' => $subtotal,
            'shipping_fee' => 0,
            'total' => $subtotal,
        ];
    }

    public function card(): static
    {
        return $this->state(fn () => ['payment_method' => Order::PAYMENT_CARD]);
    }
}
