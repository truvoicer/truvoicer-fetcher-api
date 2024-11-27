<?php
namespace App\Services\Tools\Importer\Entities;

use App\Enums\Import\ImportAction;
use App\Enums\Import\ImportConfig;
use App\Enums\Import\ImportMappingType;
use App\Enums\Import\ImportType;
use App\Models\Property;
use App\Services\Permission\AccessControlService;
use App\Services\Permission\PermissionService;
use App\Services\Property\PropertyService;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class PropertyImporterService extends ImporterBase {

    public function __construct(
        private PropertyService $propertyService,
        protected AccessControlService $accessControlService
    )
    {
        parent::__construct($accessControlService, new Property());
    }

    protected function setConfig(): void
    {
        $this->buildConfig(
            true,
            'id',
            ImportType::PROPERTY->value,
            'Properties',
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
                'label' => 'Import Property',
                'dest' => null,
                'required_fields' => ['id', 'name', 'label'],
            ],
        ];
    }

    public function import(ImportAction $action, array $data, bool $withChildren): array
    {
        try {
            $checkProvider = $this->propertyService->getPropertyRepository()->addWhere(
                'name',
                $data['name']
            );
            $this->propertyService->getPropertyRepository()->addWhere(
                'label',
                $data['label']
            );
            $property = $this->propertyService->getPropertyRepository()->findOne();
            if (!$property) {
                $data['label'] = $data['name'] = $this->propertyService->getPropertyRepository()->buildCloneEntityStr(
                    $checkProvider,
                    'name',
                    $data['name']
                );
            }
            if (!$this->propertyService->createProperty($data)) {
                return [
                    'success' => false,
                    'message' => "Failed to create property {$data['name']}."
                ];
            }
            return [
                'success' => true,
                'message' => "Property {$data['name']} imported successfully."
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'data' => $data,
                'message' => $e->getMessage()
            ];
        }
    }

    public function importSelfNoChildren(ImportAction $action, array $map, array $data): array {
        return $this->importSelf($action, $map, $data, false);
    }

    public function importSelfWithChildren(ImportAction $action, array $map, array $data): array {
        return $this->importSelf($action, $map, $data, true);
    }

    public function getImportMappings(array $data) {
        return [];
    }
    public function validateImportData(array $data): void {
        foreach ($data as $property) {
            if (empty($property['name'])) {
                $this->addError(
                    'import_type_validation',
                    "Property name is required."
                );
            }
            if (empty($property['label'])) {
                $this->addError(
                    'import_type_validation',
                    "Property label is required."
                );
            }
            if (empty($property['value_type'])) {
                $this->addError(
                    'import_type_validation',
                    "Property value type is required."
                );
            }
        }
    }
    public function filterImportData(array $data): array {
        $filter = array_filter($data, function ($property) {
            return (
                !empty($property['name']) &&
                !empty($property['label']) &&
                !empty($property['value_type'])
            );
        }, ARRAY_FILTER_USE_BOTH);

        return [
            'root' => true,
            'import_type' => $this->getConfigItem(ImportConfig::NAME),
            'label' => $this->getConfigItem(ImportConfig::LABEL),
            'children' => $this->parseEntityBatch($filter)
        ];
    }

    public function getExportData(): array {
        $data = [];
        if ($this->accessControlService->inAdminGroup()) {
            $data = $this->propertyService->findPropertiesByParams(
                $this->getUser(),
                false
            )->toArray();
        }
        return $data;
    }

    public function getExportTypeData($item): bool|array
    {
        return $this->propertyService->getPropertyById($item["id"])->toArray();

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
    public function getPropertyService(): PropertyService
    {
        return $this->propertyService;
    }

}
