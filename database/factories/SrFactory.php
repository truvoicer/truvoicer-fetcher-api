<?php

namespace Database\Factories;

use App\Enums\Sr\SrType;
use App\Repositories\SrRepository;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sr>
 */
class SrFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $label = $this->faker->word;
        return [
            'name' => Str::slug($label),
            'label' => $label,
            'default_sr' => $this->faker->boolean,
            'type' => $this->faker->randomElement(SrType::values()),
            'pagination_type' => $this->faker->randomElement(SrType::values()),
            'query_parameters' => array_combine($this->faker->words(3), $this->faker->words(3)),
            'default_data' => array_combine($this->faker->words(3), $this->faker->words(3)),
        ];
    }
}
