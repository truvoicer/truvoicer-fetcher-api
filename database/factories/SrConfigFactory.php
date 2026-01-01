<?php

namespace Database\Factories;

use Truvoicer\TfDbReadCore\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SrConfig>
 */
class SrConfigFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'value' => $this->faker->word,
            'array_value' => $this->faker->words,
            'big_text_value' => $this->faker->sentences(7)
        ];
    }

    public function withProperty(Property $property): SrConfigFactory
    {
        return $this->state(function (array $attributes) use ($property) {
            return [
                'property_id' => $property->id,
            ];
        });
    }
}
