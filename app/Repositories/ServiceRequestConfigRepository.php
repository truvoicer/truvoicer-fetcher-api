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

    public function getModel(): ServiceRequestConfig
    {
        return parent::getModel();
    }

    public function findByParams(ServiceRequest $serviceRequest, string $sort, string $order, int $count)
    {
        return $serviceRequest->serviceRequestConfig()
            ->orderBy($sort, $order)
            ->get();
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

    public function createRequestConfig(ServiceRequest $serviceRequest, array $data)
    {
        $create = $serviceRequest->serviceRequestConfig()->create($data);
        if (!$create->exists) {
            return false;
        }
        $this->setModel($create);
        return true;
    }
}
