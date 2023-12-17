<?php
namespace App\Services\Property;

use App\Services\Permission\AccessControlService;

class PropertyEntityService extends PropertyService
{
    const ENTITY_NAME = "property";

    protected AccessControlService $accessControlService;

    public function __construct(AccessControlService $accessControlService)
    {
        parent::__construct($accessControlService);
    }

}
