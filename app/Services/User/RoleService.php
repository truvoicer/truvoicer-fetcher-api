<?php

namespace App\Services\User;

use App\Models\Role;
use App\Models\User;
use App\Repositories\RoleRepository;
use App\Services\BaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RoleService extends BaseService
{

    private RoleRepository $roleRepository;

    public function __construct()
    {
        $this->roleRepository = new RoleRepository();
    }

    public function updateUserRole(User $user, Role $role) {
        $user->role_id = $role->id;
        if (!$user->update()) {
            return false;
        }
        return true;
    }
    public function createRole(array $data) {
        return $this->roleRepository->insert($data);
    }
    public function updateRole(Role $role, array $data) {
        return $this->roleRepository->saveRole($role, $data);
    }

    public function deleteRole(Role $role) {
        return $this->roleRepository->deleteRole($role);
    }

}
