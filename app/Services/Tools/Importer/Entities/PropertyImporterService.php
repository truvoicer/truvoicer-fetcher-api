<?php
namespace App\Services\Tools\Importer\Entities;

use App\Models\Property;
use App\Services\Property\PropertyService;

class PropertyImporterService extends ImporterBase {

    public function __construct(
        private PropertyService $propertyService,
    )
    {
        parent::__construct(new Property());
    }

    public function import(array $data, array $mappings = []) {
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
        return array_filter($data, function ($property) {
            return (
                !empty($property['name']) &&
                !empty($property['label']) &&
                !empty($property['value_type'])
            );
        }, ARRAY_FILTER_USE_BOTH);
    }

    public function getPropertyService(): PropertyService
    {
        return $this->propertyService;
    }

}
