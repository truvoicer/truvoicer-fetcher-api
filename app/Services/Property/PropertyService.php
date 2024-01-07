<?php
namespace App\Services\Property;

use App\Models\Property;
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
        $this->propertyRepository->setOrderDir($order);
        $this->propertyRepository->setSortField($sort);
        $this->propertyRepository->setLimit($count);
        return $this->propertyRepository->findMany();
    }
    public function getAllPropertiesArray() {
        return $this->propertyRepository->getAllPropertiesArray();
    }

    private function setPropertyObject(array $propertyData) {
        $fields = ['name', 'label', 'value_type', 'value_choices'];
        $data = [];
        foreach ($fields as $field) {
            if (isset($propertyData[$field])) {
                $data[$field] = $propertyData[$field];
            }
        }
        if (isset($data['value_choices']) && !is_array($data['value_choices'])) {
            throw new BadRequestHttpException("Property value_choices must be an array.");
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

    public function getPropertyRepository(): PropertyRepository
    {
        return $this->propertyRepository;
    }

}
