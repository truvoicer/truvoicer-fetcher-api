<?php

namespace App\Policies;

use App\Models\Sr;
use App\Models\User;
use App\Services\Auth\AuthService;
use App\Services\Permission\PermissionService;
use Illuminate\Auth\Access\Response;

class SrPolicy
{
    public function before(User $user, string $ability): bool|null
    {
        if (
            $user->tokenCan(AuthService::getApiAbility(AuthService::ABILITY_SUPERUSER)) ||
            $user->tokenCan(AuthService::getApiAbility(AuthService::ABILITY_ADMIN))
        ) {
            return true;
        }
        return null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Sr $sr): bool
    {
        return $this->checkPermissions($user, $sr, [
            PermissionService::PERMISSION_ADMIN,
            PermissionService::PERMISSION_READ,
        ]);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Sr $sr): bool
    {
        return $this->checkPermissions($user, $sr, [
            PermissionService::PERMISSION_ADMIN,
            PermissionService::PERMISSION_UPDATE,
        ]);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Sr $sr): bool
    {
        return $this->checkPermissions($user, $sr, [
            PermissionService::PERMISSION_ADMIN,
            PermissionService::PERMISSION_DELETE,
        ]);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Sr $sr): bool
    {
        return true;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Sr $sr): bool
    {
        return true;
    }

    private function checkPermissions(User $user, Sr $sr, array $permissions)
    {
        return $sr->provider->providerUser()
            ->where('user_id', $user->id)
            ->whereHas('providerUserPermission', function ($query) use ($permissions) {
                $query->whereHas('permission', function ($query) use ($permissions) {
                    $query->whereIn('name', $permissions);
                });
            })
            ->exists();
    }
}
