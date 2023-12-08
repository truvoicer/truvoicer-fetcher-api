<?php

namespace App\Services\Provider;

use App\Models\Permission;
use App\Models\Property;
use App\Models\Provider;
use App\Models\ProviderProperty;
use App\Models\Service;
use App\Models\User;
use App\Models\UserProvider;
use App\Repositories\PermissionRepository;
use App\Repositories\PropertyRepository;
use App\Repositories\ProviderPropertyRepository;
use App\Repositories\ProviderRepository;
use App\Repositories\ServiceRepository;
use App\Repositories\UserProviderRepository;
use App\Services\ApiServices\ApiService;
use App\Services\BaseService;
use App\Services\Category\CategoryService;
use App\Services\Permission\AccessControlService;
use App\Services\Permission\PermissionService;
use App\Services\Property\PropertyService;
use App\Services\ApiServices\ResponseKeysService;
use App\Services\Tools\HttpRequestService;
use App\Services\Tools\UtilsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ProviderService extends BaseService
{

    const SERVICE_ALIAS = "app.service.provider.provider_entity_service";

    protected ProviderRepository $providerRepository;
    protected PermissionRepository $permissionRepository;
    protected UserProviderRepository $userProviderRepository;
    protected ProviderPropertyRepository $providerPropertyRepository;
    protected PropertyService $propertyService;
    protected ServiceRepository $serviceRepository;
    protected HttpRequestService $httpRequestService;
    protected CategoryService $categoryService;
    protected ApiService $apiService;
    protected ResponseKeysService $responseKeysService;
    protected AccessControlService $accessControlService;

    public function __construct(EntityManagerInterface $entityManager, HttpRequestService $httpRequestService,
                                PropertyService $propertyService, CategoryService $categoryService,
                                ApiService $apiService, ResponseKeysService $responseKeysService,
                                TokenStorageInterface $tokenStorage, AccessControlService $accessControlService)
    {
        $this->apiService = $apiService;
        $this->httpRequestService = $httpRequestService;
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
        $provider = $this->providerRepository->find($providerId);
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

    public function getProviderList(string $sort = "provider_name", string $order = "asc", int $count = null)
    {
        return $this->providerRepository->findByParams(
            $sort,
            $order,
            $count
        );
    }

    public function getUserProviderList(User $user, Provider $provider)
    {
        $this->userProviderRepository->addWhere("user", $user->getId());
        $this->userProviderRepository->addWhere("provider", $provider->getId());
        return $this->userProviderRepository->findOne();
    }

    public function getProviderListByUser(string $sort = "provider_name", string $order = "asc", ?int $count = null, $user = null)
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

    public function findUserPermittedProviders(string $sort = "provider_name", string $order = "asc", ?int $count = null, $user = null)
    {
        $getProviders = $this->getProviderListByUser(
            $sort,
            $order,
            $count,
            $user
        );
        if (
            $this->accessControlService->getAuthorizationChecker()->isGranted('ROLE_SUPER_ADMIN') ||
            $this->accessControlService->getAuthorizationChecker()->isGranted('ROLE_ADMIN')
        ) {
            return $this->getProviderList($sort, $order, $count);
        }
        return array_filter($getProviders, function ($provider) use($user) {
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
        $object->property_id = $property->getId();
        $object->property_name = $property->getPropertyName();
        $object->property_label = $property->getPropertyLabel();
        $object->property_value = "";
        if ($getProviderProperty !== null) {
            $object->property_value = $getProviderProperty->getPropertyValue();
        }
        return $object;
    }

    public function getProviderProperties(int $providerId, string $sort = "property_name", string $order = "asc", int $count = null)
    {
        $provider = $this->getProviderById($providerId);

        $propertyRepo = new PropertyRepository();
        return array_map(function ($property) use ($provider) {
            $repo = new ProviderPropertyRepository();
            $repo->addWhere("provider", $provider->getId());
            $repo->addWhere("property", $property->getId());
            $providerProperty = $repo->findOne();
            $providerPropertyObject = new \stdClass();
            $providerPropertyObject->id = $property->getId();
            $providerPropertyObject->property_name = $property->getPropertyName();
            $providerPropertyObject->property_value = ($providerProperty !== null) ? $providerProperty->getPropertyValue() : "";
            $providerPropertyObject->value_type = $property->getValueType();
            $providerPropertyObject->value_choices = $property->getValueChoices();
            return $providerPropertyObject;
        }, $propertyRepo->findByParams($sort, $order, $count));
    }

    public function getProviderPropertyValue(Provider $provider, string $propertyName)
    {
        return $this->getProviderPropertyObjectByName($provider,
            $propertyName)->property_value;
    }

    public function getServiceParameterByName(Provider $provider, string $serviceName = null, string $parameterName = null)
    {
        return $this->providerRepository->getServiceParameterByName($provider, $serviceName, $parameterName);
    }

    public function getProviderServiceParametersByName(Provider $provider, string $serviceName = null, array $reservedParams = [])
    {
        return $this->providerRepository->getProviderServiceParameters($provider, $serviceName, $reservedParams);
    }

    public function getProviderServiceParametersById(int $providerId, int $serviceId)
    {
        $service = $this->serviceRepository->findById($serviceId);
        $provider = $this->providerRepository->findById($providerId);
        return $this->providerRepository->getProviderServiceParameters($provider, $service);
    }

    private function setProviderObject(Provider $provider, array $providerData)
    {
        try {
            $provider->setProviderName($providerData['provider_name']);
            $provider->setProviderLabel($providerData['provider_label']);
            $provider->setProviderApiBaseUrl($providerData['provider_api_base_url']);
            $provider->setProviderAccessKey($providerData['provider_access_key']);
            $provider->setProviderSecretKey($providerData['provider_secret_key']);
            $provider->setProviderUserId($providerData['provider_user_id']);
            foreach ($provider->getCategory() as $category) {
                $provider->removeCategory($category);
            }
            if (isset($providerData['category']) && count($providerData['category']) > 0) {
                foreach ($providerData['category'] as $category) {
                    $category = $this->categoryService->getCategoryById($category['id']);
                    $provider->addCategory($category);
                }
            }
            return $provider;
        } catch (\Exception $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        }
    }

    public function createProvider(array $providerData)
    {
        if (!isset($providerData['provider_label']) || empty($providerData['provider_label'])) {
            throw new BadRequestHttpException("Provider label is required.");
        }
        $providerData['provider_name'] = UtilsService::labelToName($providerData['provider_label'], false, '-');
        $this->providerRepository->addWhere("provider_name", $providerData['provider_name']);
        $checkProvider = $this->providerRepository->findOne();
        if ($checkProvider !== null) {
            throw new BadRequestHttpException(sprintf("Provider (%s) already exists.", $providerData['provider_name']));
        }
        $provider = $this->setProviderObject(new Provider(), $providerData);
        if ($this->httpRequestService->validateData($provider)) {
            $this->permissionRepository->addWhere("name", PermissionService::PERMISSION_ADMIN);
            $getAdminPermission = $this->permissionRepository->findOne();
            if ($getAdminPermission === null) {
                throw new BadRequestHttpException(
                    "Admin permission does not exist."
                );
            }
            return $this->providerRepository->createProvider($this->user, $provider, [$getAdminPermission]);
        }
        return false;
    }


    public function updateProvider(Provider $provider, array $providerData)
    {
        if (!array_key_exists("id", $providerData)) {
            throw new BadRequestHttpException("Provider id doesnt exist in request.");
        }
        $getProvider = $this->providerRepository->findById($providerData["id"]);
        $provider = $this->setProviderObject($getProvider, $providerData);
        if ($this->httpRequestService->validateData($provider)) {
            return $this->providerRepository->updateProvider($provider);
        }
        return false;
    }

    public function createProviderProperty(Provider $provider, array $providerPropData)
    {
        $property = $this->propertyService->getPropertyById($providerPropData['property_id']);
        return $this->providerRepository->createProviderProperty($provider, $property, $providerPropData['property_value']);
    }

    public function updateProviderProperty(Provider $provider, Property $property, array $data)
    {
        $providerPropertyRepo = new ProviderPropertyRepository();
        $providerPropertyRepo->addWhere("provider", $provider->getId());
        $providerPropertyRepo->addWhere("property", $property->getId());
        $providerProperty = $providerPropertyRepo->findOne();
        if ($providerProperty !== null) {
            $providerProperty->setPropertyValue($data['property_value']);
            return $providerPropertyRepo->saveProviderProperty($providerProperty);
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
        $this->providerPropertyRepository->addWhere("provider", $provider->getId());
        $this->providerPropertyRepository->addWhere("property", $property->getId());
        $providerProperty = $this->providerPropertyRepository->findOne();

        if ($providerProperty !== null) {
            return $this->providerPropertyRepository->deleteProviderProperty($providerProperty);
        }
        throw new BadRequestHttpException(
            sprintf("Error deleting property value. (Provider id:%s, Property id:%s)",
                $provider->getId(), $property->getId()
            ));
    }
}
