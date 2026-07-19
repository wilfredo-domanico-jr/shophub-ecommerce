<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JobOpening>
 */
class JobOpeningFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->jobTitle(),
            'department' => fake()->randomElement(['Engineering', 'Operations', 'Fulfillment', 'Marketing']),
            'location' => fake()->randomElement(['Manila', 'Pasig', 'Remote']),
            'employment_type' => fake()->randomElement(['Full-time', 'Part-time', 'Contract']),
            'description' => fake()->paragraph(),
            'is_active' => true,
        ];
    }
}
