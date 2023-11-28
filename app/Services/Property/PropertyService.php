<?php
namespace App\Services\Property;

use App\Models\Property;
use App\Repositories\PropertyRepository;
use App\Services\BaseService;
use App\Services\Permission\AccessControlService;
use App\Services\Tools\HttpRequestService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class PropertyService extends BaseService {

    const SERVICE_ALIAS = "app.service.property.property_entity_service";

    protected EntityManagerInterface $em;
    protected PropertyRepository $propertyRepository;
    protected HttpRequestService $httpRequestService;
    protected AccessControlService $accessControlService;

    public function __construct(EntityManagerInterface $entityManager, HttpRequestService $httpRequestService,
                                TokenStorageInterface $tokenStorage, AccessControlService $accessControlService)
    {
        parent::__construct($tokenStorage);
        $this->em = $entityManager;
        $this->httpRequestService = $httpRequestService;
        $this->propertyRepository = $this->em->getRepository(Property::class);
        $this->accessControlService = $accessControlService;
    }

    public function findPropertiesByParams(string $sort = "property_name", ?string $order = "asc", ?int $count= null) {
        return $this->propertyRepository->findByParams(
            $sort,
            $order,
            $count
        );
    }
    public function getAllPropertiesArray() {
        return $this->propertyRepository->getAllPropertiesArray();
    }

    private function setPropertyObject(Property $property, array $propertyData) {
        $property->setPropertyName($propertyData['property_name']);
        $property->setPropertyLabel($propertyData['property_label']);
        $property->setValueType($propertyData['value_type']);
        if (isset($propertyData['value_choices']) && is_array($propertyData['value_choices'])) {
            $property->setValueChoices($propertyData['value_choices']);
        }
        return $property;
    }

    public function getPropertyByName(string $propertyName) {
        $property = $this->propertyRepository->findOneBy(["property_name" => $propertyName]);
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
        $property = $this->setPropertyObject(new Property(), $propertyData);
        if ($this->httpRequestService->validateData($property)) {
            return $this->propertyRepository->createProperty($property);
        }
        return false;
    }

    public function updateProperty(Property $property,  $propertyData) {
        $getProperty = $this->setPropertyObject($property, $propertyData);
        if($this->httpRequestService->validateData($getProperty)) {
            return $this->propertyRepository->updateProperty($getProperty);
        }
        return false;
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
