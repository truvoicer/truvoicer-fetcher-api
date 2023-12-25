<?php

namespace App\Repositories;

use App\Models\Provider;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestParameter;

class ServiceRequestParameterRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(ServiceRequestParameter::class);
    }

    public function getModel(): ServiceRequestParameter
    {
        return parent::getModel();
    }

    public function findByParams(ServiceRequest $serviceRequest, string $sort, string $order, int $count)
    {
        $this->addWhere('service_request_id', $serviceRequest);
        return $this->findAllWithParams($sort, $order, $count);
    }

    public function getRequestParametersByRequestName(Provider $provider, string $serviceRequestName = null)
    {
        return $provider->serviceRequest()
            ->where('name', $serviceRequestName)
            ->first()
            ->serviceRequestParameter()
            ->get();
    }
}
