<?php
namespace App\Services\Property;

use App\Models\Property;
use App\Services\Permission\AccessControlService;
use App\Services\Tools\HttpRequestService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class PropertyImporterService extends PropertyService {

    public function __construct(EntityManagerInterface $entityManager, HttpRequestService $httpRequestService,
                                TokenStorageInterface $tokenStorage, AccessControlService $accessControlService) {
        parent::__construct($entityManager, $httpRequestService, $tokenStorage, $accessControlService);
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
