<?php

namespace App\Controller\Api\Backend;

use App\Controller\Api\BaseController;
use App\Entity\Permission;
use App\Entity\User;
use App\Service\Category\CategoryService;
use App\Service\Permission\AccessControlService;
use App\Service\Permission\PermissionEntities;
use App\Service\Permission\PermissionService;
use App\Service\Provider\ProviderService;
use App\Service\Tools\HttpRequestService;
use App\Service\Tools\SerializerService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * Contains api endpoint functions for permission related tasks
 *
 * Require ROLE_ADMIN for *every* controller method in this class.
 *
 * @IsGranted("ROLE_SUPER_ADMIN")
 */
class PermissionController extends BaseController
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
        HttpRequestService $httpRequestService)
    {
        parent::__construct($accessControlService, $httpRequestService, $serializerService);
        $this->permissionService = $permissionService;
    }

    /**
     * Gets a list of providers from the database based on the get request query parameters
     *
     * @Route("/api/permission/provider/list", name="api_permission_get_providers", methods={"GET"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getProviderList(Request $request, ProviderService $providerService)
    {
        return $this->jsonResponseSuccess("success",
            $this->serializerService->entityArrayToArray(
                $providerService->getProviderList(
                    $request->get('sort', "provider_name"),
                    $request->get('order', "asc"),
                    (int)$request->get('count', null)
                ),
                ["list"])
        );
    }

    /**
     * Gets a list of providers from the database based on the get request query parameters
     *
     * @Route("/api/permission/category/list", name="api_permission_get_categories", methods={"GET"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getCategoryList(Request $request, CategoryService $categoryService)
    {
        return $this->jsonResponseSuccess(
            "success",
            $this->serializerService->entityArrayToArray(
                $categoryService->findByParams(
                    $request->get('sort', "category_name"),
                    $request->get('order', "asc"),
                    (int)$request->get('count', null)
                ),
                ["list"]
            )
        );
    }

    /**
     * Gets a list of permissions from database based on the request get query parameters
     *
     * @Route("/api/permission/list", name="api_get_permissions", methods={"GET"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getPermissions(Request $request)
    {
        $getPermissions = $this->permissionService->findByParams(
            $request->get('sort', "name"),
            $request->get('order', "asc"),
            (int)$request->get('count', null)
        );
        return $this->jsonResponseSuccess("success",
            $this->serializerService->entityArrayToArray($getPermissions));
    }

    /**
     * Gets a single permission from the database based on the get request query parameters
     *
     * @Route("/api/permission/{id}", name="api_get_single_permission", methods={"GET"})
     * @param Permission $permission
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getSinglePermission(Permission $permission)
    {
        return $this->jsonResponseSuccess("success",
            $this->serializerService->entityToArray($permission));
    }


    /**
     * Gets a user mappings
     *
     * @Route("/api/permission/user/{id}/entity/list", name="api_get_user_entity_list", methods={"GET"})
     * @param User $user
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getProtectedEntitiesList()
    {
        return $this->jsonResponseSuccess(
            "success",
            PermissionEntities::PROTECTED_ENTITIES
        );
    }

    /**
     * Gets a user mappings
     *
     * @Route("/api/permission/user/{user}/entity/{entity}/list", name="api_get_single_entity_permission_list", methods={"GET"})
     * @param string $entity
     * @param User $user
     * @param ProviderService $providerService
     * @param CategoryService $categoryService
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getUserEntityPermissionList(string $entity, User $user)
    {
        return $this->jsonResponseSuccess(
            "Successfully fetched permission list",
            $this->serializerService->entityArrayToArray(
                $this->accessControlService->getPermissionEntities()->getUserEntityPermissionList($entity, $user),
                ["list"]
            )
        );
    }

    /**
     * Gets a user mappings
     *
     * @Route("/api/permission/user/{user}/entity/{entity}/{id}", name="api_get_single_user_entity_permission", methods={"GET"})
     * @param string $entity
     * @param User $user
     * @param ProviderService $providerService
     * @param CategoryService $categoryService
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getUserEntityPermission(string $entity, int $id, User $user)
    {
        return $this->jsonResponseSuccess(
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
     * @Route("/api/permission/user/{user}/entity/save", name="api_save_user_entity_permissions", methods={"POST"})
     * @param User $user
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function saveUserEntityPermissions(User $user, Request $request)
    {
        $requestData = $this->httpRequestService->getRequestData($request, true);
        return $this->jsonResponseSuccess(
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
     * @Route("/api/permission/user/{user}/entity/{entity}/{id}/delete", name="api_delete_entity_permissions", methods={"POST"})
     * @param string $entity
     * @param User $user
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function deleteUserEntityPermissions(string $entity, User $user, int $id)
    {
        $this->accessControlService->getPermissionEntities()->deleteUserEntityPermissions(
            $entity, $id, $user
        );
        return $this->jsonResponseSuccess("success", []);
    }

    /**
     * Creates a new permission based on the request post data
     *
     * @param Request $request
     * @Route("/api/permission/create", name="api_create_permission", methods={"POST"})
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function createPermission(Request $request)
    {
        $requestData = $this->httpRequestService->getRequestData($request, true);
        $create = $this->permissionService->createPermission($requestData["name"]);
        if (!$create) {
            return $this->jsonResponseFail("Error creating permission.");
        }
        return $this->jsonResponseSuccess("Successfully created permission.",
            $this->serializerService->entityToArray($create));
    }

    /**
     * Updates a new permission based on request post data
     *
     * @param Request $request
     * @Route("/api/permission/{permission}/update", name="api_update_permission", methods={"POST"})
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function updatePermission(Permission $permission, Request $request)
    {
        $requestData = $this->httpRequestService->getRequestData($request, true);
        $update = $this->permissionService->updatePermission($permission, $requestData["name"]);
        if (!$update) {
            return $this->jsonResponseFail("Error updating permission.");
        }
        return $this->jsonResponseSuccess("Successfully updated permission.",
            $this->serializerService->entityToArray($update));
    }

    /**
     * Deletes a permission based on the request post data
     *
     * @param Request $request
     * @Route("/api/permission/delete", name="api_delete_permission", methods={"POST"})
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function deletePermission(Request $request)
    {
        $requestData = $this->httpRequestService->getRequestData($request, true);
        $delete = $this->permissionService->deletePermissionById($requestData['item_id']);
        if (!$delete) {
            return $this->jsonResponseFail("Error deleting permission", $this->serializerService->entityToArray($delete, ['main']));
        }
        return $this->jsonResponseSuccess("Permission deleted.", $this->serializerService->entityToArray($delete, ['main']));
    }
}
