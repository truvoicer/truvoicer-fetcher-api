<?php
namespace App\Services\Property;

use App\Models\Property;
use App\Services\Permission\AccessControlService;

class PropertyImporterService extends PropertyService {

    public function __construct(AccessControlService $accessControlService) {
        parent::__construct($accessControlService);
    }

    public function import(array $data, array $mappings = []) {
        return array_map(function (Property $property) {
            return $this->propertyRepository->createProperty($property);
        }, $data);
    }

    public function getImportMappings(array $data) {
        return [];
    }
}
