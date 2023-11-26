<?php

namespace App\Repositories;

use App\Models\Permission;

class PermissionRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(Permission::class);
    }
}
