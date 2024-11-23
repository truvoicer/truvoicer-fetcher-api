<?php

namespace App\Services\Tools\Importer\Entities;

use App\Enums\Import\ImportConfig;
use App\Enums\Import\ImportMappingType;
use App\Enums\Import\ImportType;
use App\Models\S;
use App\Services\ApiServices\ApiService;
use App\Services\Permission\AccessControlService;
use App\Services\Permission\PermissionService;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SImporterService extends ImporterBase
{

    public function __construct(
        private ApiService $apiService,
        protected AccessControlService $accessControlService
    )
    {
        parent::__construct($accessControlService, new S());
    }

    protected function setConfig(): void
    {
        $this->buildConfig(
            true,
            'id',
            ImportType::SERVICE->value,
            'Services',
            'name',
            'label',
            'label',
            [],
        );
    }

    protected function setMappings(): void
    {
        $this->mappings = [
            [
                'name' => ImportMappingType::SELF_NO_CHILDREN->value,
                'label' => 'Import service (no children)',
                'dest' => 'root',
                'required_fields' => ['id', 'name'],
            ],
            [
                'name' => ImportMappingType::SELF_WITH_CHILDREN->value,
                'label' => 'Import service (including children)',
                'dest' => 'root',
                'required_fields' => ['id', 'name'],
            ],
        ];
    }

    public function import(array $data, array $mappings = []): array
    {
        return array_map(function (array $map) {
            return match ($map['mapping']['name']) {
                'service_no_children' => $this->importServiceNoChildren($this->filterMapData($map)),
                'service_include_children' => $this->importServiceIncludeChildren($this->filterMapData($map)),
                default => [
                    'success' => false,
                    'data' => $map['data'],
                ],
            };
        }, $mappings);
    }

    public function importSelfNoChildren(array $map, array $data): array {

        return [
            'success' => true,
        ];
    }

    public function importSelfWithChildren(array $map, array $data): array {

        return [
            'success' => true,
        ];
    }

    public function importServiceNoChildren(array $data): array
    {
        try {
            $this->apiService->createService(
                $this->getUser(),
                $data
            );
            return [
                'success' => true,
                'data' => $this->apiService->getServiceRepository()->getModel()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'data' => $data,
                'error' => $e->getMessage()
            ];
        }
    }
    public function importServiceIncludeChildren(array $data): array
    {
        try {
            $this->apiService->createService(
                $this->getUser(),
                $data
            );

            return [
                'success' => true,
                'data' => $this->apiService->getServiceRepository()->getModel()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'data' => $data,
                'error' => $e->getMessage()
            ];
        }
    }

    public function getImportMappings(array $data)
    {
        return [];
    }
    public function validateImportData(array $data): void {
        $this->compareKeysWithModelFields($data);
    }
    public function filterImportData(array $data): array {
        $filter = array_filter($data, function ($service) {
            return $this->compareItemKeysWithModelFields($service);
        });
        return [
            'root' => true,
            'import_type' => $this->getConfigItem(ImportConfig::NAME),
            'label' => $this->getConfigItem(ImportConfig::LABEL),
            'children' => $this->parseEntityBatch($filter)
        ];
    }
    public function getExportData(): array {
        return $this->apiService->findUserServices(
            $this->getUser(),
            false
        )->toArray();
    }

    public function getExportTypeData($item): bool|array
    {
        return $this->apiService->getServiceById($item["id"])->toArray();
    }

    public function parseEntity(array $entity): array {
        $entity['import_type'] = $this->getConfigItem(ImportConfig::NAME);
        return $entity;
    }

    public function parseEntityBatch(array $data): array
    {
        return array_map(function (array $providerData) {
            return $this->parseEntity($providerData);
        }, $data);
    }
    public function getApiService(): ApiService
    {
        return $this->apiService;
    }

}
