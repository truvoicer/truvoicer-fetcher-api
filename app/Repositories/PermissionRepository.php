<?php

namespace App\Repositories;

use App\Models\Permission;
use App\Helpers\Tools\UtilHelpers;

class PermissionRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(Permission::class);
    }

    public function getModel(): Permission
    {
        return parent::getModel();
    }

    public function findByParams(string $sort, string  $order, ?int $count = null)
    {
        return $this->findAllWithParams($sort, $order, $count);
    }

    public function savePermission(Permission $permission, array $data) {
        $this->setModel($permission);
        return $this->save($data);
    }


    public function buildPermissionData(string $name, string $label) {
        return [
            'name' => $name,
            'label' => $label
        ];
    }

    public function createPermission(string $name, string $label) {
        return $this->insert($this->buildPermissionData($name, $label));
    }

    public function findPermissionsByParams(array $permissions) {
        foreach ($permissions as $column => $value) {
            $this->addWhere($column, $value);
        }
        return $this->findMany();
    }
}
