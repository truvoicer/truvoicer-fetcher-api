<?php

namespace App\Http\Controllers\Api\Backend;

use App\Http\Controllers\Controller;
use App\Entity\Category;
use App\Services\Permission\AccessControlService;
use App\Services\Permission\PermissionService;
use App\Services\Tools\HttpRequestService;
use App\Services\Category\CategoryService;
use App\Services\Tools\SerializerService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * Contains api endpoint functions for category related tasks
 *
 * Require ROLE_ADMIN for *every* controller method in this class.
 *
 * @IsGranted("ROLE_USER")
 * @Route("/api/category")
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
        AccessControlService $accessControlService)
    {

        parent::__construct($accessControlService, $httpRequestService, $serializerService);
        $this->categoryService = $categoryService;
    }

    /**
     * Gets a list of categories from database based on the request get query parameters
     *
     * @Route("/list", name="api_get_categories", methods={"GET"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getCategories(Request $request)
    {
        if ($this->isGranted('ROLE_SUPER_ADMIN') || $this->isGranted('ROLE_ADMIN')) {
            $categories = $this->categoryService->getCategoryList(
                $request->get('sort', "category_name"),
                $request->get('order', "asc"),
                (int)$request->get('count', null)
            );
        } else {
            $categories = $this->categoryService->findUserPermittedCategories(
                $request->get('sort', "category_name"),
                $request->get('order', "asc"),
                (int)$request->get('count', null),
                $request->user()
            );
        }
        return $this->sendSuccessResponse("success",
            $this->serializerService->entityArrayToArray($categories)
        );
    }

    /**
     * Gets a single category from the database based on the get request query parameters
     *
     * @Route("/{category}", name="api_get_single_category", methods={"GET"})
     * @param Category $category
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getSingleCategory(Category $category)
    {
        if (!$this->isGranted('ROLE_SUPER_ADMIN') && !$this->isGranted('ROLE_ADMIN')) {
            $this->accessControlService->checkPermissionsForEntity(
                self::DEFAULT_ENTITY, $category, $request->user(),
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_READ,
                ],
            );
        }
        return $this->sendSuccessResponse("success",
            $this->serializerService->entityToArray($category));
    }

    /**
     * Creates a new category based on the request post data
     *
     * @param Request $request
     * @Route("/create", name="api_create_category", methods={"POST"})
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function createCategory(Request $request)
    {
        $requestData = $this->httpRequestService->getRequestData($request, true);
        $create = $this->categoryService->createCategory($requestData);
        if (!$create) {
            return $this->sendErrorResponse("Error creating category.");
        }
        return $this->sendSuccessResponse("Successfully created category.",
            $this->serializerService->entityToArray($create));
    }

    /**
     * Updates a new category based on request post data
     *
     * @param Request $request
     * @Route("/{category}/update", name="api_update_category", methods={"POST"})
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function updateCategory(Category $category, Request $request)
    {
        if (!$this->isGranted('ROLE_SUPER_ADMIN') && !$this->isGranted('ROLE_ADMIN')) {
            $this->accessControlService->checkPermissionsForEntity(
                self::DEFAULT_ENTITY, $category, $request->user(),
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_UPDATE,
                ],
            );
        }
        $requestData = $this->httpRequestService->getRequestData($request, true);
        $create = $this->categoryService->updateCategory($category, $requestData);
        if (!$create) {
            return $this->sendErrorResponse("Error updating category.");
        }
        return $this->sendSuccessResponse("Successfully updated category.",
            $this->serializerService->entityToArray($create));
    }

    /**
     * Deletes a category based on the request post data
     *
     * @param Request $request
     * @Route("/{category}/delete", name="api_delete_category", methods={"POST"})
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function deleteCategory(Category $category, Request $request)
    {
        if (!$this->isGranted('ROLE_SUPER_ADMIN') && !$this->isGranted('ROLE_ADMIN')) {
            $this->accessControlService->checkPermissionsForEntity(
                self::DEFAULT_ENTITY, $category, $request->user(),
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
