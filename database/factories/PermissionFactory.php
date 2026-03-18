<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Truvoicer\TfDbReadCore\Models\Permission;
use Truvoicer\TfDbReadCore\Services\Permission\PermissionService;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Truvoicer\TfDbReadCore\Models\Permission>
 */
class PermissionFactory extends Factory
{
    protected $model = Permission::class;

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
