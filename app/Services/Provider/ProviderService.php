<?php

namespace App\Services\Provider;

use App\Models\Category;
use App\Models\Property;
use App\Models\Provider;
use App\Models\ProviderProperty;
use App\Models\S;
use App\Models\User;
use App\Repositories\PermissionRepository;
use App\Repositories\PropertyRepository;
use App\Repositories\ProviderPropertyRepository;
use App\Repositories\ProviderRepository;
use App\Repositories\SRepository;
use App\Repositories\ProviderUserRepository;
use App\Services\ApiServices\ApiService;
use App\Services\ApiServices\ServiceRequests\SrService;
use App\Services\Auth\AuthService;
use App\Services\BaseService;
use App\Services\Category\CategoryService;
use App\Services\Permission\AccessControlService;
use App\Services\Permission\PermissionService;
use App\Services\Property\PropertyService;
use App\Services\ApiServices\SResponseKeysService;
use App\Helpers\Tools\UtilHelpers;
use App\Services\Task\ScheduleService;
use App\Services\User\UserAdminService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ProviderService extends BaseService
{


    protected ProviderRepository $providerRepository;
    protected PermissionRepository $permissionRepository;
    protected ProviderUserRepository $userProviderRepository;
    protected ProviderPropertyRepository $providerPropertyRepository;
    protected PropertyService $propertyService;
    protected SRepository $serviceRepository;
    protected CategoryService $categoryService;
    protected ApiService $apiService;
    protected SResponseKeysService $responseKeysService;
    protected AccessControlService $accessControlService;

    public function __construct(
        PropertyService      $propertyService,
        CategoryService      $categoryService,
        ApiService           $apiService,
        SResponseKeysService $responseKeysService,
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
        $this->serviceRepository = new SRepository();
        $this->accessControlService = $accessControlService;
    }

    public function findProviders(User $user)
    {
        if (
            $user->tokenCan(AuthService::getApiAbility(AuthService::ABILITY_SUPERUSER)) ||
            $user->tokenCan(AuthService::getApiAbility(AuthService::ABILITY_ADMIN))
        ) {
            return  $this->providerRepository->getProviderList();
        }
        return $this->providerRepository->findUserProviders($user);
    }


    public function findProvidersByService(S $service, $user): Collection
    {
        $this->providerRepository->setQuery(
            $service->providers()->distinct()
        );
        return $this->findProviders($user);
    }
    public function getUserProviderByName(User $user, string $providerName = null)
    {
        return $this->userProviderRepository->findUserProviderByName($user, $providerName);
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

    public function getProviderPropertyObjectByName(Provider $provider, string $propertyName)
    {
        $property = $this->propertyService->getPropertyByName($propertyName);
        return $this->getProviderProperty($provider, $property);
    }


    public function getProviderProperty(Provider $provider, Property $property)
    {
        return $this->providerPropertyRepository->findProviderPropertyWithRelation(
            $provider,
            $property
        );
    }

    public function getProviderProperties(Provider $provider)
    {
        return $this->providerPropertyRepository->findProviderProperties($provider);
    }

    public function getProviderPropertyValue(Provider $provider, string $propertyName)
    {
        $property = $this->getProviderPropertyObjectByName($provider, $propertyName);
        if (!$property instanceof Property) {
            return null;
        }
        if (!$property->providerProperty instanceof ProviderProperty ) {
            return null;
        }
        return $property->providerProperty->value;
    }

    private function setProviderObject(array $providerData)
    {
        $fields = [
            'name',
            'label',
            'api_base_url',
            'access_key',
            'secret_key',
            'user_id',
        ];
        try {
            $data = [];
            foreach ($fields as $field) {
                if (array_key_exists($field, $providerData)) {
                    $data[$field] = $providerData[$field];
                }
            }
            if (
                !empty($providerData['categories']) &&
                is_array($providerData['categories']) &&
                count($providerData['categories'])
            ) {
                $categories = array_map(function ($category) {
                    if (!empty($category['id']) && is_numeric($category['id'])) {
                        return $category['id'];
                    }
                    if (!empty($category['value']) && is_numeric($category['value'])) {
                        return $category['value'];
                    }
                    return false;
                }, $providerData['categories']);
                $data['categories'] = array_filter($categories, function ($category) {
                    return $category !== false;
                });
            }
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


    public function updateProvider(User $user, Provider $provider, array $providerData)
    {
        $providerData = $this->setProviderObject($providerData);

        if (!empty($providerData['label'])) {
            if (empty($providerData['name'])) {
                $providerData['name'] = UtilHelpers::labelToName($providerData['label'], false, '-');
            }
            $checkProvider = $this->providerRepository->findUserModelBy($provider, $user, [
                ['name', '=', $providerData['name']]
            ], false);

            if ($checkProvider instanceof Provider) {
                throw new BadRequestHttpException(sprintf("Provider (%s) already exists.", $providerData['name']));
            }
        }

        $update = $this->providerRepository->updateProvider($provider, $providerData);
        if (!$update) {
            throw new BadRequestHttpException(sprintf("Error updating provider: %s", $providerData['name']));
        }
        return $this->permissionEntities->saveRelatedEntity(
            $provider,
            Category::class,
            (!empty($providerData['categories']) && is_array($providerData['categories'])) ? $providerData['categories'] : []
        );
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
    public function deleteBatchProvider(array $ids)
    {
        if (!count($ids)) {
            throw new BadRequestHttpException("No provider ids provided.");
        }
        return $this->providerRepository->deleteBatch($ids);
    }
    public function deleteBatchProviderProperty(array $ids)
    {
        if (!count($ids)) {
            throw new BadRequestHttpException("No property ids provided.");
        }
        return $this->providerPropertyRepository->deleteBatch($ids);
    }

    public function getProviderRepository(): ProviderRepository
    {
        return $this->providerRepository;
    }

    public function getProviderPropertyRepository(): ProviderPropertyRepository
    {
        return $this->providerPropertyRepository;
    }

}
