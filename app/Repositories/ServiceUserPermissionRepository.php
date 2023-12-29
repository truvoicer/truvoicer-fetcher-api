<?php

namespace App\Repositories;

use App\Models\ServiceUserPermission;

class ServiceUserPermissionRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(ServiceUserPermission::class);
    }

    public function getModel(): ServiceUserPermission
    {
        return parent::getModel();
    }

}
