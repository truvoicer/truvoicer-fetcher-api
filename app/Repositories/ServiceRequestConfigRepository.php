<?php

namespace App\Repositories;

use App\Models\Provider;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestConfig;

class ServiceRequestConfigRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(ServiceRequestConfig::class);
    }

    public function findByParams(ServiceRequest $serviceRequest, string $sort, string $order, int $count)
    {
        $this->addWhere('service_request_id', $serviceRequest);
        return $this->findAllWithParams($sort, $order, $count);
    }

    public function getRequestConfigByName(Provider $provider, ServiceRequest $serviceRequest, string $configItemName)
    {
        return $provider->serviceRequest()
            ->where('id', $serviceRequest->id)
            ->first()
            ->serviceRequestConfig()
            ->where('name', $configItemName)
            ->first();
    }
}
