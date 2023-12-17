<?php

namespace App\Repositories;

use App\Models\Category;
use App\Models\Role;

class RoleRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(Category::class);
    }

    public function saveRole(Role $role, array $data)
    {
        $this->setModel($role);
        return $this->save($data);
    }


    public function deleteRole(Role $role) {
        $this->setModel($role);
        return $this->delete();
    }
}
