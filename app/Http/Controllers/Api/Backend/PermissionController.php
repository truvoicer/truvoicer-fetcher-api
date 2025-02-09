<?php

namespace App\Http\Controllers\Api\Backend;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryCollection;
use App\Http\Resources\PermissionCollection;
use App\Http\Resources\PermissionResource;
use App\Http\Resources\ProviderCollection;
use App\Models\Permission;
use App\Models\User;
use App\Services\Category\CategoryService;
use App\Services\Permission\AccessControlService;
use App\Services\Permission\PermissionEntities;
use App\Services\Permission\PermissionService;
use App\Services\Provider\ProviderService;
use App\Services\Tools\HttpRequestService;
use App\Services\Tools\SerializerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Contains api endpoint functions for permission related tasks
 *
 */
class PermissionController extends Controller
{

    public function __construct(
        private PermissionService    $permissionService,
    )
    {
        parent::__construct();
    }

    public function getPermissions(Request $request)
    {
        $getPermissions = $this->permissionService->findByParams(
            $request->get('sort', "name"),
            $request->get('order', "asc"),
            $request->get('count', -1)
        );
        return $this->sendSuccessResponse("success",
            new PermissionCollection($getPermissions)
        );
    }

    public function getSinglePermission(Permission $permission)
    {
        return $this->sendSuccessResponse("success",
            new PermissionResource($permission)
        );
    }

    public function getProtectedEntitiesList()
    {
        return $this->sendSuccessResponse(
            "success",
            PermissionEntities::PROTECTED_ENTITIES
        );
    }

    public function getUserEntityPermissionList(string $entity, User $user)
    {
        return $this->sendSuccessResponse(
            "Successfully fetched permission list",
            $this->accessControlService->getPermissionEntities()->getUserEntityPermissionList($entity, $user)
        );
    }

    public function getUserEntityPermission(string $entity, int $id, User $user)
    {
        return $this->sendSuccessResponse(
            "Successfully fetched permissions",
            $this->accessControlService->getPermissionEntities()->getUserEntityPermission($entity, $id, $user),
        );
    }

    /**
     * Gets a user mappings
     *
     * @param User $user
     */
    public function saveUserEntityPermissions(User $user, Request $request)
    {
        return $this->sendSuccessResponse(
            "Entity permissions saved successfully",
            $this->accessControlService->getPermissionEntities()->saveUserEntityPermissionsByEntityId(
                $request->get("entity"), $user, $request->get("id"), $request->get("permissions")
            )
        );
    }

    /**
     * Gets a user mappings
     *
     * @param string $entity
     * @param User $user
     * @param int $id
     */
    public function deleteUserEntityPermissions(string $entity, User $user, int $id)
    {
        $this->accessControlService->getPermissionEntities()->deleteUserEntityPermissions(
            $entity, $id, $user
        );
        return $this->sendSuccessResponse("success");
    }

    /**
     * Creates a new permission based on the request post data
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createPermission(Request $request)
    {
        $create = $this->permissionService->createPermission($request->get('name'), $request->get('label'));
        if (!$create) {
            return $this->sendErrorResponse("Error creating permission.");
        }
        return $this->sendSuccessResponse(
            "Successfully created permission.",
            new PermissionCollection(
                $this->permissionService->getPermissionRepository()->getModel()
            )
        );
    }

    /**
     * Updates a new permission based on request post data
     *
     * @param Request $request
     */
    public function updatePermission(Permission $permission, Request $request)
    {
        $update = $this->permissionService->updatePermission($permission, $request->all());
        if (!$update) {
            return $this->sendErrorResponse("Error updating permission.");
        }
        return $this->sendSuccessResponse("Successfully updated permission.",
            new PermissionCollection(
                $this->permissionService->getPermissionRepository()->getModel()
            )
        );
    }

    /**
     * Deletes a permission based on the request post data
     *
     * @param Request $request
     */
    public function deletePermission(Permission $permission, Request $request)
    {
        if (!$this->permissionService->deletePermission($permission)) {
            return $this->sendErrorResponse("Error deleting permission");
        }
        return $this->sendSuccessResponse("Permission deleted.");
    }
}
