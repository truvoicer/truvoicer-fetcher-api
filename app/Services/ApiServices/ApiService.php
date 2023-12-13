<?php
namespace App\Services\ApiServices;

use App\Models\Service;
use App\Repositories\ServiceRepository;
use App\Repositories\ServiceRequestParameterRepository;
use App\Repositories\ServiceRequestRepository;
use App\Services\BaseService;
use App\Services\Permission\AccessControlService;
use App\Services\Tools\HttpRequestService;
use App\Services\Tools\UtilsService;;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ApiService extends BaseService
{
    const SERVICE_ALIAS = "app.service.api_services.api_service_entity_service";

    protected HttpRequestService $httpRequestService;
    protected ServiceRepository $serviceRepository;
    protected ServiceRequestRepository $serviceRequestRepository;
    protected ServiceRequestParameterRepository $requestParametersRepo;
    protected ResponseKeysService $responseKeysService;
    protected AccessControlService $accessControlService;

    /**
     * ApiServicesService constructor.
     * @param HttpRequestService $httpRequestService
     * @param ResponseKeysService $responseKeysService
     * @param AccessControlService $accessControlService
     */
    public function __construct(HttpRequestService $httpRequestService,
                                ResponseKeysService $responseKeysService,
                                AccessControlService $accessControlService)
    {
        $this->httpRequestService = $httpRequestService;
        $this->serviceRepository = new ServiceRepository();
        $this->serviceRequestRepository = new ServiceRequestRepository();
        $this->requestParametersRepo = new ServiceRequestParameterRepository();
        $this->responseKeysService = $responseKeysService;
        $this->accessControlService = $accessControlService;
    }

    public function findByQuery(string $query)
    {
        return $this->serviceRepository->findByQuery($query);
    }
    public function findByParams(string $sort = "name", ?string $order = "asc", ?int $count= null) {
        $this->serviceRepository->setOrderBy($order);
        $this->serviceRepository->setSort($sort);
        $this->serviceRepository->setLimit($count);
        return $this->serviceRepository->findMany();
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

    public function createService(array $data)
    {
        if (empty($data['label'])) {
            throw new BadRequestHttpException("Label is required.");
        }
        $data['service_name'] = UtilsService::labelToName($data['service_label'], false, '-');
        $createService = $this->serviceRepository->insert($data);
        if (!$createService) {
            return false;
        }
        return $this->responseKeysService->createDefaultServiceResponseKeys($this->serviceRepository->getModel());
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
        return $this->serviceRepository->delete();
    }
}
