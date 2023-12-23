<?php

namespace App\Http\Controllers\Api\Backend;

use App\Http\Controllers\Controller;
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
    private PermissionService $permissionService;

    /**
     * PermissionController constructor.
     * Initialise services for this class
     *
     * @param AccessControlService $accessControlService
     * @param SerializerService $serializerService
     * @param PermissionService $permissionService
     * @param HttpRequestService $httpRequestService
     */
    public function __construct(
        AccessControlService $accessControlService,
        SerializerService $serializerService,
        PermissionService $permissionService,
        HttpRequestService $httpRequestService,
        Request $request
    ) {
        parent::__construct($accessControlService, $httpRequestService, $serializerService, $request);
        $this->permissionService = $permissionService;
    }

    public function getProviderList(Request $request, ProviderService $providerService)
    {
        return $this->sendSuccessResponse("success",
            $this->serializerService->entityArrayToArray(
                $providerService->getProviderList(
                    $request->get('sort', "name"),
                    $request->get('order', "asc"),
                    (int)$request->get('count', null)
                ),
                ["list"])
        );
    }

    public function getCategoryList(Request $request, CategoryService $categoryService)
    {
        return $this->sendSuccessResponse(
            "success",
            $this->serializerService->entityArrayToArray(
                $categoryService->findByParams(
                    $request->get('sort', "name"),
                    $request->get('order', "asc"),
                    (int)$request->get('count', null)
                ),
                ["list"]
            )
        );
    }

    public function getPermissions(Request $request)
    {
        $getPermissions = $this->permissionService->findByParams(
            $request->get('sort', "name"),
            $request->get('order', "asc"),
            (int)$request->get('count', null)
        );
        return $this->sendSuccessResponse("success",
            $this->serializerService->entityArrayToArray($getPermissions));
    }

    public function getSinglePermission(Permission $permission)
    {
        return $this->sendSuccessResponse("success",
            $this->serializerService->entityToArray($permission));
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
            $this->serializerService->entityArrayToArray(
                $this->accessControlService->getPermissionEntities()->getUserEntityPermissionList($entity, $user),
                ["list"]
            )
        );
    }

    public function getUserEntityPermission(string $entity, int $id, User $user)
    {
        return $this->sendSuccessResponse(
            "Successfully fetched permissions",
            $this->serializerService->entityToArray(
                $this->accessControlService->getPermissionEntities()->getUserEntityPermission($entity, $id, $user),
                ["list"]
            )
        );
    }

    /**
     * Gets a user mappings
     *
     * @param User $user
     */
    public function saveUserEntityPermissions(User $user, Request $request)
    {
        $requestData = $this->httpRequestService->getRequestData($request, true);
        return $this->sendSuccessResponse(
            "Entity permissions saved successfully",
            $this->serializerService->entityToArray(
                $this->accessControlService->getPermissionEntities()->saveUserEntityPermissions(
                    $requestData["entity"], $user, $requestData["id"], $requestData["permissions"]
                ),
                ["list"]
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
        return $this->sendSuccessResponse("success", []);
    }

    /**
     * Creates a new permission based on the request post data
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createPermission(Request $request)
    {
        $requestData = $this->httpRequestService->getRequestData($request, true);
        $create = $this->permissionService->createPermission($requestData["name"]);
        if (!$create) {
            return $this->sendErrorResponse("Error creating permission.");
        }
        return $this->sendSuccessResponse("Successfully created permission.",
            $this->serializerService->entityToArray($create));
    }

    /**
     * Updates a new permission based on request post data
     *
     * @param Request $request
     */
    public function updatePermission(Permission $permission, Request $request)
    {
        $requestData = $this->httpRequestService->getRequestData($request, true);
        $update = $this->permissionService->updatePermission($permission, $requestData["name"]);
        if (!$update) {
            return $this->sendErrorResponse("Error updating permission.");
        }
        return $this->sendSuccessResponse("Successfully updated permission.",
            $this->serializerService->entityToArray($update));
    }

    /**
     * Deletes a permission based on the request post data
     *
     * @param Request $request
     */
    public function deletePermission(Request $request)
    {
        $requestData = $this->httpRequestService->getRequestData($request, true);
        $delete = $this->permissionService->deletePermissionById($requestData['item_id']);
        if (!$delete) {
            return $this->sendErrorResponse("Error deleting permission", $this->serializerService->entityToArray($delete, ['main']));
        }
        return $this->sendSuccessResponse("Permission deleted.", $this->serializerService->entityToArray($delete, ['main']));
    }
}
