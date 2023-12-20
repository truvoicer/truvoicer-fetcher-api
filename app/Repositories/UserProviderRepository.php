<?php

namespace App\Repositories;

use App\Models\Permission;
use App\Models\Provider;
use App\Models\User;
use App\Models\UserProvider;

class UserProviderRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(UserProvider::class);
    }

    public function findUserProviderByName(User $user, string $value)
    {
        return $user->provider()
            ->where('name', $value)
            ->first();
    }

    public function findProvidersByUser(User $user, string $sort, string $order, ?int $count)
    {
        $query = $user->provider()
            ->orderBy("$sort", $order);
        if ($count !== null && $count > 0) {
            $query->limit($count);
        }
        return $query->get();

    }

    public function createUserProvider(User $user, Provider $provider, array $permissions = [])
    {
        $saveUserProvider = $user->provider()->save($provider);
        foreach ($permissions as $permission) {
            if ($permission instanceof Permission) {
                $saveUserProvider->permission()->save($permission);
            }
        }
        return $saveUserProvider;
    }

    public function deleteUserProvidersRelationsByUser(User $user)
    {
        return $user->provider()->delete();
    }

    public function deleteUserProvidersRelationsByProvider(User $user, Provider $provider)
    {
        return $user->provider()->where('provider_id', $provider->id)->delete();
    }
}
