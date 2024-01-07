<?php

namespace App\Repositories;

use App\Models\Category;
use App\Models\Role;
use App\Models\User;

class RoleRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(Role::class);
    }

    public function getModel(): Role
    {
        return parent::getModel();
    }

    public static function findUserRoleBy(User $user, array $conditions = []) {
        $query = $user->roles();
        foreach ($conditions as $key => $value) {
            $query->where($key, $value);
        }
        return $query->first();
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
