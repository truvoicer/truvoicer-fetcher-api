<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProviderProperty>
 */
class ProviderPropertyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'value' => $this->faker->unique()->word(),
            'big_text_value' => $this->faker->unique()->sentence(),
            'array_value' => $this->faker->randomElements([
                'a', 'b', 'c', 'd'
            ]),
        ];
    }
}
