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
use App\Repositories\ProviderUserRepository;
use App\Services\ApiServices\ApiService;
use App\Services\Auth\AuthService;
use App\Services\BaseService;
use App\Services\Category\CategoryService;
use App\Services\Permission\AccessControlService;
use App\Services\Permission\PermissionService;
use App\Services\Property\PropertyService;
use App\Services\ApiServices\ResponseKeysService;
use App\Helpers\Tools\UtilHelpers;
use App\Services\User\UserAdminService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ProviderService extends BaseService
{


    protected ProviderRepository $providerRepository;
    protected PermissionRepository $permissionRepository;
    protected ProviderUserRepository $userProviderRepository;
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
        parent::__construct();
        $this->apiService = $apiService;
        $this->responseKeysService = $responseKeysService;
        $this->permissionRepository = new PermissionRepository();
        $this->providerRepository = new ProviderRepository();
        $this->userProviderRepository = new ProviderUserRepository();
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
        $this->providerRepository->setOrderDir($order);
        $this->providerRepository->setSortField($sort);
        $this->providerRepository->setLimit($count);
        return $this->providerRepository->findMany();
    }

    public function findUserProviders(User $user, string $sort, string $order, ?int $count) {
        $this->providerRepository->setPermissions([
            PermissionService::PERMISSION_ADMIN,
            PermissionService::PERMISSION_READ,
        ]);
        return $this->providerRepository->findModelsByUser(
            new Provider(),
            $user
        );
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
        return $this->providerPropertyRepository->findProviderPropertyByProperty(
            $provider,
            $property
        );
    }

    public function getProviderProperties(Provider $provider, string $sort = "name", string $order = "asc", int $count = null)
    {
        $this->providerPropertyRepository->setOrderDir($order);
        $this->providerPropertyRepository->setSortField($sort);
        $this->providerPropertyRepository->setLimit($count);
        return $this->providerPropertyRepository->findProviderProperties($provider);
    }

    public function getProviderPropertyValue(Provider $provider, string $propertyName)
    {
        return $this->getProviderPropertyObjectByName($provider,
            $propertyName)->property_value;
    }

    private function setProviderObject(array $providerData)
    {
        $fields = [
            'name',
            'label',
            'api_base_url',
            'access_key',
            'secret_key',
            'user_id'
        ];
        try {
            $data = [];
            foreach ($fields as $field) {
                if (array_key_exists($field, $providerData)) {
                    $data[$field] = $providerData[$field];
                }
            }
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

    public function createProvider(User $user, array $data)
    {
        if (empty($data['label'])) {
            throw new BadRequestHttpException("Provider label is required.");
        }
        if (empty($data['name'])) {
            $data['name'] = UtilHelpers::labelToName($data['label'], false, '-');
        }

        $checkProvider = $this->providerRepository->findUserModelBy(new Provider(), $user, [
            ['name', '=', $data['name']]
        ], false);

        if ($checkProvider instanceof Provider) {
            throw new BadRequestHttpException(sprintf("Provider (%s) already exists.", $data['name']));
        }
        $provider = $this->setProviderObject($data);
        if (!$this->providerRepository->createProvider($provider)) {
            throw new BadRequestHttpException(sprintf("Error creating provider: %s", $data['name']));
        }

        return $this->permissionEntities->saveUserEntityPermissions(
            $user,
            $this->providerRepository->getModel(),
            ['name' => PermissionService::PERMISSION_ADMIN]
        );
    }


    public function updateProvider(Provider $provider, array $providerData)
    {
        $providerData = $this->setProviderObject($providerData);
        $providerData['id'] = $provider->id;
        return $this->providerRepository->updateProvider($provider, $providerData);
    }

    public function createProviderProperty(Provider $provider, Property $property, string $value)
    {
        return $this->providerPropertyRepository->saveProviderProperty($provider, $property, $value);
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
        return $this->providerPropertyRepository->deleteProviderProperty($provider, $property);
    }

    public function getProviderRepository(): ProviderRepository
    {
        return $this->providerRepository;
    }
}
