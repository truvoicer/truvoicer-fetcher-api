<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Services\Auth\AuthService;
use App\Services\User\RoleService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    private RoleService $roleService;

    /**
     * Run the database seeds.
     */
    public function run(RoleService $roleService): void
    {
        $this->roleService = $roleService;
        foreach (AuthService::DEFAULT_ROLES as $role) {
            $findRole = $this->roleService->findRoleBy([['name', '=', $role['name']]]);
            if ($findRole instanceof Role && $findRole->exists) {
                continue;
            }
            $createRole = $this->roleService->createRole([
                'name' => $role['name'],
                'label' => $role['label'],
                'ability' => $role['ability'],
            ]);
            if (!$createRole) {
                throw new \Exception("Error creating role | Name: {$role['name']}");
            }
        }
    }
}
