<?php
namespace App\Services\ApiServices;

use App\Services\Permission\AccessControlService;
use App\Services\Tools\HttpRequestService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ApiServiceEntityService extends ApiService
{
    const ENTITY_NAME = "service";

    protected AccessControlService $accessControlService;

    public function __construct(EntityManagerInterface $entityManager, HttpRequestService $httpRequestService,
                                ResponseKeysService $responseKeysService, TokenStorageInterface $tokenStorage,
                                AccessControlService $accessControlService)
    {
        parent::__construct($entityManager, $httpRequestService, $responseKeysService, $tokenStorage, $accessControlService);
    }

}
