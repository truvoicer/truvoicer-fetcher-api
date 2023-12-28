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

    public function findPropertiesByParams(string $sort = "name", ?string $order = "asc", ?int $count= null) {
        $this->propertyRepository->setOrderDir($order);
        $this->propertyRepository->setSortField($sort);
        $this->propertyRepository->setLimit($count);
        return $this->propertyRepository->findMany();
    }
    public function getAllPropertiesArray() {
        return $this->propertyRepository->getAllPropertiesArray();
    }

    private function setPropertyObject(array $propertyData) {
        $data = [];
        $data['name'] = $propertyData['name'];
        $data['label'] = $propertyData['label'];
        $data['value_type'] = $propertyData['value_type'];
        if (isset($propertyData['value_choices']) && is_array($propertyData['value_choices'])) {
            $data['value_choices'] = $propertyData['value_choices'];
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
        $getProperty['id'] = $property->id;
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
}
