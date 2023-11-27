<?php
namespace App\Services\ApiServices;

use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestParameter;
use App\Repositories\ServiceRepository;
use App\Repositories\ServiceRequestParameterRepository;
use App\Repositories\ServiceRequestRepository;
use App\Services\BaseService;
use App\Services\Permission\AccessControlService;
use App\Services\Tools\HttpRequestService;
use App\Services\Tools\UtilsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ApiService extends BaseService
{
    const SERVICE_ALIAS = "app.service.api_services.api_service_entity_service";

    protected EntityManagerInterface $entityManager;
    protected HttpRequestService $httpRequestService;
    protected ServiceRepository $serviceRepository;
    protected ServiceRequestRepository $serviceRequestRepository;
    protected ServiceRequestParameterRepository $requestParametersRepo;
    protected ResponseKeysService $responseKeysService;
    protected AccessControlService $accessControlService;

    /**
     * ApiServicesService constructor.
     * @param EntityManagerInterface $entityManager
     * @param HttpRequestService $httpRequestService
     * @param ResponseKeysService $responseKeysService
     * @param TokenStorageInterface $tokenStorage
     * @param AccessControlService $accessControlService
     */
    public function __construct(EntityManagerInterface $entityManager, HttpRequestService $httpRequestService,
                                ResponseKeysService $responseKeysService, TokenStorageInterface $tokenStorage,
                                AccessControlService $accessControlService)
    {
        parent::__construct($tokenStorage);
        $this->entityManager = $entityManager;
        $this->httpRequestService = $httpRequestService;
        $this->serviceRepository = $this->entityManager->getRepository(Service::class);
        $this->serviceRequestRepository = $this->entityManager->getRepository(ServiceRequest::class);
        $this->requestParametersRepo = $this->entityManager->getRepository(ServiceRequestParameter::class);
        $this->responseKeysService = $responseKeysService;
        $this->accessControlService = $accessControlService;
    }

    public function findByQuery(string $query)
    {
        return $this->serviceRepository->findByQuery($query);
    }
    public function findByParams(string $sort = "service_name", ?string $order = "asc", ?int $count= null) {
        return $this->serviceRepository->findByParams(
            $sort,
            $order,
            $count
        );
    }

    public function getAllServicesArray() {
        return $this->serviceRepository->getAllServicesArray();
    }

    public function getServiceById($id) {
        $getService = $this->serviceRepository->findOneBy(["id" => $id]);
        if ($getService === null) {
            throw new BadRequestHttpException("Service does not exist in database.");
        }
        return $getService;
    }

    private function getServiceObject(Service $service, array $data)
    {
        $service->setServiceLabel($data['service_label']);
        $service->setServiceName($data['service_name']);
        return $service;
    }

    public function createService(array $data)
    {
        if (!isset($data['service_label']) || empty($data['service_label'])) {
            throw new BadRequestHttpException("Service label is required.");
        }
        $data['service_name'] = UtilsService::labelToName($data['service_label'], false, '-');
        $service = $this->getServiceObject(new Service(), $data);
        if ($this->httpRequestService->validateData($service)) {
            $createService = $this->serviceRepository->saveService($service);
            if ($createService) {
                $this->responseKeysService->createDefaultServiceResponseKeys($createService);
            }
            return $createService;
        }
        return false;
    }

    public function updateService(Service $service, array $data)
    {
        $update = $this->getServiceObject($service, $data);
        if ($this->httpRequestService->validateData($update)) {
            return $this->serviceRepository->saveService($service);
        }
        return false;
    }

    public function deleteServiceById(int $serviceId) {
        $service = $this->serviceRepository->find($serviceId);
        if ($service === null) {
            throw new BadRequestHttpException(sprintf("Service id: %s not found in database.", $serviceId));
        }
        return $this->deleteService($service);
    }

    public function deleteService(Service $service) {
        return $this->serviceRepository->deleteService($service);
    }
}
