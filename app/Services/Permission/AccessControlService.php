<?php

namespace App\Services\Permission;

use App\Models\User;
use App\Services\Auth\AuthService;
use App\Services\User\UserAdminService;
use App\Traits\User\UserTrait;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AccessControlService
{
    use UserTrait;

    protected PermissionEntities $permissionEntities;

    public function __construct(PermissionEntities $permissionEntities)
    {
        $this->permissionEntities = $permissionEntities;
    }

    public function checkPermissionsForEntity(
        Model $entityObject,
        array $allowedPermissions = [],
        bool $showException = true
    ) {
//        if ($this->inAdminGroup()) {
//            return true;
//        }

        $permissions = $this->permissionEntities->userHasEntityPermissions($this->user, $entityObject, $allowedPermissions);

        if (!$permissions) {
            if ($showException) {
                throw new BadRequestHttpException("Access control: operation not permitted");
            }
            return false;
        }

        return true;
    }

    public function inAdminGroup(): bool
    {
        $user = $this->getUser();
        return (
            UserAdminService::userTokenHasAbility($user, AuthService::ABILITY_SUPERUSER)
        );
    }

    /**
     * @return PermissionEntities
     */
    public function getPermissionEntities(): PermissionEntities
    {
        return $this->permissionEntities;
    }

}
