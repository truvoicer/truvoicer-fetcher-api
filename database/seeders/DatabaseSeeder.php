<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\ApiManager\Data\DefaultData;
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
        ]);

    }
}
