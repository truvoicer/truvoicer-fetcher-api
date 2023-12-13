<?php

namespace App\Repositories;

use App\Models\Permission;
use App\Services\Tools\UtilsService;

class PermissionRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(Permission::class);
    }
    public function findByParams(string $sort, string  $order, int $count)
    {
        return $this->findAllWithParams($sort, $order, $count);
    }

    public function savePermission(Permission $permission, array $data) {
        $this->setModel($permission);
        return $this->save($data);
    }


    public function buildPermissionData(?string $name) {
        return [
            'name' => UtilsService::labelToName($name),
            'label' => $name
        ];
    }

    public function createPermission(string $name) {
        return $this->insert($this->buildPermissionData($name));
    }
}
