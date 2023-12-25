<?php

namespace App\Http\Controllers\Api\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\CreateCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\Auth\AuthService;
use App\Services\Category\CategoryService;
use App\Services\Permission\AccessControlService;
use App\Services\Permission\PermissionService;
use App\Services\Tools\HttpRequestService;
use App\Services\Tools\SerializerService;
use Illuminate\Http\Request;

/**
 * Contains api endpoint functions for category related tasks
 *
 * Require ROLE_ADMIN for *every* controller method in this class.
 *
 */
class CategoryController extends Controller
{
    const DEFAULT_ENTITY = "category";

    private CategoryService $categoryService;

    /**
     * CategoryController constructor.
     * Initialise services for this class
     *
     * @param SerializerService $serializerService
     * @param CategoryService $categoryService
     * @param HttpRequestService $httpRequestService
     * @param AccessControlService $accessControlService
     */
    public function __construct(
        SerializerService $serializerService,
        CategoryService $categoryService,
        HttpRequestService $httpRequestService,
        AccessControlService $accessControlService
    ) {
        parent::__construct($accessControlService, $httpRequestService, $serializerService);
        $this->categoryService = $categoryService;
        $this->accessControlService->setEntityName(self::DEFAULT_ENTITY);
    }

    /**
     * Gets a list of categories from database based on the request get query parameters
     *
     * @param Request $request
     */
    public function getCategories(Request $request)
    {
        if ($request->user()->tokenCan(AuthService::getApiAbility(AuthService::ABILITY_SUPERUSER)) || $request->user()->tokenCan(AuthService::getApiAbility(AuthService::ABILITY_ADMIN))) {
            $categories = $this->categoryService->getCategoryList(
                $request->get('sort', "name"),
                $request->get('order', "asc"),
                (int)$request->get('count', null)
            );
        } else {
            $categories = $this->categoryService->findUserPermittedCategories(
                $request->get('sort', "name"),
                $request->get('order', "asc"),
                (int)$request->get('count', null),
                $request->user()
            );
        }
        return $this->sendSuccessResponse("success",
            CategoryResource::collection($categories)
        );
    }

    /**
     * Gets a single category from the database based on the get request query parameters
     *
     */
    public function getSingleCategory(Category $category, Request $request)
    {
        $this->setAccessControlUser($request->user());
        if (!$request->user()->tokenCan(AuthService::getApiAbility(AuthService::ABILITY_SUPERUSER)) && !$request->user()->tokenCan(AuthService::getApiAbility(AuthService::ABILITY_ADMIN))) {
            $this->accessControlService->checkPermissionsForEntity(
                $category,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_READ,
                ],
            );
        }
        return $this->sendSuccessResponse("success",
            $this->serializerService->entityToArray($category));
    }

    public function createCategory(CreateCategoryRequest $request)
    {
        $create = $this->categoryService->createCategory($request->all());
        if (!$create) {
            return $this->sendErrorResponse("Error creating category.");
        }
        return $this->sendSuccessResponse("Successfully created category.",
            $this->serializerService->entityToArray(
                $this->categoryService->getCategoryRepository()->getModel()
            )
        );
    }

    public function updateCategory(Category $category, UpdateCategoryRequest $request)
    {
        $this->setAccessControlUser($request->user());
        dd(
            $this->accessControlService->checkPermissionsForEntity(
                $category,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_UPDATE,
                ],
            )
        );
        if (
            $this->accessControlService->checkPermissionsForEntity(
                $category,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_UPDATE,
                ],
            )
        ) {

        }
        dd(
            $request->user()->tokenCan(
                AuthService::getApiAbility(AuthService::ABILITY_SUPERUSER)
            ),
            $request->user()->tokenCan(AuthService::getApiAbility(AuthService::ABILITY_ADMIN))
        );
        $requestData = $this->httpRequestService->getRequestData($request, true);
        $create = $this->categoryService->updateCategory($category, $requestData);
        if (!$create) {
            return $this->sendErrorResponse("Error updating category.");
        }
        return $this->sendSuccessResponse("Successfully updated category.",
            $this->serializerService->entityToArray($create));
    }

    public function deleteCategory(Category $category, Request $request)
    {
        $this->setAccessControlUser($request->user());
        if (!$request->user()->tokenCan(AuthService::getApiAbility(AuthService::ABILITY_SUPERUSER)) && !$request->user()->tokenCan(AuthService::getApiAbility(AuthService::ABILITY_ADMIN))) {
            $this->accessControlService->checkPermissionsForEntity(
                $category,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_DELETE,
                ],
            );
        }
        $delete = $this->categoryService->deleteCategory($category);
        if (!$delete) {
            return $this->sendErrorResponse("Error deleting category", $this->serializerService->entityToArray($delete, ['main']));
        }
        return $this->sendSuccessResponse("Category deleted.", $this->serializerService->entityToArray($delete, ['main']));
    }
}
