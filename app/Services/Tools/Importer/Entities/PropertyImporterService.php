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
    public function validateImportData(array $data): bool {
        return [];
    }
}
