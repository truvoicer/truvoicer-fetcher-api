<?php

namespace Database\Factories;

use App\Services\Permission\PermissionService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Permission>
 */
class PermissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $permission = $this->faker->randomElement(PermissionService::DEFAULT_PERMISSIONS);
        return [
            'name' => $permission['name'],
            'label' => $permission['label'],
        ];
    }
}
