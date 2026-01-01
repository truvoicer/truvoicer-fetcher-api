<?php

namespace App\Services\ApiServices;

use Truvoicer\TruFetcherGet\Models\S;
use App\Models\User;
use Truvoicer\TruFetcherGet\Repositories\SRepository;
use Truvoicer\TruFetcherGet\Repositories\SrParameterRepository;
use Truvoicer\TruFetcherGet\Repositories\SrRepository;
use App\Services\BaseService;
use Truvoicer\TruFetcherGet\Helpers\Tools\UtilHelpers;
use App\Services\Permission\AccessControlService;
use App\Services\Permission\PermissionService;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ApiService extends BaseService
{


    /**
     * ApiServicesService constructor.
     * @param SResponseKeysService $responseKeysService
     */
    public function __construct(
        protected SResponseKeysService  $responseKeysService,
        protected AccessControlService  $accessControlService,
        protected SRepository           $serviceRepository,
        protected SrRepository          $serviceRequestRepository,
        protected SrParameterRepository $requestParametersRepo,
    )
    {
        parent::__construct();
    }

    public function findByQuery(string $query)
    {
        return $this->serviceRepository->findByQuery($query);
    }

    public function findByParams(string $sort = "name", ?string $order = "asc", int $count = -1, ?bool $pagination = true)
    {
        $this->serviceRepository->setPagination($pagination);
        $this->serviceRepository->setOrderDir($order);
        $this->serviceRepository->setSortField($sort);
        $this->serviceRepository->setLimit($count);
        return $this->serviceRepository->findMany();
    }

    public function findUserServices(User $user, ?bool $pagination = true)
    {
        $this->serviceRepository->setPagination($pagination);
        $this->serviceRepository->setPermissions([
            PermissionService::PERMISSION_ADMIN,
            PermissionService::PERMISSION_READ,
        ]);
        return $this->serviceRepository->findModelsByUser(
            new S(),
            $user
        );
    }

    public function getServiceProviderList(S $service, $user): Collection
    {
        $this->accessControlService->setUser($user);

        return $service->providers()->distinct()->get()->filter(function ($provider) {
            return $this->accessControlService->checkPermissionsForEntity(
                $provider,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_READ,
                ],
                false
            );
        });
    }


    public function getAllServicesArray()
    {
        return $this->serviceRepository->findAll()->toArray();
    }

    public function getServiceById($id)
    {
        $getService = $this->serviceRepository->findById($id);
        if ($getService === null) {
            throw new BadRequestHttpException("Service does not exist in database.");
        }
        return $getService;
    }

    public function createService(User $user, array $data)
    {
        if (empty($data['label'])) {
            if ($this->throwException) {
                throw new BadRequestHttpException("Label is required.");
            }
            return false;
        }
        if (empty($data['name'])) {
            $data['name'] = UtilHelpers::labelToName($data['label'], false, '-');
        }
        $checkService = $this->serviceRepository->findUserModelBy(new S(), $user, [
            ['name', '=', $data['name']]
        ], false);

        if ($checkService instanceof S) {
            if ($this->throwException) {
                throw new BadRequestHttpException(sprintf("Service (%s) already exists.", $data['name']));
            }
            return false;
        }
        $createService = $this->serviceRepository->insert($data);
        if (!$createService) {
            return false;
        }
        $addRelations = $this->permissionEntities->setThrowException($this->throwException)->saveUserEntityPermissions(
            $user,
            $this->serviceRepository->getModel(),
            ['name' => PermissionService::PERMISSION_ADMIN]
        );
        if (!$addRelations) {
            return false;
        }
        $this->responseKeysService->setThrowException($this->throwException);
        return $this->responseKeysService->createDefaultServiceResponseKeys($this->serviceRepository->getModel());
    }

    public function updateService(S $service, array $data)
    {
        $this->serviceRepository->setModel($service);
        return $this->serviceRepository->save($data);
    }

    public function deleteServiceById(int $serviceId)
    {
        $service = $this->serviceRepository->findById($serviceId);
        if ($service === null) {
            throw new BadRequestHttpException(sprintf("Service id: %s not found in database.", $serviceId));
        }
        $this->serviceRepository->setModel($service);
        return $this->serviceRepository->delete();
    }

    public function deleteService(S $service)
    {
        $this->serviceRepository->setModel($service);
        return $this->serviceRepository->delete();
    }

    public function deleteBatch(array $ids)
    {
        if (!count($ids)) {
            throw new BadRequestHttpException("No service ids provided.");
        }
        return $this->serviceRepository->deleteBatch($ids);
    }

    public function getServiceRepository(): SRepository
    {
        return $this->serviceRepository;
    }
}
