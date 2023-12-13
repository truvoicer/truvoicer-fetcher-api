<?php

namespace App\Repositories;

use App\Models\Provider;
use App\Models\Service;

class ServiceRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(Service::class);
    }

    public function getAllServicesArray() {
        return $this->findAll();
    }

    public function findByQuery($query)
    {
        return $this->findByLabelOrName($query);
    }

    public function saveService(array $data)
    {
        return $this->save($data);
    }

    public function getServiceByRequestName(Provider $provider, string $serviceName) {
        return $provider->serviceRequest()->where('name', $serviceName)->first();
    }

    public function findByParams(string $sort, string  $order, ?int $count)
    {
        return $this->findAllWithParams($sort, $order, $count);
    }

    public function deleteService(Service $service) {
        $this->setModel($service);
        return $this->delete();
    }
}
