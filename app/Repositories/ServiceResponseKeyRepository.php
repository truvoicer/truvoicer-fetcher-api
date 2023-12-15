<?php

namespace App\Repositories;

use App\Models\Provider;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\ServiceResponseKey;

class ServiceResponseKeyRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(ServiceResponseKey::class);
    }


    public function getServiceResponseKeyByName(Service $service, string $responseKeyName)
    {
        return $service->serviceResponseKey()->where('name', $responseKeyName)->first();
    }

    public function getResponseKeys(Provider $provider, ServiceRequest $serviceRequest)
    {
        return $provider->serviceRequest()
            ->where('id', $serviceRequest->id)
            ->first()
            ->serviceResponseKey()
            ->get();
    }

    public function getRequestResponseKey(ServiceRequest $serviceRequest, ServiceResponseKey $responseKey) {
        $serviceRequestResponseKeyRepo = new ServiceRequestResponseKeyRepository();
        $serviceRequestResponseKeyRepo->addWhere('service_request_id', $serviceRequest->id);
        $serviceRequestResponseKeyRepo->addWhere('service_response_key_id', $responseKey->id);
        return $serviceRequestResponseKeyRepo->findOne();
    }
}
