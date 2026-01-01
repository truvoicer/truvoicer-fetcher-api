<?php

namespace App\Policies;

use Truvoicer\TruFetcherGet\Models\Category;
use App\Models\User;
use Truvoicer\TruFetcherGet\Services\Auth\AuthService;
use Truvoicer\TruFetcherGet\Services\Permission\PermissionService;

class CategoryPolicy
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
    public function view(User $user, Category $category): bool
    {
        return $this->checkPermissions($user, $category, [
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
    public function update(User $user, Category $category): bool
    {
        return $this->checkPermissions($user, $category, [
            PermissionService::PERMISSION_ADMIN,
            PermissionService::PERMISSION_UPDATE,
        ]);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Category $category): bool
    {
        return $this->checkPermissions($user, $category, [
            PermissionService::PERMISSION_ADMIN,
            PermissionService::PERMISSION_DELETE,
        ]);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Category $category): bool
    {
        return true;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Category $category): bool
    {
        return true;
    }

    private function checkPermissions(User $user, Category $category, array $permissions)
    {
        return $category->categoryUser()
            ->where('user_id', $user->id)
            ->whereHas('categoryUserPermission', function ($query) use ($permissions) {
                $query->whereHas('permission', function ($query) use ($permissions) {
                    $query->whereIn('name', $permissions);
                });
            })
            ->exists();
    }
}
