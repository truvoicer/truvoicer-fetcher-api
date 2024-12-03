<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SrRateLimit>
 */
class SrRateLimitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'override' => $this->faker->boolean,
            'max_attempts' => $this->faker->numberBetween(1, 10),
            'decay_seconds' => $this->faker->numberBetween(1, 10),
            'delay_seconds_per_request' => $this->faker->numberBetween(1, 10),
        ];
    }
}
