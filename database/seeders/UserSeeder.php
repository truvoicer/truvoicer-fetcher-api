<?php

namespace Database\Seeders;

use Truvoicer\TfDbReadCore\Models\Role;
use App\Models\User;
use Truvoicer\TfDbReadCore\Services\ApiManager\Data\DefaultData;
use Truvoicer\TfDbReadCore\Services\Auth\AuthService;
use Truvoicer\TfDbReadCore\Services\User\RoleService;
use Truvoicer\TfDbReadCore\Services\User\UserAdminService;
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


        $testUserData = DefaultData::TEST_USER_DATA;
        $user = $userAdminService->getUserRepository()->findOneBy(
            [['email', '=', $testUserData['email']]]
        );
        if (!$user instanceof User) {
            throw new \Exception("Error finding user");
        }
        $token = $userAdminService->createUserToken($user);
        $tokenData = [
//            'data' => $token->accessToken->toArray(),
            'token' => $token->plainTextToken,
        ];
        $this->command->info('User token created successfully');

    }
}
