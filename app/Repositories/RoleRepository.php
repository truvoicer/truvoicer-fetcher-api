<?php

namespace App\Repositories;

use App\Models\Category;
use App\Models\Role;
use App\Models\User;
use App\Services\Auth\AuthService;

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

    public function fetchRolesById(array $roleIds) {
        $this->addWhere('id', $roleIds, 'in');
        return $this->findMany();
    }
    public function fetchRolesByNames(array $roleNames) {
        $this->addWhere('name', $roleNames, 'in');
        return $this->findMany();
    }

    public static function findUserRoleBy(User $user, array $conditions = []) {
        $query = $user->roles();
        foreach ($conditions as $key => $value) {
            $query->where($key, $value);
        }
        return $query->first();
    }

    public function fetchUserRoles(User $user, array $includeRoles = []) {
        $ids = array_map(function($roleName) {
            $appUserRole = $this->findOneBy([['name', $roleName, '=']]);
            if (!$appUserRole instanceof Role) {
                return false;
            }
            return $appUserRole->id;
        }, $includeRoles);
        $filteredIds = array_filter($ids);
        return $this->getModel()
            ->whereHas('users', function($query) use ($user, $filteredIds) {
                $query->where('user_id', $user->id);

            })
            ->orWhereIn('id', $filteredIds)
            ->get();
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
