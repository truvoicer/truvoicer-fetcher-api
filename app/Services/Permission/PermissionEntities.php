<?php
namespace App\Services\Permission;

use App\Models\User;
use App\Services\Category\CategoryService;
use App\Services\Provider\ProviderService;
use App\Services\ServiceFactory;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class PermissionEntities
{
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
            self::SERVICE_ID_KEY => ProviderService::SERVICE_ALIAS
        ],
        [
            self::ENTITY_KEY => "category",
            self::ENTITY_LABEL_KEY => "Categories",
            self::ENTITY_LABEL_DATA_KEY => "label",
            self::ENTITY_SORT_KEY => "name",
            self::SERVICE_ID_KEY => CategoryService::SERVICE_ALIAS
        ]
    ];

    private ServiceFactory $serviceFactory;
    private array $entityConfig = [];
    private object $serviceObject;

    public function __construct(ServiceFactory $serviceFactory) {
        $this->serviceFactory = $serviceFactory;
    }

    public function findEntityConfigByEntityName(string $entityName) {
        $entityConfigKey = array_search($entityName, array_column(self::PROTECTED_ENTITIES, "entity"));
        if ($entityConfigKey === false) {
            throw new BadRequestHttpException(
                sprintf("Error, Entity config not found for [%s]", $entityName)
            );
        }
        $this->entityConfig = self::PROTECTED_ENTITIES[$entityConfigKey];
        return $this->entityConfig;
    }

    public function getServiceObjectByEntityName(string $entityName): object
    {
        $entityConfig = $this->findEntityConfigByEntityName($entityName);
        $this->serviceObject = $this->serviceFactory->getService($entityConfig[self::SERVICE_ID_KEY]);
        return $this->serviceObject;
    }

    public function validateServiceObjectFunction(object $serviceObject, string $functionName) {
        if (!method_exists($serviceObject, $functionName)) {
            throw new BadRequestHttpException(
                sprintf("Function [%s] does not exist in [%s]", $functionName, get_class($serviceObject))
            );
        }
    }

    public function getUserEntityPermissionList(string $entity, User $user) {
        $serviceObject = $this->getServiceObjectByEntityName($entity);
        $functionName = sprintf("getUser%sPermissionsListByUser", ucfirst($this->entityConfig[self::ENTITY_KEY]));
        $this->validateServiceObjectFunction($serviceObject, $functionName);

        return $serviceObject->$functionName($this->entityConfig[self::ENTITY_SORT_KEY], "asc", null, $user);
    }

    public function getUserEntityPermission(string $entity, int $id, User $user)
    {
        $serviceObject = $this->getServiceObjectByEntityName($entity);
        $functionName = sprintf("getUser%sByUser", ucfirst($this->entityConfig[self::ENTITY_KEY]));
        $this->validateServiceObjectFunction($serviceObject, $functionName);
        return $serviceObject->$functionName($user, $id);
    }

    public function saveUserEntityPermissions(string $entity, User $user, int $id, array $permissions)
    {
        $serviceObject = $this->getServiceObjectByEntityName($entity);
        $saveFunctionName = sprintf("saveUser%sPermissions", ucfirst($this->entityConfig[self::ENTITY_KEY]));
        $getObjectFunctionName = sprintf("getUser%sByUser", ucfirst($this->entityConfig[self::ENTITY_KEY]));
        $this->validateServiceObjectFunction($serviceObject, $saveFunctionName);
        $this->validateServiceObjectFunction($serviceObject, $getObjectFunctionName);
        $serviceObject->$saveFunctionName($user, $id, $permissions);
        return $serviceObject->$getObjectFunctionName($user, $id);
    }

    public function deleteUserEntityPermissions(string $entity, int $id, User $user)
    {
        $serviceObject = $this->getServiceObjectByEntityName($entity);
        $deleteFunctionName = sprintf("deleteUser%sPermissions", ucfirst($this->entityConfig[self::ENTITY_KEY]));
        $getObjectFunctionName = sprintf("get%sById", ucfirst($this->entityConfig[self::ENTITY_KEY]));
        $this->validateServiceObjectFunction($serviceObject, $deleteFunctionName);
        $this->validateServiceObjectFunction($serviceObject, $getObjectFunctionName);
        return $serviceObject->$deleteFunctionName(
            $user,
            $serviceObject->$getObjectFunctionName($id)
        );
    }
}
