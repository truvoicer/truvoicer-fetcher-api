<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Services\ApiManager\Data\DefaultData;
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
        $testUserData = DefaultData::TEST_USER_DATA;
        $role = $roleService->getRoleRepository()->findOneBy(
            [['name', '=', AuthService::ABILITY_SUPERUSER]]
        );

        if (!$role instanceof Role) {
            throw new \Exception("Error finding role");
        }

        $user = $userAdminService->getUserRepository()->findOneBy(
            [['email', '=', $testUserData['email']]]
        );
        if ($user instanceof User) {
            return;
        }

        $createUser = $userAdminService->createUser([
            'email' => $testUserData['email'],
            'password' => $testUserData['password']
        ], [$role->id]);

        if (!$createUser) {
            throw new \Exception("Error creating user");
        }
    }
}
