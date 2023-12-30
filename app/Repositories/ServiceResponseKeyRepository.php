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

    public function getModel(): ServiceResponseKey
    {
        return parent::getModel();
    }


    public function getServiceResponseKeyByName(Service $service, string $name)
    {
        return $service->serviceResponseKey()->where('name', $name)->first();
    }

    public function findServiceResponseKeys(Service $service) {
        return $service->serviceResponseKey()->get();
    }

    public function createServiceResponseKey(Service $service, array $data) {
        $create = $service->serviceResponseKey()->create($data);
        $this->setModel($create);
        return true;
    }
    public function saveServiceResponseKey(ServiceResponseKey $serviceResponseKey, array $data) {
        $this->setModel($serviceResponseKey);
        return $this->save($data);
    }
}
