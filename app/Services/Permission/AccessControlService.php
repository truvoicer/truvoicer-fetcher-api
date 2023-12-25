<?php

namespace App\Services\Permission;

use App\Models\User;
use App\Services\Auth\AuthService;
use App\Services\User\UserAdminService;
use App\Traits\User\UserTrait;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AccessControlService
{
    use UserTrait;

    protected PermissionEntities $permissionEntities;
    private string $entityName;

    public function __construct(PermissionEntities $permissionEntities)
    {
        $this->permissionEntities = $permissionEntities;
    }

    public function checkPermissionsForEntity(
        object $entityObject,
        array $allowedPermissions = [],
        bool $showException = true
    ) {

//        if ($this->inAdminGroup()) {
//            return true;
//        }
        $serviceObject = $this->getPermissionEntities()->getServiceObjectByEntityName($this->entityName);
        $functionName = sprintf("getUser%sList", ucfirst($this->entityName));
        $this->getPermissionEntities()->validateServiceObjectFunction($serviceObject, $functionName);
        $entityRelations = $serviceObject->$functionName($this->user, $entityObject);
        if ($entityRelations === null) {
            if ($showException) {
                throw new BadRequestHttpException("Access control: operation not permitted");
            }
            return false;
        }
        foreach ($entityRelations->getPermission() as $permission) {
            if ($permission->getName() === PermissionService::PERMISSION_ADMIN) {
                return true;
            }
            if (in_array($permission->getName(), $allowedPermissions)) {
                return true;
            }
        }
        if ($showException) {
            throw new BadRequestHttpException("Access control: operation not permitted");
        }
        return false;
    }

    public function inAdminGroup(): bool
    {
        $user = $this->getUser();
        return (
            UserAdminService::userTokenHasAbility($user, AuthService::ABILITY_SUPERUSER) ||
            UserAdminService::userTokenHasAbility($user, AuthService::ABILITY_ADMIN)
        );
    }

    /**
     * @return PermissionEntities
     */
    public function getPermissionEntities(): PermissionEntities
    {
        return $this->permissionEntities;
    }

    public function setEntityName(string $entityName): void
    {
        $this->entityName = $entityName;
    }

}
