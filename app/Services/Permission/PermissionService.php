<?php
namespace App\Services\Permission;

use App\Models\Permission;
use App\Repositories\PermissionRepository;
use App\Services\BaseService;
use App\Services\Tools\HttpRequestService;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class PermissionService extends BaseService
{
    const PERMISSION_ADMIN = "admin";
    const PERMISSION_READ = "read";
    const PERMISSION_WRITE = "write";
    const PERMISSION_UPDATE = "update";
    const PERMISSION_DELETE = "delete";

    const DEFAULT_PERMISSIONS = [
      [
          'name' => self::PERMISSION_ADMIN,
          'label' => 'Admin'
      ],
      [
          'name' => self::PERMISSION_READ,
          'label' => 'Read'
      ],
      [
          'name' => self::PERMISSION_WRITE,
          'label' => 'Write'
      ],
      [
          'name' => self::PERMISSION_UPDATE,
          'label' => 'Update'
      ],
      [
          'name' => self::PERMISSION_DELETE,
          'label' => 'Delete'
      ],
    ];
    protected PermissionRepository $permissionRepository;

    public function __construct()
    {
        parent::__construct();
        $this->permissionRepository = new PermissionRepository();
    }

    public function findByParams(string $sort, string  $order, int $count = -1) {
        $this->permissionRepository->setOrderDir($order);
        $this->permissionRepository->setSortField($sort);
        $this->permissionRepository->setLimit($count);
        return $this->permissionRepository->findMany();
    }


    public function getPermissionById(int $permissionId) {
        $permission = $this->permissionRepository->findById($permissionId);
        if ($permission === null) {
            throw new BadRequestHttpException(sprintf("Permission id:%s not found in database.",
                $permissionId
            ));
        }
        return $permission;
    }

    public function createPermission(string $name, string $label)
    {
        return $this->permissionRepository->createPermission($name, $label);
    }

    public function updatePermission(Permission $permission, array $data)
    {
        return $this->permissionRepository->savePermission(
            $permission,
            $data
        );
    }

    public function deletePermission(Model $permission)
    {
        $this->permissionRepository->setModel($permission);
        return $this->permissionRepository->delete();
    }

    public function deletePermissionById(int $permissionId)
    {
        $permission = $this->permissionRepository->findById($permissionId);
        return $this->deletePermission($permission);
    }

    public function getPermissionRepository(): PermissionRepository
    {
        return $this->permissionRepository;
    }
}
