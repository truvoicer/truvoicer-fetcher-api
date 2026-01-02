<?php

namespace Database\Seeders;

use Truvoicer\TfDbReadCore\Models\User;
use Truvoicer\TfDbReadCore\Services\ApiManager\Data\DefaultData;
use Truvoicer\TfDbReadCore\Services\User\UserAdminService;
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
