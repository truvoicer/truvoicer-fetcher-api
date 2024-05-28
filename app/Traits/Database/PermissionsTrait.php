<?php

namespace App\Traits\Database;

use App\Helpers\Tools\ClassHelpers;
use App\Models\User;
use App\Repositories\PermissionRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

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


public function getModelByUserQuery(Model $model, User $user, ?bool $checkPermissions = true)
    {
        $modelTableName = ClassHelpers::getClassConstantValue($model::class, 'TABLE_NAME');
        if (!$modelTableName) {
            return false;
        }

        if (!method_exists($model, 'permissions')) {
            throw new BadRequestHttpException(
                sprintf('permissions relation not found in class: %s', $model::class)
            );
        }

        $modelRelClass = $model->permissions()->getParent();
        $modelClassName = str_replace('App\\Models\\', '', $modelRelClass::class);
        $modelClassName = lcfirst($modelClassName);

        $modelUserRelationName = "{$modelClassName}";
        if (!method_exists($model, $modelUserRelationName)) {
            throw new BadRequestHttpException(
                sprintf('%s relation not found in class: %s', $modelUserRelationName, $model::class)
            );
        }
        $modelUserPermissionRelationName = "{$modelClassName}Permission";
        if (!method_exists($modelRelClass, $modelUserPermissionRelationName)) {
            throw new BadRequestHttpException(
                sprintf('%s relation not found in class: %s', $modelUserPermissionRelationName, $modelRelClass::class)
            );
        }

        $query = $user->{$modelTableName}();
        $query->whereHas($modelUserRelationName, function ($query) use ($checkPermissions, $modelUserPermissionRelationName) {
            if ($checkPermissions) {
                $query->whereHas($modelUserPermissionRelationName, function ($query) {
                    $query->whereHas('permission', function ($query) {
                        $query->whereIn('name', $this->permissions);
                    });
                });
            }
        });
        $this->resetPermissions();
        return $query;
    }


    public function findUserModelQuery(Model $model, User $user, array $params, ?bool $checkPermissions = true)
    {
        $query = $this->getModelByUserQuery($model, $user, $checkPermissions);
        $query = $this->applyConditionsToQuery($params, $query);
        return $query;
    }
    public function findUserModelBy(Model $model, User $user, array $params, ?bool $checkPermissions = true)
    {
        return $this->findUserModelQuery($model, $user, $params, $checkPermissions)->first();
    }

    public function findUserModelsBy(Model $model, User $user, array $params, ?bool $checkPermissions = true)
    {
        return $this->findUserModelQuery($model, $user, $params, $checkPermissions)->get();
    }


    public function createUserModelRelation(User $user, Model $model)
    {
        $modelTableName = ClassHelpers::getClassConstantValue($model::class, 'TABLE_NAME');
        if (!$modelTableName) {
            return false;
        }
        if (!method_exists($user, $modelTableName)) {
            return false;
        }
        $syncCat = $user->{$modelTableName}()->toggle([$model->id]);

        if (!$this->dbHelpers->validateToggle($syncCat, [$model->id])) {
            return false;
        }
        return true;
    }

    public function createUserModelPermission(User $user, Model $model, Collection $permissions)
    {
        $modelTableName = ClassHelpers::getClassConstantValue($model::class, 'TABLE_NAME');
        if (!$modelTableName) {
            return false;
        }
        $userRelatedRepositoryClass = ClassHelpers::getClassConstantValue($model::class, 'RELATED_USER_REPOSITORY');
        if (!$userRelatedRepositoryClass) {
            return false;
        }
        if (!method_exists($user, $modelTableName)) {
            return false;
        }

        $foreignPivotKeyName = $user->{$modelTableName}()->getForeignPivotKeyName();
        $relatedPivotKeyName = $user->{$modelTableName}()->getRelatedPivotKeyName();

        $userRelatedRepositoryInstance = new $userRelatedRepositoryClass();

        $modelUserRel = $userRelatedRepositoryInstance->findOneBy([
            [$relatedPivotKeyName, '=', $model->id],
            [$foreignPivotKeyName, '=', $user->id],
        ]);

        if (!$modelUserRel) {
            return false;
        }

        if (!method_exists($modelUserRel, 'permissions')) {
            return false;
        }

        foreach ($permissions as $permission) {
            $savePermission = $modelUserRel->permissions()->toggle([$permission->id]);
            if (!$this->dbHelpers->validateToggle($savePermission, [$permission->id])) {
                return false;
            }
        }
        return true;
    }

    public function saveUserPermissions(User $user, Model $model, array $permissions = []) {
        $permissionRepository = new PermissionRepository();
        $permissions = $permissionRepository->findPermissionsByParams($permissions);
        if ($permissions->isEmpty()) {
            return false;
        }

        if (!$this->createUserModelRelation($user, $model)) {
            return false;
        }
        if (!$this->createUserModelPermission($user, $model, $permissions)) {
            return false;
        }
        return true;
    }

}
