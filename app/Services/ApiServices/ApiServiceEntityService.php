<?php
namespace App\Services\ApiServices;

use App\Services\Permission\AccessControlService;

class ApiServiceEntityService extends ApiService
{
    const ENTITY_NAME = "service";

    protected AccessControlService $accessControlService;

}
