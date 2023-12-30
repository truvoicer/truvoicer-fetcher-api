<?php
namespace App\Services\ApiServices\ServiceRequests;

use App\Models\ServiceRequest;
use App\Models\ServiceRequestParameter;
use App\Repositories\ServiceRequestParameterRepository;
use App\Services\BaseService;

class RequestParametersService extends BaseService
{
    private ServiceRequestParameterRepository $requestParametersRepo;

    public function __construct()
    {
        parent::__construct();
        $this->requestParametersRepo = new ServiceRequestParameterRepository();
    }

    public function findByParams(ServiceRequest $serviceRequest,  string $sort, string $order, int $count) {
        return $this->requestParametersRepo->findByParams($serviceRequest, $sort, $order, $count);
    }

    private function getServiceRequestParametersObject(array $data)
    {
        $fields = [
            'name',
            'value',
        ];

        $configData = [];
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $configData[$field] = $data[$field];
            }
        }
        return $configData;
    }

    public function createRequestParameter(ServiceRequest $serviceRequest, array $data)
    {
        return $this->requestParametersRepo->createRequestParameter(
            $serviceRequest,
            $this->getServiceRequestParametersObject($data)
        );
    }

    public function updateRequestParameter(ServiceRequestParameter $serviceRequestParameter, array $data)
    {
        $this->requestParametersRepo->setModel($serviceRequestParameter);
        return $this->requestParametersRepo->save(
            $this->getServiceRequestParametersObject($data)
        );
    }

    public function deleteRequestParameter(ServiceRequestParameter $serviceRequestParameter) {
        $this->requestParametersRepo->setModel($serviceRequestParameter);
        return $this->requestParametersRepo->delete();
    }

    public function getRequestParametersRepo(): ServiceRequestParameterRepository
    {
        return $this->requestParametersRepo;
    }

}
