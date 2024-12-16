<?php

namespace App\Services\Tools\Importer\Entities;

use App\Enums\Import\ImportAction;
use App\Enums\Import\ImportConfig;
use App\Enums\Import\ImportMappingType;
use App\Enums\Import\ImportType;
use App\Helpers\Tools\UtilHelpers;
use App\Models\S;
use App\Services\ApiServices\ApiService;
use App\Services\Permission\AccessControlService;
use Exception;

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

    protected function loadDependencies(): void
    {
        $this->apiService->setThrowException(false);
    }

    public function lock(ImportAction $action, array $map, array $data, ?array $dest = null): array
    {
        return [
            'success' => true,
            'message' => 'Service import is locked.'
        ];
    }

    protected function overwrite(array $data, bool $withChildren, array $map, ?array $dest = null, ?array $extraData = []): array
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
        } catch (Exception $e) {
            return [
                'success' => false,
                'data' => $data,
                'error' => $e->getMessage()
            ];
        }
    }

    protected function create(array $data, bool $withChildren, array $map, ?array $dest = null, ?array $extraData = []): array
    {
        try {
            $checkService = $this->apiService->getServiceRepository()->findUserModelBy(new S(), $this->getUser(), [
                ['name', '=', $data['name']]
            ], false);

            if ($checkService->first() instanceof S) {
                $data['label'] = $data['name'] = $this->apiService->getServiceRepository()->buildCloneEntityStr(
                    $checkService,
                    'name',
                    $data['name']
                );
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
        } catch (Exception $e) {
            return [
                'success' => false,
                'data' => $data,
                'error' => $e->getMessage()
            ];
        }
    }

    public function importSelfNoChildren(ImportAction $action, array $map, array $data, ?array $dest = null): array {
        return $this->importSelf($action, $map, $data, false, $dest);
    }

    public function importSelfWithChildren(ImportAction $action, array $map, array $data, ?array $dest = null): array {
        return $this->importSelf($action, $map, $data, true, $dest);
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
        } catch (Exception $e) {
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
        } catch (Exception $e) {
            return [
                'success' => false,
                'data' => $data,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getImportMappings(array $data): array
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
    public function deepFind(ImportType $importType, array $data, array $conditions, ?string $operation = 'AND'): array|null {
        return UtilHelpers::deepFindInNestedEntity(
            data: $data,
            conditions: $conditions,
            childrenKeys: ['s_response_key'],
            itemToMatchHandler: function ($item) use ($importType) {
                return match ($importType) {
                    ImportType::SERVICE => [$item],
                    ImportType::S_RESPONSE_KEY => (!empty($item['s_response_key']))? $item['s_response_key'] : [],
                    default => [],
                };
            },
            operation: $operation
        );
    }
    public function getApiService(): ApiService
    {
        return $this->apiService;
    }

}
