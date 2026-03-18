<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Truvoicer\TfDbReadCore\Models\Property;
use Truvoicer\TfDbReadCore\Models\SrConfig;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Truvoicer\TfDbReadCore\Models\SrConfig>
 */
class SrConfigFactory extends Factory
{
    protected $model = SrConfig::class;

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
            'big_text_value' => $this->faker->sentences(7),
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
