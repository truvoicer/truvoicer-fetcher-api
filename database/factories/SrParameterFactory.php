<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Truvoicer\TfDbReadCore\Models\SrParameter;

/**
 * @extends Factory<SrParameter>
 */
class SrParameterFactory extends Factory
{
    protected $model = SrParameter::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
            'value' => $this->faker->word,
        ];
    }
}
