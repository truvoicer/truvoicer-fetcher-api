<?php

namespace Database\Seeders;

use Truvoicer\TruFetcherGet\Models\Permission;
use Truvoicer\TruFetcherGet\Models\Role;
use Truvoicer\TruFetcherGet\Services\Permission\PermissionService;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{

    /**
     * Run the database seeds.
     */
    public function run(PermissionService $permissionService): void
    {
        foreach (PermissionService::DEFAULT_PERMISSIONS as $permission) {
            $findPermission = $permissionService->getPermissionRepository()->findOneBy(
                [['name', '=', $permission['name']]]
            );
            if ($findPermission instanceof Permission) {
                continue;
            }
            $createPermission = $permissionService->createPermission(
                $permission['name'],
                $permission['label'],
            );
            if (!$createPermission) {
                throw new \Exception("Error creating permission | Name: {$permission['name']}");
            }
        }
    }
}
