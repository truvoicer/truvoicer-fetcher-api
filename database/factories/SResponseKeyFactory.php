<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Truvoicer\TfDbReadCore\Models\SResponseKey;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Truvoicer\TfDbReadCore\Models\SResponseKey>
 */
class SResponseKeyFactory extends Factory
{
    protected $model = SResponseKey::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
        ];
    }
}
