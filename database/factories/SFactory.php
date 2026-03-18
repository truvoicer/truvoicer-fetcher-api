<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Truvoicer\TfDbReadCore\Models\S;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Truvoicer\TfDbReadCore\Models\S>
 */
class SFactory extends Factory
{
    protected $model = S::class;

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
        ];
    }
}
