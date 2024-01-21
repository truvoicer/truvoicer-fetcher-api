<?php

namespace App\Services\ApiServices;

use App\Models\S;
use App\Services\Tools\HttpRequestService;

class ApiServiceImporterService extends ApiService
{

    public function __construct(
        SResponseKeysService $responseKeysService
    )
    {
        parent::__construct($responseKeysService);
    }

    public function import(array $data, array $mappings = [])
    {
        return array_map(function (S $service) {
            $this->serviceRepository->setModel($service);
            return $this->serviceRepository->save($service);
        }, $data);
    }

    public function getImportMappings(array $data)
    {
        return [];
    }
}
