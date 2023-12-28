<?php

namespace App\Traits\Database;

trait PermissionsTrait
{
    public array $permissions = [];

    public function setPermissions(array $permissions): void
    {
        $this->permissions = $permissions;
    }

    public function resetPermissions() {
        $this->permissions = [];
    }

}
