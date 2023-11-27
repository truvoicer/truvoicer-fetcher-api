<?php
namespace App\Services\Property;

use App\Services\Permission\AccessControlService;
use App\Services\Tools\HttpRequestService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class PropertyEntityService extends PropertyService
{
    const ENTITY_NAME = "property";

    protected AccessControlService $accessControlService;

    public function __construct(EntityManagerInterface $entityManager, HttpRequestService $httpRequestService,
                                TokenStorageInterface $tokenStorage, AccessControlService $accessControlService)
    {
        parent::__construct($entityManager, $httpRequestService, $tokenStorage, $accessControlService);
    }

}
