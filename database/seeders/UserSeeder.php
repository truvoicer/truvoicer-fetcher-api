<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Services\Auth\AuthService;
use App\Services\User\RoleService;
use App\Services\User\UserAdminService;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(RoleService $roleService, UserAdminService $userAdminService): void
    {
        $email = 'mikydxl@gmail.com';
        $role = $roleService->getRoleRepository()->findOneBy(
            [['name', '=', AuthService::ABILITY_SUPERUSER]]
        );

        if (!$role instanceof Role) {
            throw new \Exception("Error finding role");
        }

        $user = $userAdminService->getUserRepository()->findOneBy(
            [['email', '=', $email]]
        );
        if ($user instanceof User) {
            return;
        }

        $createUser = $userAdminService->createUser([
            'email' => $email,
            'password' => 'Deelite4'
        ], $role);
        if (!$createUser) {
            throw new \Exception("Error creating user");
        }
    }
}
