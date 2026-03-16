<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Truvoicer\TfDbReadCore\Services\User\UserAdminService;

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
