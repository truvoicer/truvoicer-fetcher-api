<?php
namespace App\Services\ApiServices;

use App\Models\Service;
use App\Services\Permission\AccessControlService;
use App\Services\Tools\HttpRequestService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ApiServiceImporterService extends ApiService
{

    public function __construct(EntityManagerInterface $entityManager, HttpRequestService $httpRequestService,
                                ResponseKeysService $responseKeysService, TokenStorageInterface $tokenStorage,
                                AccessControlService $accessControlService) {
        parent::__construct($entityManager, $httpRequestService, $responseKeysService, $tokenStorage, $accessControlService);
    }

    public function import(array $data, array $mappings = []) {
        return array_map(function (Service $service) {
            return $this->serviceRepository->saveService($service);
        }, $data);
    }

    public function getImportMappings(array $data) {
        return [];
    }
}
