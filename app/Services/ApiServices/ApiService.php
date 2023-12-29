<?php
namespace App\Services\ApiServices;

use App\Models\Service;
use App\Models\User;
use App\Repositories\ServiceRepository;
use App\Repositories\ServiceRequestParameterRepository;
use App\Repositories\ServiceRequestRepository;
use App\Services\BaseService;
use App\Helpers\Tools\UtilHelpers;
use App\Services\Permission\PermissionService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ApiService extends BaseService
{

    protected ServiceRepository $serviceRepository;
    protected ServiceRequestRepository $serviceRequestRepository;
    protected ServiceRequestParameterRepository $requestParametersRepo;
    protected ResponseKeysService $responseKeysService;

    /**
     * ApiServicesService constructor.
     * @param ResponseKeysService $responseKeysService
     */
    public function __construct(ResponseKeysService $responseKeysService)
    {
        parent::__construct();
        $this->serviceRepository = new ServiceRepository();
        $this->serviceRequestRepository = new ServiceRequestRepository();
        $this->requestParametersRepo = new ServiceRequestParameterRepository();
        $this->responseKeysService = $responseKeysService;
    }

    public function findByQuery(string $query)
    {
        return $this->serviceRepository->findByQuery($query);
    }
    public function findByParams(string $sort = "name", ?string $order = "asc", ?int $count= null) {
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
            new Service(),
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
        $checkService = $this->serviceRepository->findUserModelBy(new Service(), $user, [
            ['name', '=', $data['name']]
        ], false);

        if ($checkService instanceof Service) {
            throw new BadRequestHttpException(sprintf("Service (%s) already exists.", $data['name']));
        }
        $createService = $this->serviceRepository->insert($data);
        if (!$createService) {
            return false;
        }
        return $this->permissionEntities->saveUserEntityPermissions(
            $user,
            $this->serviceRepository->getModel(),
            ['name' => PermissionService::PERMISSION_ADMIN]
        );
    }

    public function updateService(Service $service, array $data)
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

    public function deleteService(Service $service) {
        $this->serviceRepository->setModel($service);
        return $this->serviceRepository->delete();
    }

    public function getServiceRepository(): ServiceRepository
    {
        return $this->serviceRepository;
    }
}
