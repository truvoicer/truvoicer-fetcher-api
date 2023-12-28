<?php
namespace App\Services\ApiServices\ServiceRequests;

use App\Models\ServiceRequest;
use App\Models\ServiceRequestParameter;
use App\Repositories\ServiceRepository;
use App\Repositories\ServiceRequestParameterRepository;
use App\Repositories\ServiceRequestRepository;
use App\Repositories\ServiceResponseKeyRepository;
use App\Services\BaseService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class RequestParametersService extends BaseService
{
    private ServiceRepository $serviceRepository;
    private ServiceRequestRepository $serviceRequestRepository;
    private ServiceRequestParameterRepository $requestParametersRepo;
    private ServiceResponseKeyRepository $responseKeysRepo;

    public function __construct()
    {
        parent::__construct();
        $this->serviceRepository = new ServiceRepository();
        $this->serviceRequestRepository = new ServiceRequestRepository();
        $this->requestParametersRepo = new ServiceRequestParameterRepository();
        $this->responseKeysRepo = new ServiceResponseKeyRepository();
    }

    public function findByParams(ServiceRequest $serviceRequest,  string $sort, string $order, int $count) {
        return $this->requestParametersRepo->findByParams($serviceRequest, $sort, $order, $count);
    }

    private function getServiceRequestParametersObject(ServiceRequest $serviceRequest, array $data)
    {
        $data['service_request_id'] = $serviceRequest->id;
        return $data;
    }

    public function createRequestParameter(ServiceRequest $serviceRequest, array $data)
    {
        return $this->requestParametersRepo->save($this->getServiceRequestParametersObject($serviceRequest, $data));
    }

    public function updateRequestParameter(ServiceRequestParameter $serviceRequestParameter, ServiceRequest $serviceRequest, array $data)
    {
        return $this->requestParametersRepo->save($this->getServiceRequestParametersObject($serviceRequest, $data));
    }

    public function deleteRequestParameterById(int $id) {
        $requestParameter = $this->requestParametersRepo->findById($id);
        if ($requestParameter === null) {
            throw new BadRequestHttpException(sprintf("Service request parameter id: %s not found in database.", $id));
        }
        return $this->deleteRequestParameter($requestParameter);
    }

    public function deleteRequestParameter(ServiceRequestParameter $serviceRequestParameter) {
        $this->requestParametersRepo->setModel($serviceRequestParameter);
        return $this->requestParametersRepo->delete();
    }
}
