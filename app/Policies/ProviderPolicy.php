<?php

namespace App\Policies;

use App\Models\Provider;
use App\Models\User;
use App\Services\Auth\AuthService;
use App\Services\Permission\PermissionService;
use Illuminate\Auth\Access\Response;

class ProviderPolicy
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
    public function view(User $user, Provider $provider): bool
    {
        return $user->providers()
            ->where('id', $provider->id)
            ->whereHas($provider, function ($query) {
                $query->whereHas('providerPermissions', function ($query) {
                    $query->whereHas('permission', function ($query) {
                        $query->whereIn('name', [
                            PermissionService::PERMISSION_ADMIN,
                            PermissionService::PERMISSION_READ,
                        ]);
                    });
                });
            })
            ->exists();
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
    public function update(User $user, Provider $provider): bool
    {
        return $user->providers()
            ->where('id', $provider->id)
            ->whereHas($provider, function ($query) {
                $query->whereHas('providerPermissions', function ($query) {
                    $query->whereHas('permission', function ($query) {
                        $query->whereIn('name', [
                            PermissionService::PERMISSION_ADMIN,
                            PermissionService::PERMISSION_UPDATE,
                        ]);
                    });
                });
            })
            ->exists();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Provider $provider): bool
    {
        return $user->providers()
            ->where('id', $provider->id)
            ->whereHas($provider, function ($query) {
                $query->whereHas('providerPermissions', function ($query) {
                    $query->whereHas('permission', function ($query) {
                        $query->whereIn('name', [
                            PermissionService::PERMISSION_ADMIN,
                            PermissionService::PERMISSION_DELETE,
                        ]);
                    });
                });
            })
            ->exists();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Provider $provider): bool
    {
        return true;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Provider $provider): bool
    {
        return true;
    }
}
