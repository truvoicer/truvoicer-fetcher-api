<?php

namespace App\Repositories;

use App\Models\SUserPermission;

class SUserPermissionRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(SUserPermission::class);
    }

    public function getModel(): SUserPermission
    {
        return parent::getModel();
    }

}
