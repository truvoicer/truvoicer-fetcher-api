<?php
namespace App\Services\ApiServices\ServiceRequests;

use App\Models\Sr;
use App\Models\SrParameter;
use App\Repositories\SrParameterRepository;
use App\Services\BaseService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SrParametersService extends BaseService
{
    private SrParameterRepository $requestParametersRepo;

    public function __construct()
    {
        parent::__construct();
        $this->requestParametersRepo = new SrParameterRepository();
    }

    public function findBySr(Sr $serviceRequest) {
        return $this->requestParametersRepo->findBySr($serviceRequest);
    }
    public function findByParams(Sr $serviceRequest, string $sort, string $order, ?int $count = null) {
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
    public function deleteBatch(array $ids)
    {
        if (!count($ids)) {
            throw new BadRequestHttpException("No service request parameter ids provided.");
        }
        return $this->requestParametersRepo->deleteBatch($ids);
    }

    public function getRequestParametersRepo(): SrParameterRepository
    {
        return $this->requestParametersRepo;
    }

}
