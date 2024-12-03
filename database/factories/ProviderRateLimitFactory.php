<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProviderRateLimit>
 */
class ProviderRateLimitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'max_attempts' => $this->faker->numberBetween(1, 10),
            'decay_seconds' => $this->faker->numberBetween(1, 10),
            'delay_seconds_per_request' => $this->faker->numberBetween(1, 10),
        ];
    }
}
