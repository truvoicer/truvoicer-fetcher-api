<?php
namespace App\Services\ApiServices;

use App\Models\S;
use App\Models\User;
use App\Repositories\SRepository;
use App\Repositories\SrParameterRepository;
use App\Repositories\SrRepository;
use App\Services\BaseService;
use App\Helpers\Tools\UtilHelpers;
use App\Services\Permission\PermissionService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ApiService extends BaseService
{

    protected SRepository $serviceRepository;
    protected SrRepository $serviceRequestRepository;
    protected SrParameterRepository $requestParametersRepo;
    protected SResponseKeysService $responseKeysService;

    /**
     * ApiServicesService constructor.
     * @param SResponseKeysService $responseKeysService
     */
    public function __construct(SResponseKeysService $responseKeysService)
    {
        parent::__construct();
        $this->serviceRepository = new SRepository();
        $this->serviceRequestRepository = new SrRepository();
        $this->requestParametersRepo = new SrParameterRepository();
        $this->responseKeysService = $responseKeysService;
    }

    public function findByQuery(string $query)
    {
        return $this->serviceRepository->findByQuery($query);
    }
    public function findByParams(string $sort = "name", ?string $order = "asc", int $count= -1) {
        $this->serviceRepository->setOrderDir($order);
        $this->serviceRepository->setSortField($sort);
        $this->serviceRepository->setLimit($count);
        return $this->serviceRepository->findMany();
    }

    public function findUserServices(User $user, string $sort, string $order, ?int $count) {
        $this->serviceRepository->setPermissions([
            PermissionService::PERMISSION_ADMIN,
            PermissionService::PERMISSION_READ,
        ]);
        return $this->serviceRepository->findModelsByUser(
            new S(),
            $user
        );
    }
    public function getAllServicesArray() {
        return $this->serviceRepository->findAll()->toArray();
    }

    public function getServiceById($id) {
        $getService = $this->serviceRepository->findById($id);
        if ($getService === null) {
            throw new BadRequestHttpException("Service does not exist in database.");
        }
        return $getService;
    }

    public function createService(User $user, array $data)
    {
        if (empty($data['label'])) {
            throw new BadRequestHttpException("Label is required.");
        }
        if (empty($data['name'])) {
            $data['name'] = UtilHelpers::labelToName($data['label'], false, '-');
        }
        $checkService = $this->serviceRepository->findUserModelBy(new S(), $user, [
            ['name', '=', $data['name']]
        ], false);

        if ($checkService instanceof S) {
            throw new BadRequestHttpException(sprintf("Service (%s) already exists.", $data['name']));
        }
        $createService = $this->serviceRepository->insert($data);
        if (!$createService) {
            return false;
        }
        $addRelations = $this->permissionEntities->saveUserEntityPermissions(
            $user,
            $this->serviceRepository->getModel(),
            ['name' => PermissionService::PERMISSION_ADMIN]
        );
        if (!$addRelations) {
            return false;
        }
        return $this->responseKeysService->createDefaultServiceResponseKeys($this->serviceRepository->getModel());
    }

    public function updateService(S $service, array $data)
    {
        $this->serviceRepository->setModel($service);
        return $this->serviceRepository->save($data);
    }

    public function deleteServiceById(int $serviceId) {
        $service = $this->serviceRepository->findById($serviceId);
        if ($service === null) {
            throw new BadRequestHttpException(sprintf("Service id: %s not found in database.", $serviceId));
        }
        $this->serviceRepository->setModel($service);
        return $this->serviceRepository->delete();
    }

    public function deleteService(S $service) {
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
