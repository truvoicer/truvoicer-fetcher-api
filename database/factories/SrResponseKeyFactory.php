<?php

namespace Database\Factories;

use App\Models\Sr;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SrResponseKey>
 */
class SrResponseKeyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $isDate = $this->faker->boolean;
        return [
            'value' => $this->faker->word,
            'show_in_response' => $this->faker->boolean,
            'list_item' => $this->faker->boolean,
            'custom_value' => $this->faker->boolean,
            'search_priority' => $this->faker->randomNumber(),
            'searchable' => $this->faker->boolean,
            'is_date' => $this->faker->boolean,
            'date_format' => ($isDate) ? $this->faker->randomElement(['Y-m-d', 'Y-m-d H:i:s']) : null,
            'array_keys' => null,
            'prepend_extra_data_value' => null,
            'append_extra_data_value' => null,
            'is_service_request' => $this->faker->boolean,
        ];
    }

    public function withSr(Sr $sr): SrResponseKeyFactory
    {
        return $this->state(function (array $attributes) use ($sr) {
            return [
                'sr_id' => $sr->id,
            ];
        });
    }
}
