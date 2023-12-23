<?php

namespace App\Services\Provider;

use App\Models\Property;
use App\Models\Provider;
use App\Models\User;
use App\Repositories\PermissionRepository;
use App\Repositories\PropertyRepository;
use App\Repositories\ProviderPropertyRepository;
use App\Repositories\ProviderRepository;
use App\Repositories\ServiceRepository;
use App\Repositories\UserProviderRepository;
use App\Services\ApiServices\ApiService;
use App\Services\Auth\AuthService;
use App\Services\BaseService;
use App\Services\Category\CategoryService;
use App\Services\Permission\AccessControlService;
use App\Services\Permission\PermissionService;
use App\Services\Property\PropertyService;
use App\Services\ApiServices\ResponseKeysService;
use App\Services\Tools\UtilsService;
use App\Services\User\UserAdminService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ProviderService extends BaseService
{

    const SERVICE_ALIAS = ProviderEntityService::class;

    protected ProviderRepository $providerRepository;
    protected PermissionRepository $permissionRepository;
    protected UserProviderRepository $userProviderRepository;
    protected ProviderPropertyRepository $providerPropertyRepository;
    protected PropertyService $propertyService;
    protected ServiceRepository $serviceRepository;
    protected CategoryService $categoryService;
    protected ApiService $apiService;
    protected ResponseKeysService $responseKeysService;
    protected AccessControlService $accessControlService;

    public function __construct(
        PropertyService      $propertyService,
        CategoryService      $categoryService,
        ApiService           $apiService,
        ResponseKeysService  $responseKeysService,
        AccessControlService $accessControlService
    )
    {
        $this->apiService = $apiService;
        $this->responseKeysService = $responseKeysService;
        $this->permissionRepository = new PermissionRepository();
        $this->providerRepository = new ProviderRepository();
        $this->userProviderRepository = new UserProviderRepository();
        $this->providerPropertyRepository = new ProviderPropertyRepository();
        $this->propertyService = $propertyService;
        $this->categoryService = $categoryService;
        $this->serviceRepository = new ServiceRepository();
        $this->accessControlService = $accessControlService;
    }

    public function getProviderByName(string $providerName = null)
    {
        return $this->providerRepository->findByName($providerName);
    }

    public function getUserProviderByName(string $providerName = null)
    {
        return $this->userProviderRepository->findUserProviderByName($this->user, $providerName);
    }

    public function getProviderById(int $providerId)
    {
        $provider = $this->providerRepository->findById($providerId);
        if ($provider === null) {
            throw new BadRequestHttpException(sprintf("Provider id:%s not found in database.",
                $providerId
            ));
        }
        return $provider;
    }

    public function findByQuery(string $query)
    {
        return $this->providerRepository->findByQuery($query);
    }

    public function getProviderPropertyRelationById(int $id)
    {
        $providerProperty = $this->providerPropertyRepository->findById($id);
        if ($providerProperty === null) {
            throw new BadRequestHttpException(sprintf("ProviderProperty relation id:%s not found in database.",
                $id
            ));
        }
        return $providerProperty;
    }

    public function getProviderList(string $sort = "name", string $order = "asc", int $count = null)
    {
        $this->providerRepository->setOrderBy($order);
        $this->providerRepository->setSort($sort);
        $this->providerRepository->setLimit($count);
        return $this->providerRepository->findMany();
    }

    public function getUserProviderList(User $user, Provider $provider)
    {
        $this->userProviderRepository->addWhere("user", $user->id);
        $this->userProviderRepository->addWhere("provider", $provider->id);
        return $this->userProviderRepository->findOne();
    }

    public function getProviderListByUser(string $sort = "name", string $order = "asc", ?int $count = null, $user = null)
    {
        $getProviders = $this->userProviderRepository->findProvidersByUser(
            ($user === null) ? $this->user : $user,
            $sort,
            $order,
            $count
        );
        return array_map(function ($userProvider) {
            return $userProvider->getProvider();
        }, $getProviders);
    }

    public function findUserPermittedProviders(string $sort = "name", string $order = "asc", ?int $count = null, $user = null)
    {
        $getProviders = $this->getProviderListByUser(
            $sort,
            $order,
            $count,
            $user
        );
        if (
            UserAdminService::userTokenHasAbility($user, AuthService::ABILITY_SUPERUSER) ||
            UserAdminService::userTokenHasAbility($user, AuthService::ABILITY_ADMIN)
        ) {
            return $this->getProviderList($sort, $order, $count);
        }
        return array_filter($getProviders, function ($provider) use ($user) {
            return $this->accessControlService->checkPermissionsForEntity(
                ProviderEntityService::ENTITY_NAME, $provider, $user,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_READ,
                ],
                false
            );
        }, ARRAY_FILTER_USE_BOTH);
    }

    public function getProviderPropertyRelation(int $id)
    {
        return $this->getProviderPropertyRelationById($id);
    }

    public function getProviderPropertyObjectByName(Provider $provider, string $propertyName)
    {
        $property = $this->propertyService->getPropertyByName($propertyName);
        return $this->getProviderProperty($provider, $property);
    }

    public function getProviderPropertyObjectById(Provider $provider, Property $property)
    {
        return $this->getProviderProperty($provider, $property);
    }

    public function getProviderProperty(Provider $provider, Property $property)
    {
        $getProviderProperty = $this->providerRepository->getProviderProperty($provider, $property);
        $object = new \stdClass();
        $object->property_id = $property->id;
        $object->property_name = $property->name;
        $object->label = $property->label;
        $object->property_value = "";
        if ($getProviderProperty !== null) {
            $object->property_value = $getProviderProperty->value;
        }
        return $object;
    }

    public function getProviderProperties(int $providerId, string $sort = "property_name", string $order = "asc", int $count = null)
    {
        $provider = $this->getProviderById($providerId);

        $propertyRepo = new PropertyRepository();
        $propertyRepo->setOrderBy($order);
        $propertyRepo->setSort($sort);
        $propertyRepo->setLimit($count);

        return array_map(function ($property) use ($provider) {
            $repo = new ProviderPropertyRepository();
            $repo->addWhere("provider", $provider->id);
            $repo->addWhere("property", $property->id);
            $providerProperty = $repo->findOne();
            $providerPropertyObject = new \stdClass();
            $providerPropertyObject->id = $property->id;
            $providerPropertyObject->property_name = $property->name;
            $providerPropertyObject->property_value = ($providerProperty !== null) ? $providerProperty->value : "";
            $providerPropertyObject->value_type = $property->value_type;
            $providerPropertyObject->value_choices = $property->value_choices;
            return $providerPropertyObject;
        }, $propertyRepo->findMany());
    }

    public function getProviderPropertyValue(Provider $provider, string $propertyName)
    {
        return $this->getProviderPropertyObjectByName($provider,
            $propertyName)->property_value;
    }

    private function setProviderObject(array $providerData)
    {
        try {
            $data = [];
            $data['name'] = $providerData['name'];
            $data['label'] = $providerData['label'];
            $data['api_base_url'] = $providerData['api_base_url'];
            $data['access_key'] = $providerData['access_key'];
            $data['secret_key'] = $providerData['secret_key'];
            $data['user_id'] = $providerData['user_id'];
//            if (isset($providerData['category']) && count($providerData['category']) > 0) {
//                foreach ($providerData['category'] as $category) {
//                    $category = $this->categoryService->getCategoryById($category['id']);
//                    $provider->addCategory($category);
//                }
//            }
            return $data;
        } catch (\Exception $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        }
    }

    public function createProvider(array $providerData)
    {
        if (!isset($providerData['label']) || empty($providerData['label'])) {
            throw new BadRequestHttpException("Provider label is required.");
        }
        $providerData['name'] = UtilsService::labelToName($providerData['label'], false, '-');
        $this->providerRepository->addWhere("name", $providerData['name']);
        $checkProvider = $this->providerRepository->findOne();
        if ($checkProvider !== null) {
            throw new BadRequestHttpException(sprintf("Provider (%s) already exists.", $providerData['name']));
        }
        $provider = $this->setProviderObject($providerData);
        $this->permissionRepository->addWhere("name", PermissionService::PERMISSION_ADMIN);
        $getAdminPermission = $this->permissionRepository->findOne();
        if ($getAdminPermission === null) {
            throw new BadRequestHttpException(
                "Admin permission does not exist."
            );
        }
        return $this->providerRepository->createProvider($this->user, $provider, [$getAdminPermission]);

    }


    public function updateProvider(Provider $provider, array $providerData)
    {
        $providerData = $this->setProviderObject($providerData);
        $providerData['id'] = $provider->id;
        return $this->providerRepository->updateProvider($provider, $providerData);
    }

    public function createProviderProperty(Provider $provider, array $providerPropData)
    {
        $property = $this->propertyService->getPropertyById($providerPropData['property_id']);
        return $this->providerRepository->createProviderProperty($provider, $property, $providerPropData['property_value']);
    }

    public function updateProviderProperty(Provider $provider, Property $property, array $data)
    {
        $providerPropertyRepo = new ProviderPropertyRepository();
        $providerPropertyRepo->addWhere("provider", $provider->id);
        $providerPropertyRepo->addWhere("property", $property->id);
        $providerProperty = $providerPropertyRepo->findOne();
        if ($providerProperty !== null) {
            $providerPropertyRepo->setModel($providerProperty);
            return $providerPropertyRepo->save([
                'value' => $data['value']
            ]);
        }
        return $providerPropertyRepo->createProviderProperty($provider, $property, $data['property_value']);
    }

    public function deleteProviderById(int $providerId)
    {
        return $this->deleteProvider($this->getProviderById($providerId));
    }

    public function deleteProvider(Provider $provider)
    {
        return $this->providerRepository->deleteProvider($provider);
    }

    public function deleteProviderProperty(Provider $provider, Property $property)
    {
        $this->providerPropertyRepository->addWhere("provider", $provider->id);
        $this->providerPropertyRepository->addWhere("property", $property->id);
        $providerProperty = $this->providerPropertyRepository->findOne();

        if ($providerProperty !== null) {
            return $this->providerPropertyRepository->deleteProviderProperty($providerProperty);
        }
        throw new BadRequestHttpException(
            sprintf("Error deleting property value. (Provider id:%s, Property id:%s)",
                $provider->id, $property->id
            ));
    }
}
