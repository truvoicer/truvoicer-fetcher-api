<?php

namespace App\Repositories;

use App\Models\ProviderUserPermission;

class ProviderUserPermissionRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(ProviderUserPermission::class);
    }

    public function getModel(): ProviderUserPermission
    {
        return parent::getModel();
    }

}
