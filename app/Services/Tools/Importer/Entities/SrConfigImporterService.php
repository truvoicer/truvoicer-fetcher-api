<?php

namespace App\Services\Tools\Importer\Entities;

use App\Enums\Import\ImportAction;
use App\Enums\Import\ImportConfig;
use App\Enums\Import\ImportMappingType;
use App\Enums\Import\ImportType;
use App\Helpers\Tools\UtilHelpers;
use App\Models\Property;
use App\Models\Sr;
use App\Models\SrConfig;
use App\Services\ApiServices\ServiceRequests\SrConfigService;
use App\Services\ApiServices\ServiceRequests\SrService;
use App\Services\Permission\AccessControlService;
use Exception;
use Illuminate\Database\Eloquent\Model;

class SrConfigImporterService extends ImporterBase
{

    public function __construct(
        private SrConfigService         $srConfigService,
        private PropertyImporterService $propertyImporterService,
        protected AccessControlService  $accessControlService
    )
    {
        parent::__construct($accessControlService, new SrConfig());
    }

    protected function loadDependencies(): void
    {
        $this->srService->setThrowException(false);
        $this->srConfigService->setThrowException(false);
        $this->propertyImporterService->setThrowException(false);
        $this->propertyImporterService->setUser($this->getUser());
    }

    protected function setConfig(): void
    {
        $this->buildConfig(
            false,
            'id',
            ImportType::SR_CONFIG->value,
            'Sr Config',
            'value',
            '{property.label}: {value}',
            'label',
        );
    }

    protected function setMappings(): void
    {
        $this->mappings = [
            [
                'name' => ImportMappingType::SELF_NO_CHILDREN->value,
                'label' => 'Import sr config to sr',
                'dest' => ImportType::SR->value,
                'required_fields' => ['id'],
            ],
        ];
    }
    private function saveSrConfig(Sr $sr, Property $property, array $data): array
    {
        if (!$this->srConfigService->saveRequestConfig($sr, $property, array_merge($data, $data['property']))) {
            return [
                'success' => false,
                'message' => "Failed to create sr config for Sr {$sr->name}",
                'data' => $data
            ];
        }
        return [
            'success' => true,
            'message' => "Sr Config for Sr {$sr->name} imported successfully."
        ];
    }

    protected function overwrite(array $data, bool $withChildren, array $map, ?array $dest = null): array
    {
        try {
            $sr = $this->findSr($data, $map, $dest);
            if (!$sr['success']) {
                return $sr;
            }

            $sr = $sr['sr'];
            $property = $this->findProperty($sr, $data);
            if (!$property['success']) {
                $property = $this->propertyImporterService->import(
                    ImportAction::CREATE,
                    $data['property'],
                    $withChildren,
                    $map
                );
                if (!$property['success']) {
                    return $property;
                }
                $property = $this->propertyImporterService->getPropertyService()->getPropertyRepository()->getModel();
            } else {
                $property = $property['property'];
            }

            return $this->saveSrConfig($sr, $property, $data);
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => $data
            ];
        }
    }

    protected function create(array $data, bool $withChildren, array $map, ?array $dest = null): array
    {
        try {
            $sr = $this->findSr($data, $map, $dest);
            if (!$sr['success']) {
                return $sr;
            }
            $sr = $sr['sr'];
            $property = $this->findProperty($sr, $data);
            if (!$property['success']) {
                return [
                    'success' => false,
                    'message' => "Failed to find property for Sr {$sr->name}",
                    'data' => $data
                ];
            }
            $property = $property['property'];
            return $this->saveSrConfig($sr, $property, $data);
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => $data
            ];
        }
    }

    public function findProperty(Sr $sr, array $data): array
    {
        if (empty($data['property'])) {
            return [
                'success' => false,
                'message' => "Sr config property is required for sr id: {$sr->id}."
            ];
        }
        $this->propertyImporterService->getPropertyService()->getPropertyRepository()->addWhere(
            'name',
            $data['property']['name']
        );
        $property = $this->propertyImporterService->getPropertyService()->getPropertyRepository()->findOne();
        if (!$property instanceof Model) {
            return [
                'success' => false,
                'message' => "Sr config property not found for property name: {$data['property']['name']}"
            ];
        }
        return [
            'success' => true,
            'property' => $property
        ];
    }


    public function importSelfNoChildren(ImportAction $action, array $map, array $data, ?array $dest = null): array
    {
        return $this->importSelf($action, $map, $data, false, $dest);
    }

    public function importSelfWithChildren(ImportAction $action, array $map, array $data, ?array $dest = null): array
    {
        return $this->importSelf($action, $map, $data, true, $dest);
    }

    public function getImportMappings(array $data): array
    {
        return [];
    }

    public function validateImportData(array $data): void
    {
        if (empty($sr['value']) && empty($sr['array_value'])) {
            $this->addError(
                'import_type_validation',
                "Service Request name is required."
            );
        }
    }

    public function filterImportData(array $data): array
    {
        return [
            'root' => true,
            'import_type' => $this->getConfigItem(ImportConfig::NAME),
            'label' => $this->getConfigItem(ImportConfig::LABEL),
            'children' => $this->parseEntityBatch($data)
        ];
    }

    public function parseEntity(array $entity): array
    {
        $entity['import_type'] = $this->getConfigItem(ImportConfig::NAME);
        return $entity;
    }

    public function parseEntityBatch(array $data): array
    {
        return array_map(function (array $providerData) {
            return $this->parseEntity($providerData);
        }, $data);
    }

    public function getSrConfigService(): SrConfigService
    {
        return $this->srConfigService;
    }

    public function getExportData(): array
    {
        return [];
    }

    public function deepFind(ImportType $importType, array $data, array $conditions, ?string $operation = 'AND'): array|null {
        return UtilHelpers::deepFindInNestedEntity(
            data: $data,
            conditions: $conditions,
            childrenKeys: ['srs', 'sr', 'child_srs'],
            itemToMatchHandler: function ($item) use ($importType) {
                return match ($importType) {
                    ImportType::SR_CONFIG => (!empty($item['sr_config']))? $item['sr_config'] : [],
                    default => [],
                };
            },
            operation: $operation
        );
    }

    public function getExportTypeData($item): array|bool
    {
        return [];
    }
}
