<?php
namespace App\Services\Permission;

use App\Models\Permission;
use App\Repositories\PermissionRepository;
use App\Services\BaseService;
use App\Services\Tools\HttpRequestService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class PermissionService extends BaseService
{
    const PERMISSION_ADMIN = "admin";
    const PERMISSION_READ = "read";
    const PERMISSION_WRITE = "write";
    const PERMISSION_UPDATE = "update";
    const PERMISSION_DELETE = "delete";

    protected EntityManagerInterface $entityManager;
    protected HttpRequestService $httpRequestService;
    protected PermissionRepository $permissionRepository;

    public function __construct(EntityManagerInterface $entityManager, HttpRequestService $httpRequestService,
                                TokenStorageInterface $tokenStorage)
    {
        parent::__construct($tokenStorage);
        $this->entityManager = $entityManager;
        $this->httpRequestService = $httpRequestService;
        $this->permissionRepository = new PermissionRepository();
    }

    public function findByParams(string $sort, string  $order, int $count) {
        $this->permissionRepository->setOrderBy($order);
        $this->permissionRepository->setSort($sort);
        $this->permissionRepository->setLimit($count);
        return $this->permissionRepository->findMany();
    }


    public function getPermissionById(int $permissionId) {
        $permission = $this->permissionRepository->find($permissionId);
        if ($permission === null) {
            throw new BadRequestHttpException(sprintf("Permission id:%s not found in database.",
                $permissionId
            ));
        }
        return $permission;
    }

    public function createPermission($name)
    {
        $this->httpRequestService->validateData(
            $this->permissionRepository->buildPermissionData($name)
        );
        return $this->permissionRepository->createPermission($name);
    }

    public function updatePermission(Permission $permission, $name)
    {
        return $this->permissionRepository->savePermission(
            $permission,
            $this->permissionRepository->buildPermissionData($name)
        );
    }

    public function deletePermission(Permission $permission)
    {
        return $this->permissionRepository->delete($permission);
    }

    public function deletePermissionById(int $permissionId)
    {
        $permission = $this->permissionRepository->find($permissionId);
        return $this->permissionRepository->delete($permission);
    }
}
