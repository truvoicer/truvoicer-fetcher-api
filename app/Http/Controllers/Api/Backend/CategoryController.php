<?php

namespace App\Http\Controllers\Api\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\CreateCategoryRequest;
use App\Http\Requests\Category\DeleteBatchCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\CategoryCollection;
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
        CategoryService $categoryService,
        AccessControlService $accessControlService
    ) {
        parent::__construct($accessControlService);
        $this->categoryService = $categoryService;
    }

    /**
     * Gets a list of categories from database based on the request get query parameters
     *
     * @param Request $request
     */
    public function getCategories(Request $request)
    {
        $pagination = $request->query->filter('pagination', true, FILTER_VALIDATE_BOOLEAN);
        if (
            $request->user()->tokenCan(AuthService::getApiAbility(AuthService::ABILITY_SUPERUSER)) ||
            $request->user()->tokenCan(AuthService::getApiAbility(AuthService::ABILITY_ADMIN))
        ) {
            $categories = $this->categoryService->findByParams(
                $request->get('sort', "name"),
                $request->get('order', "asc"),
                $request->get('count', -1),
                $pagination
            );
        } else {
            $categories = $this->categoryService->findUserCategories(
                $request->user(),
                $pagination
            );
        }
        return $this->sendSuccessResponse("success",
            new CategoryCollection($categories)
        );
    }

    /**
     * Gets a single category from the database based on the get request query parameters
     *
     */
    public function getSingleCategory(Category $category, Request $request)
    {
        $this->setAccessControlUser($request->user());
        if (
            !$this->accessControlService->checkPermissionsForEntity(
                $category,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_READ,
                ],
            )
        ) {
            return $this->sendErrorResponse("Access control: operation not permitted");
        }
        return $this->sendSuccessResponse("success",
            new CategoryResource($category)
        );
    }

    public function createCategory(CreateCategoryRequest $request)
    {
        $create = $this->categoryService->createCategory($request->user(), $request->all());
        if (!$create) {
            return $this->sendErrorResponse("Error creating category.");
        }
        return $this->sendSuccessResponse("Successfully created category.",
            new CategoryResource($this->categoryService->getCategoryRepository()->getModel())
        );
    }

    public function updateCategory(Category $category, UpdateCategoryRequest $request)
    {
        $this->setAccessControlUser($request->user());
        if (
            !$this->accessControlService->checkPermissionsForEntity(
                $category,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_UPDATE,
                ],
            )
        ) {
            return $this->sendErrorResponse("Access control: operation not permitted");
        }

        $create = $this->categoryService->updateCategory($category, $request->all());
        if (!$create) {
            return $this->sendErrorResponse("Error updating category.");
        }
        return $this->sendSuccessResponse("Successfully updated category.",
            $this->serializerService->entityToArray(
                $this->categoryService->getCategoryRepository()->getModel()
            ));
    }

    public function deleteCategory(Category $category, Request $request)
    {
        $this->setAccessControlUser($request->user());
        if (
            !$this->accessControlService->checkPermissionsForEntity(
                $category,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_DELETE,
                ],
            )
        ) {
            return $this->sendErrorResponse("Access control: operation not permitted");
        }
        if (!$this->categoryService->deleteCategory($category)) {
            return $this->sendErrorResponse("Error deleting category");
        }
        return $this->sendSuccessResponse("Category deleted.");
    }
    public function deleteBatch(
        DeleteBatchCategoryRequest $request
    ): \Illuminate\Http\JsonResponse
    {
        $this->setAccessControlUser($request->user());

        if (!$this->categoryService->deleteBatch($request->get('ids'))) {
            return $this->sendErrorResponse(
                "Error deleting categories",
            );
        }
        return $this->sendSuccessResponse(
            "Categories deleted.",
        );
    }
}
