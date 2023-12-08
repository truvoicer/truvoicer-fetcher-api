<?php

namespace App\Repositories;

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
        return $this->createQueryBuilder('userProvider')
            ->leftJoin('userProvider.provider', 'provider')
            ->where("userProvider.user = :user")
            ->andWhere("provider.provider_name = :providerName")
            ->setParameter("user", $user)
            ->setParameter("providerName", $value)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findProvidersByUser(User $user, string $sort, string $order, ?int $count)
    {
        $query = $this->createQueryBuilder('userProvider')
            ->where("userProvider.user = :user")
            ->leftJoin('userProvider.provider', 'provider')
            ->setParameter("user", $user)
            ->orderBy("provider.$sort", $order);

        if ($count !== null && $count > 0) {
            $query->setMaxResults($count);
        }
        return $query->getQuery()
            ->getResult();

    }

    public function saveUserProvider(UserProvider $userProvider)
    {
        $this->getEntityManager()->persist($userProvider);
        $this->getEntityManager()->flush();
        return $userProvider;
    }

    public function createUserProvider(User $user, Provider $provider, array $permissions = [])
    {
        $userProvider = new UserProvider();
        $userProvider->setUser($user);
        $userProvider->setProvider($provider);
        foreach ($permissions as $permission) {
            if ($permission instanceof Permission) {
                $userProvider->addPermission($permission);
            }
        }
        return $this->saveUserProvider($userProvider);
    }

    public function deleteUserProvidersRelationsByUser(User $user)
    {
        $getUserProviders = $this->findBy(["user" => $user]);
        foreach ($getUserProviders as $userProvider) {
            $this->delete($userProvider);
        }
        return true;
    }

    public function deleteUserProvidersRelationsByProvider(User $user, Provider $provider)
    {
        $getUserProviders = $this->findBy(["user" => $user, "provider" => $provider]);
        foreach ($getUserProviders as $userProvider) {
            $this->delete($userProvider);
        }
        return true;
    }
    public function delete(UserProvider $userProvider)
    {
        if ($userProvider != null) {
            $this->getEntityManager()->remove($userProvider);
            $this->getEntityManager()->flush();
            return true;
        }
        return false;
    }
}
