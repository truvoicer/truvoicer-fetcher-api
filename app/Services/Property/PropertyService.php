<?php
namespace App\Services\Property;

use App\Models\Property;
use App\Models\Provider;
use App\Models\ProviderProperty;
use App\Models\SrConfig;
use App\Repositories\PropertyRepository;
use App\Services\BaseService;
use App\Services\Permission\AccessControlService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class PropertyService extends BaseService {


    protected PropertyRepository $propertyRepository;

    public function __construct(AccessControlService $accessControlService)
    {
        parent::__construct();
        $this->propertyRepository = new PropertyRepository();
    }

    public function findPropertiesByParams(string $sort = "name", ?string $order = "asc", ?int $count= -1) {
        return $this->propertyRepository->findMany();
    }
    public function getAllPropertiesArray() {
        return $this->propertyRepository->getAllPropertiesArray();
    }

    public static function getPropertyValue(string $valueType, ProviderProperty|SrConfig $property) {
        switch ($valueType) {
            case 'choice':
            case 'text':
                return $property->value;
            case 'list':
            case 'entity_list':
                return $property->array_value;
        }
        return null;
    }

    private function setPropertyObject(array $propertyData) {
        $fields = ['name', 'label', 'value_type', 'value_choices', 'entities'];
        $data = [];
        foreach ($fields as $field) {
            if (isset($propertyData[$field])) {
                $data[$field] = $propertyData[$field];
            }
        }
        if (isset($data['value_choices']) && !is_array($data['value_choices'])) {
            throw new BadRequestHttpException("Property value_choices must be an array.");
        }
        if (isset($data['entities']) && !is_array($data['entities'])) {
            throw new BadRequestHttpException("Property entities must be an array.");
        }
        return $data;
    }

    public function getPropertyByName(string $propertyName) {
        $this->propertyRepository->addWhere("name", $propertyName);
        $property = $this->propertyRepository->findOne();
        if ($property === null) {
            throw new BadRequestHttpException(sprintf("Property name:%s not found in database.",
                $propertyName
            ));
        }
        return $property;
    }
    public function getProviderPropertyByPropertyName(Provider $provider, string $propertyName) {
        return $this->propertyRepository->getProviderPropertyByPropertyName($provider, $propertyName);
    }

    public function getPropertyById(int $propertyId) {
        $property = $this->propertyRepository->findById($propertyId);
        if ($property === null) {
            throw new BadRequestHttpException(sprintf("Property id:%s not found in database.",
                $propertyId
            ));
        }
        return $property;
    }

    public function createProperty(array $propertyData) {
        $property = $this->setPropertyObject($propertyData);
        return $this->propertyRepository->createProperty($property);
    }

    public function updateProperty(Property $property,  $propertyData) {
        $getProperty = $this->setPropertyObject($propertyData);
        return $this->propertyRepository->updateProperty($property, $getProperty);
    }

    public function deletePropertyById(int $propertyId) {
        $property = $this->getPropertyById($propertyId);
        if ($property === null) {
            throw new BadRequestHttpException(sprintf("Property id: %s not found in database.", $propertyId));
        }
        return $this->deleteProperty($property);
    }

    public function deleteProperty(Property $property) {
        return $this->propertyRepository->deleteProperty($property);
    }

    public function deleteBatch(array $ids)
    {
        if (!count($ids)) {
            throw new BadRequestHttpException("No property ids provided.");
        }
        return $this->propertyRepository->deleteBatch($ids);
    }
    public function getPropertyRepository(): PropertyRepository
    {
        return $this->propertyRepository;
    }

}
