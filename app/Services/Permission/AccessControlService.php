<?php
namespace App\Services\Permission;

use App\Models\User;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class AccessControlService
{
    protected PermissionEntities $permissionEntities;
    protected AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(PermissionEntities $permissionEntities, AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->permissionEntities = $permissionEntities;
        $this->authorizationChecker = $authorizationChecker;
    }

    public function checkPermissionsForEntity(string $entity, object $entityObject, User $user, array $allowedPermissions = [],
                                              bool $showException = true) {
        $serviceObject = $this->getPermissionEntities()->getServiceObjectByEntityName($entity);
        $functionName = sprintf("getUser%sList", ucfirst($entity));
        $this->getPermissionEntities()->validateServiceObjectFunction($serviceObject, $functionName);
        $entityRelations = $serviceObject->$functionName($user, $entityObject);
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
         return (
            $this->getAuthorizationChecker()->isGranted('ROLE_SUPER_ADMIN') ||
            $this->getAuthorizationChecker()->isGranted('ROLE_ADMIN')
        );
    }

    /**
     * @return PermissionEntities
     */
    public function getPermissionEntities(): PermissionEntities
    {
        return $this->permissionEntities;
    }

    /**
     * @return AuthorizationCheckerInterface
     */
    public function getAuthorizationChecker(): AuthorizationCheckerInterface
    {
        return $this->authorizationChecker;
    }
}
