<?php
namespace App\Services\Permission;

use App\Helpers\Tools\ClassHelpers;
use App\Models\User;
use App\Repositories\BaseRepository;
use App\Services\Category\CategoryService;
use App\Services\Provider\ProviderService;
use App\Services\ServiceFactory;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class PermissionEntities
{
    const FUNC_USER_HAS_ENTITY_PERMISSIONS = 'userHasEntityPermissions';
    const FUNC_GET_USER_PERMISSIONS = 'getUserPermissions';
    const FUNC_GET_USER_ENTITY_PERMISSIONS = 'getPermissionsListByUser';
    const FUNC_SAVE_USER_PERMISSIONS = 'saveUserPermissions';
    const FUNC_DELETE_USER_PERMISSIONS = 'deleteUserPermissions';
    const ENTITY_KEY = "entity";
    const ENTITY_LABEL_KEY = "entity_label";
    const ENTITY_LABEL_DATA_KEY = "entity_label_data_key";
    const ENTITY_SORT_KEY = "entity_label_sort_key";
    const SERVICE_ID_KEY = "service_id";

    const PROTECTED_ENTITIES = [
        [
            self::ENTITY_KEY => "provider",
            self::ENTITY_LABEL_KEY => "Providers",
            self::ENTITY_LABEL_DATA_KEY => "label",
            self::ENTITY_SORT_KEY => "name",
//            self::SERVICE_ID_KEY => ProviderService::SERVICE_ALIAS
        ],
        [
            self::ENTITY_KEY => "category",
            self::ENTITY_LABEL_KEY => "Categories",
            self::ENTITY_LABEL_DATA_KEY => "label",
            self::ENTITY_SORT_KEY => "name",
//            self::SERVICE_ID_KEY => CategoryService::SERVICE_ALIAS
        ]
    ];

    private array $entityConfig = [];

    public function getEntityInstance(string $entity)
    {
        $className = "App\Models\{$entity}";
        if (!class_exists($className)) {
            return false;
        }
        return new $className();
    }
    public function getModelRepositoryInstance(Model $model): BaseRepository|false
    {
        if (!ClassHelpers::classHasConstant($model::class, 'REPOSITORY')) {
            return false;
        }
        $repoClass = $model::REPOSITORY;
        return new $repoClass();
    }

    public function findEntityById(Model $model, int $id): Model|false|null
    {
        $repositoryInstance = $this->getModelRepositoryInstance($model);
        if (!$repositoryInstance) {
            return false;
        }
        $findEntityItem = $repositoryInstance->findById($id);
        if (!$findEntityItem) {
            return false;
        }
        return $repositoryInstance->getModel();
    }

    public function validateServiceObjectFunction(object $serviceObject, string $functionName) {
        if (!method_exists($serviceObject, $functionName)) {
            return false;
        }
        return true;
    }

    public function userHasEntityPermissions(
        User $user,
        Model $entityObject,
        array $permissions
    ) {
        $serviceObject = $this->getModelRepositoryInstance($entityObject);
        if (!$serviceObject) {
            throw new BadRequestHttpException(
                sprintf("Entity repository not found for class: %s", $entityObject::class)
            );
        }

        $functionName = self::FUNC_USER_HAS_ENTITY_PERMISSIONS;
        if (!$this->validateServiceObjectFunction($serviceObject, $functionName)) {
            throw new BadRequestHttpException(
                sprintf("Function [%s] does not exist in [%s]", $functionName, get_class($serviceObject))
            );
        }

        return $serviceObject->$functionName($user, $entityObject, $permissions);
    }

    public function getEntityUserPermissions(
        User $user,
        Model $entityObject
    ) {
        $serviceObject = $this->getModelRepositoryInstance($entityObject);
        if (!$serviceObject) {
            throw new BadRequestHttpException(
                sprintf("Entity repository not found for class: %s", $entityObject::class)
            );
        }

        $functionName = self::FUNC_GET_USER_PERMISSIONS;
        if (!$this->validateServiceObjectFunction($serviceObject, $functionName)) {
            throw new BadRequestHttpException(
                sprintf("Function [%s] does not exist in [%s]", $functionName, get_class($serviceObject))
            );
        }

        return $serviceObject->$functionName($user, $entityObject);
    }

    public function getUserEntityPermissionList(string $entity, User $user) {
        $entityInstance = $this->getEntityInstance($entity);
        if (!$entityInstance) {
            throw new BadRequestHttpException(
                sprintf(
                    'Entity model [%s] does not exist',
                    $entity,
                )
            );
        }
        $repositoryInstance = $this->getModelRepositoryInstance($entityInstance);
        $functionName = self::FUNC_GET_USER_ENTITY_PERMISSIONS;
        if (!$this->validateServiceObjectFunction($repositoryInstance, $functionName)) {
            throw new BadRequestHttpException(
                sprintf(
                    "Function [%s] does not exist in [%s]",
                    $functionName,
                    get_class($repositoryInstance)
                )
            );
        }

        return $repositoryInstance->$functionName($user, 'name', "asc", null);
    }

    public function getUserEntityPermission(string $entity, int $id, User $user)
    {
        $entityInstance = $this->getEntityInstance($entity);
        if (!$entityInstance) {
            throw new BadRequestHttpException(
                sprintf(
                    'Entity model [%s] does not exist',
                    $entity,
                )
            );
        }

        $entityItem = $this->findEntityById($entityInstance, $id);
        if (!$entityItem) {
            return null;
        }
        return $this->getEntityUserPermissions($user, $entityItem);
    }

    public function saveUserEntityPermissionsByEntityId(string $entity, User $user, int $id, array $permissions)
    {
        $entityInstance = $this->getEntityInstance($entity);
        if (!$entityInstance) {
            throw new BadRequestHttpException(
                sprintf(
                    'Entity model [%s] does not exist',
                    $entity,
                )
            );
        }

        $entityItem = $this->findEntityById($entityInstance, $id);
        if (!$entityItem) {
            return null;
        }

        return $this->saveUserEntityPermissions($user, $entityItem, $permissions);
    }

    public function saveUserEntityPermissions(User $user, Model $entity, array $permissions)
    {
        $repositoryInstance = $this->getModelRepositoryInstance($entity);
        $saveFunctionName = self::FUNC_SAVE_USER_PERMISSIONS;
        if (!$this->validateServiceObjectFunction($repositoryInstance, $saveFunctionName)) {
            throw new BadRequestHttpException(
                sprintf("Function [%s] does not exist in [%s]", $saveFunctionName, get_class($repositoryInstance))
            );
        }


        return $repositoryInstance->$saveFunctionName($user, $entity, $permissions);
    }

    public function deleteUserEntityPermissions(string $entity, int $id, User $user)
    {
        $entityInstance = $this->getEntityInstance($entity);
        if (!$entityInstance) {
            throw new BadRequestHttpException(
                sprintf(
                    'Entity model [%s] does not exist',
                    $entity,
                )
            );
        }

        $repositoryInstance = $this->getModelRepositoryInstance($entityInstance);
        $deleteFunctionName = self::FUNC_DELETE_USER_PERMISSIONS;

        if (!$this->validateServiceObjectFunction($repositoryInstance, $deleteFunctionName)) {
            throw new BadRequestHttpException(
                sprintf("Function [%s] does not exist in [%s]", $deleteFunctionName, get_class($repositoryInstance))
            );
        }

        $entityItem = $this->findEntityById($entityInstance, $id);
        if (!$entityItem) {
            return null;
        }

        return $repositoryInstance->$deleteFunctionName(
            $user,
            $entityItem
        );
    }
}
