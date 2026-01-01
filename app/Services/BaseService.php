<?php

namespace App\Services;

use App\Models\User;
use Truvoicer\TruFetcherGet\Repositories\UserRepository;
use App\Services\Permission\PermissionEntities;
use App\Traits\Error\ErrorTrait;
use App\Traits\User\UserTrait;

class BaseService
{
    use UserTrait, ErrorTrait;

    protected PermissionEntities $permissionEntities;

    public function __construct()
    {
        $this->permissionEntities = new PermissionEntities();
        $this->userRepository = new UserRepository();
    }

}
