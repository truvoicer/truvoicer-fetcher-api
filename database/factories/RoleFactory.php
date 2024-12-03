<?php

namespace Database\Factories;

use App\Services\Auth\AuthService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Role>
 */
class RoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $role = $this->faker->randomElement(AuthService::DEFAULT_ROLES);
        return [
            'name' => $role['name'],
            'label' => $role['label'],
            'ability' => $role['ability'],
        ];
    }
}
