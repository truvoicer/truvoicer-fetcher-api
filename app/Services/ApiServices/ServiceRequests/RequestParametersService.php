<?php
namespace App\Services\ApiServices\ServiceRequests;

use App\Models\Sr;
use App\Models\SrParameter;
use App\Repositories\SrParameterRepository;
use App\Services\BaseService;

class RequestParametersService extends BaseService
{
    private SrParameterRepository $requestParametersRepo;

    public function __construct()
    {
        parent::__construct();
        $this->requestParametersRepo = new SrParameterRepository();
    }

    public function findByParams(Sr $serviceRequest, string $sort, string $order, int $count) {
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

    public function createRequestParameter(Sr $serviceRequest, array $data)
    {
        return $this->requestParametersRepo->createRequestParameter(
            $serviceRequest,
            $this->getServiceRequestParametersObject($data)
        );
    }

    public function updateRequestParameter(SrParameter $serviceRequestParameter, array $data)
    {
        $this->requestParametersRepo->setModel($serviceRequestParameter);
        return $this->requestParametersRepo->save(
            $this->getServiceRequestParametersObject($data)
        );
    }

    public function deleteRequestParameter(SrParameter $serviceRequestParameter) {
        $this->requestParametersRepo->setModel($serviceRequestParameter);
        return $this->requestParametersRepo->delete();
    }

    public function getRequestParametersRepo(): SrParameterRepository
    {
        return $this->requestParametersRepo;
    }

}
