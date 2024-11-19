<?php
namespace App\Services\Tools\Importer\Entities;

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
        $this->setConfig([
            "show" => true,
            "id" => "id",
            "name" => "properties",
            "label" => "Properties",
            "nameField" => "property_name",
            "labelField" => "label",
            'children_keys' => [],
            'import_mappings' => [
                [
                    'name' => 'property',
                    'label' => 'Import Property',
                    'source' => 'properties',
                    'dest' => 'root',
                    'required_fields' => ['id', 'name', 'label'],
                ],
            ],
        ]);
        parent::__construct($accessControlService, new Property());
    }

    public function import(array $data, array $mappings = []): array
    {
        return array_map(function (Property $property) {
            return $this->propertyService->getPropertyRepository()->createProperty($property);
        }, $data);
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
            'import_type' => 'properties',
            'label' => 'Properties',
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
        $entity['import_type'] = 'properties';
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
