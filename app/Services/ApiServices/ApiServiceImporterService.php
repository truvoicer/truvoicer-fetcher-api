<?php

namespace App\Services\ApiServices;

use App\Models\Service;
use App\Services\Tools\HttpRequestService;

class ApiServiceImporterService extends ApiService
{

    public function __construct(
        ResponseKeysService $responseKeysService
    )
    {
        parent::__construct($responseKeysService);
    }

    public function import(array $data, array $mappings = [])
    {
        return array_map(function (Service $service) {
            $this->serviceRepository->setModel($service);
            return $this->serviceRepository->save($service);
        }, $data);
    }

    public function getImportMappings(array $data)
    {
        return [];
    }
}
