<?php

namespace App\Services\Provider;


use App\Models\Provider;
use App\Models\User;
use App\Services\ApiServices\ApiService;
use App\Services\ApiServices\ResponseKeysService;
use App\Services\Category\CategoryService;
use App\Services\Permission\AccessControlService;
use App\Services\Property\PropertyService;

class ProviderEntityService extends ProviderService
{
    const ENTITY_NAME = "provider";

    protected AccessControlService $accessControlService;

    public function __construct(
        PropertyService $propertyService,
        CategoryService $categoryService,
        ApiService $apiService,
        ResponseKeysService $responseKeysService,
        AccessControlService $accessControlService
    ) {
        parent::__construct(
            $propertyService,
            $categoryService,
            $apiService,
            $responseKeysService,
            $accessControlService
        );
    }


    public function getUserProviderByUser(User $user, int $providerId)
    {
        $this->userProviderRepository->addWhere("user", $user->id);
        $this->userProviderRepository->addWhere("provider", $this->getProviderById($providerId));
        return $this->userProviderRepository->findOne();
    }

    public function getUserProviderList(User $user, Provider $provider)
    {
        $this->userProviderRepository->addWhere("user", $user->id);
        $this->userProviderRepository->addWhere("provider", $provider->id);
        return $this->userProviderRepository->findOne();
    }

    public function getUserProviderPermissionsListByUser(
        string $sort = "provider_name",
        string $order = "asc",
        ?int $count = null,
        $user = null
    ) {
        $getProviders = $this->userProviderRepository->findProvidersByUser(
            ($user === null) ? $this->user : $user,
            $sort,
            $order,
            $count
        );
        return array_map(function ($userProvider) {
            return [
                "provider" => $userProvider->getProvider(),
                "permission" => $userProvider->getPermission()
            ];
        }, $getProviders);
    }


    public function deleteUserProviderPermissions(User $user, Provider $provider)
    {
        return $this->userProviderRepository->deleteUserProvidersRelationsByProvider($user, $provider);
    }

    public function saveUserProviderPermissions(User $user, int $providerId, array $permissions)
    {
        $getProvider = $this->providerRepository->findById($providerId);
        if ($getProvider === null) {
            return false;
        }
        $this->userProviderRepository->deleteUserProvidersRelationsByProvider($user, $getProvider);
        $buildPermissions = array_map(function ($permission) {
            return $this->permissionRepository->findById($permission);
        }, $permissions);
        $this->userProviderRepository->createUserProvider($user, $getProvider, $buildPermissions);
        return true;
    }
}
