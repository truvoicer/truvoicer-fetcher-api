<?php

namespace Database\Seeders;

use App\Library\Defaults\DefaultData;
use App\Models\User;
use App\Services\User\UserAdminService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(UserAdminService $userAdminService): void
    {
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            PropertySeeder::class,
            UserSeeder::class,
        ]);

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
        var_dump($tokenData);
    }
}
