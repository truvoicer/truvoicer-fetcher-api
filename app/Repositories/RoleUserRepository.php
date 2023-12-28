<?php

namespace App\Repositories;

use App\Models\Category;
use App\Models\Role;
use App\Models\RoleUser;

class RoleUserRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(RoleUser::class);
    }

    public function getModel(): RoleUser
    {
        return parent::getModel();
    }
}
