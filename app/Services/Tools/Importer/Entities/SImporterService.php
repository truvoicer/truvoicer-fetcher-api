<?php

namespace App\Services\Tools\Importer\Entities;

use App\Enums\Import\ImportAction;
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
        $this->apiService->setThrowException(false);
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

    protected function overwrite(array $data, bool $withChildren): array
    {

        try {
            $checkService = $this->apiService->getServiceRepository()->findUserModelBy(new S(), $this->getUser(), [
                ['name', '=', $data['name']]
            ], false);

            if (!$checkService instanceof S) {
                return [
                    'success' => false,
                    'message' => "Service {$data['name']} not found."
                ];
            }
            if (
                !$this->apiService->updateService(
                    $checkService,
                    $data
                )
            ) {
                return [
                    'success' => false,
                    'message' => "Error updating service {$data['name']}."
                ];
            }
            return [
                'success' => true,
                'message' => "Service {$data['name']} imported successfully,"
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'data' => $data,
                'error' => $e->getMessage()
            ];
        }
    }

    protected function create(array $data, bool $withChildren): array
    {
        try {
            $checkService = $this->apiService->getServiceRepository()->findUserModelBy(new S(), $this->getUser(), [
                ['name', '=', $data['name']]
            ], false);

            if ($checkService instanceof S) {
                return [
                    'success' => false,
                    'message' => "Service {$data['name']} already exists."
                ];
            }
            if (
                !$this->apiService->createService(
                    $this->getUser(),
                    $data
                )
            ) {
                return [
                    'success' => false,
                    'message' => "Error creating service {$data['name']}."
                ];
            }
            return [
                'success' => true,
                'message' => "Service {$data['name']} imported successfully,"
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'data' => $data,
                'error' => $e->getMessage()
            ];
        }
    }

    public function importSelfNoChildren(ImportAction $action, array $map, array $data): array {
        return $this->importSelf($action, $map, $data, false);
    }

    public function importSelfWithChildren(ImportAction $action, array $map, array $data): array {
        return $this->importSelf($action, $map, $data, true);
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
                'message' => $e->getMessage()
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
                'message' => $e->getMessage()
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
