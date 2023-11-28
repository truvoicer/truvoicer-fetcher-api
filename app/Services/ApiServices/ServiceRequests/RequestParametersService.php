<?php
namespace App\Services\ApiServices\ServiceRequests;

use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestParameter;
use App\Models\ServiceResponseKey;
use App\Repositories\ServiceRepository;
use App\Repositories\ServiceRequestParameterRepository;
use App\Repositories\ServiceRequestRepository;
use App\Repositories\ServiceResponseKeyRepository;
use App\Services\BaseService;
use App\Services\Tools\HttpRequestService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class RequestParametersService extends BaseService
{
    private $entityManager;
    private $httpRequestService;
    private $serviceRepository;
    private $serviceRequestRepository;
    private $requestParametersRepo;
    private $responseKeysRepo;

    public function __construct(EntityManagerInterface $entityManager, HttpRequestService $httpRequestService,
                                TokenStorageInterface $tokenStorage)
    {
        parent::__construct($tokenStorage);
        $this->entityManager = $entityManager;
        $this->httpRequestService = $httpRequestService;
        $this->serviceRepository = new ServiceRepository();
        $this->serviceRequestRepository = new ServiceRequestRepository();
        $this->requestParametersRepo = new ServiceRequestParameterRepository();
        $this->responseKeysRepo = new ServiceResponseKeyRepository();
    }

    public function findByParams(ServiceRequest $serviceRequest,  string $sort, string $order, int $count) {
        return $this->requestParametersRepo->findByParams($serviceRequest, $sort, $order, $count);
    }

    private function getServiceRequestParametersObject(ServiceRequestParameter $requestParameters,
                                                       ServiceRequest $serviceRequest, array $data)
    {
        $requestParameters->setParameterValue($data['parameter_value']);
        $requestParameters->setParameterName($data['parameter_name']);
        $requestParameters->setServiceRequest($serviceRequest);
        return $requestParameters;
    }

    public function createRequestParameter(ServiceRequest $serviceRequest, array $data)
    {
        $requestParameter = $this->getServiceRequestParametersObject(new ServiceRequestParameter(), $serviceRequest, $data);
        if ($this->httpRequestService->validateData($requestParameter)) {
            return $this->requestParametersRepo->save($requestParameter);
        }
        return false;
    }

    public function updateRequestParameter(ServiceRequestParameter $serviceRequestParameter, ServiceRequest $serviceRequest, array $data)
    {
        $requestParameter = $this->getServiceRequestParametersObject($serviceRequestParameter, $serviceRequest, $data);
        if ($this->httpRequestService->validateData($requestParameter)) {
            return $this->requestParametersRepo->save($requestParameter);
        }
        return false;
    }

    public function deleteRequestParameterById(int $id) {
        $requestParameter = $this->requestParametersRepo->find($id);
        if ($requestParameter === null) {
            throw new BadRequestHttpException(sprintf("Service request parameter id: %s not found in database.", $id));
        }
        return $this->deleteRequestParameter($requestParameter);
    }

    public function deleteRequestParameter(ServiceRequestParameter $serviceRequestParameter) {
        return $this->requestParametersRepo->delete($serviceRequestParameter);
    }
}
