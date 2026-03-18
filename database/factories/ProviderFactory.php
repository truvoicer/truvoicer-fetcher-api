<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Truvoicer\TfDbReadCore\Models\Provider;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Truvoicer\TfDbReadCore\Models\Provider>
 */
class ProviderFactory extends Factory
{
    protected $model = Provider::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $company = $this->faker->company;

        return [
            'name' => Str::slug($company),
            'label' => $company,
        ];
    }
}
