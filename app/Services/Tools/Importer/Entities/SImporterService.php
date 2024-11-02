<?php

namespace App\Services\Tools\Importer\Entities;

use App\Models\S;
use App\Services\ApiServices\ApiService;
use App\Services\Permission\AccessControlService;
use App\Services\Permission\PermissionService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SImporterService extends ImporterBase
{

    public function __construct(
        private ApiService $apiService,
        protected AccessControlService $accessControlService
    )
    {
        $this->setConfig([
            "show" => false,
            "id" => "id",
            "name" => "services",
            "label" => "Services",
            "nameField" => "name",
            "labelField" => "label",
            'import_mappings' => [],
        ]);
        parent::__construct($accessControlService, new S());
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
    public function getExportData(): array {
        return $this->apiService->findUserServices(
            $this->getUser(),
            false
        )->toArray();
    }

    public function getExportTypeData($item)
    {
        return $this->apiService->getServiceById($item["id"]);
    }
    public function getApiService(): ApiService
    {
        return $this->apiService;
    }

}
