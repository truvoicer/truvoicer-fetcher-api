<?php

namespace App\Services\Tools\Importer\Entities;

use App\Models\S;
use App\Services\ApiServices\ApiService;

class SImporterService extends ImporterBase
{

    public function __construct(
        private ApiService $apiService,
    )
    {
        parent::__construct(new S());
    }

    public function import(array $data, array $mappings = [])
    {
        return array_map(function (S $service) {
            $this->apiService->getServiceRepository()->setModel($service);
            return $this->apiService->getServiceRepository()->save($service);
        }, $data);
    }

    public function getImportMappings(array $data)
    {
        return [];
    }
    public function validateImportData(array $data): void {
        $this->compareKeysWithModelFields($data);
    }
    public function filterImportData(array $data): array {
        return array_filter($data, function ($service) {
            return $this->compareItemKeysWithModelFields($service);
        });
    }

    public function getApiService(): ApiService
    {
        return $this->apiService;
    }

}
