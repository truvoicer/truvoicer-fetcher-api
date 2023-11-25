<?php

namespace App\Services\User;

use App\Models\Role;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RoleService extends BaseService
{

    private Role $role;

    public function updateUserRole() {
        $user = $this->getUser();
        $user->role_id = $this->role->id;
        if (!$user->update()) {
            return false;
        }
        return true;
    }
    public function createRole(array $data) {
        $this->role = new Role($data);
        $save = $this->role->save();
        if (!$save) {
            return false;
        }
        return true;
    }
    public function updateRole(array $data) {
        $this->role->fill($data);
        $save = $this->role->save();
        if (!$save) {
            return false;
        }
        return true;
    }

    public function deleteRole() {
        if (!$this->role->delete()) {
            return false;
        }
        return true;
    }

    /**
     * @param Role $role
     */
    public function setRole(Role $role): void
    {
        $this->role = $role;
    }

    /**
     * @return Role
     */
    public function getRole(): Role
    {
        return $this->role;
    }

}
