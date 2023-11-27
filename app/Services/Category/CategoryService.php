<?php

namespace App\Services\Category;

use App\Models\Category;
use App\Models\Permission;
use App\Models\Provider;
use App\Models\User;
use App\Models\UserCategory;
use App\Repositories\CategoryRepository;
use App\Repositories\PermissionRepository;
use App\Repositories\ProviderRepository;
use App\Repositories\UserCategoryRepository;
use App\Services\BaseService;
use App\Services\Permission\AccessControlService;
use App\Services\Permission\PermissionService;
use App\Services\Provider\ProviderEntityService;
use App\Services\Tools\HttpRequestService;
use App\Services\Tools\UtilsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class CategoryService extends BaseService
{
    const SERVICE_ALIAS = "app.service.category.category_entity_service";

    protected EntityManagerInterface $entityManager;
    protected HttpRequestService $httpRequestService;
    protected PermissionRepository $permissionRepository;
    protected ProviderRepository $providerRepository;
    protected CategoryRepository $categoryRepository;
    protected UserCategoryRepository $userCategoryRepository;
    protected AccessControlService $accessControlService;

    public function __construct(EntityManagerInterface $entityManager, HttpRequestService $httpRequestService,
                                TokenStorageInterface $tokenStorage, AccessControlService $accessControlService)
    {
        parent::__construct($tokenStorage);
        $this->entityManager = $entityManager;
        $this->httpRequestService = $httpRequestService;
        $this->userCategoryRepository = $this->entityManager->getRepository(UserCategory::class);
        $this->permissionRepository = $this->entityManager->getRepository(Permission::class);
        $this->categoryRepository = $this->entityManager->getRepository(Category::class);
        $this->providerRepository = $this->entityManager->getRepository(Provider::class);
        $this->accessControlService = $accessControlService;
    }

    public function getAllCategoriesArray()
    {
        return $this->categoryRepository->getAllCategoriesArray();
    }

    public function findByQuery(string $query)
    {
        return $this->categoryRepository->findByQuery($query);
    }

    public function findByParams(string $sort, string $order, int $count)
    {
        return $this->categoryRepository->findByParams($sort, $order, $count);
    }

    public function getCategoryList(string $sort, string $order, ?int $count)
    {
        return $this->categoryRepository->findByParams(
            $sort,
            $order,
            $count
        );
    }

    public function findUserCategories(string $sort, string $order, ?int $count, $user = null)
    {
        $getCategories =  $this->userCategoryRepository->findCategoriesByUser(
            ($user === null) ? $this->user : $user,
            $sort,
            $order,
            $count
        );
        return array_map(function ($userCategory) {
            return $userCategory->getCategory();
        }, $getCategories);
    }

    public function findUserPermittedCategories(string $sort, string $order, ?int $count, $user = null) {
        $getCategories = $this->findUserCategories(
            $sort,
            $order,
            $count
        );
        if (
            $this->accessControlService->getAuthorizationChecker()->isGranted('ROLE_SUPER_ADMIN') ||
            $this->accessControlService->getAuthorizationChecker()->isGranted('ROLE_ADMIN')
        ) {
            return $this->getCategoryList($sort, $order, $count);
        }
        return array_filter($getCategories, function ($category) use($user) {
            return $this->accessControlService->checkPermissionsForEntity(
                CategoryEntityService::ENTITY_NAME, $category, $user,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_READ,
                ],
                false
            );
        }, ARRAY_FILTER_USE_BOTH);
    }


    public function getCategorySelectedProvidersList(string $selectedProviders = null, $user)
    {
        $selectedProvidersArray = explode(",", $selectedProviders);
        $providerArray = [];
        $i = 0;
        foreach ($selectedProvidersArray as $providerId) {
            $provider = $this->providerRepository->findOneBy(["id" => (int)$providerId]);
            $checkPermission = $this->accessControlService->checkPermissionsForEntity(
                ProviderEntityService::ENTITY_NAME, $provider, $user,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_READ,
                ],
                false
            );
            if (!$checkPermission) {
                continue;
            }
            $providerArray[$i]['id'] = $provider->getId();
            $providerArray[$i]['provider_name'] = $provider->getProviderName();
            $providerArray[$i]['provider_label'] = $provider->getProviderLabel();
            $i++;
        };
        return $providerArray;
    }

    public function getCategoryProviderList(Category $category, $user)
    {
        $providerArray = [];
        $i = 0;
        foreach ($category->getProviders() as $provider) {
            $checkPermission = $this->accessControlService->checkPermissionsForEntity(
                ProviderEntityService::ENTITY_NAME, $provider, $user,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_READ,
                ],
                false
            );
            if (!$checkPermission) {
                continue;
            }
            $providerArray[$i]['id'] = $provider->getId();
            $providerArray[$i]['provider_name'] = $provider->getProviderName();
            $providerArray[$i]['provider_label'] = $provider->getProviderLabel();
            $i++;
        };
        return $providerArray;
    }

    public function getCategoryById(int $categoryId)
    {
        $category = $this->categoryRepository->findOneBy(["id" => $categoryId]);
        if ($category === null) {
            throw new BadRequestHttpException(sprintf("Category id:%s not found in database.",
                $categoryId
            ));
        }
        return $category;
    }

    private function getCategoryObject(Category $category, array $data)
    {
        $category->setCategoryName($data['category_name']);
        $category->setCategoryLabel($data['category_label']);
        return $category;
    }

    public function createCategory(array $data)
    {
        if (empty($data['category_label'])) {
            throw new BadRequestHttpException("Category label is not set.");
        }
        $data['category_name'] = UtilsService::labelToName($data['category_label'], false, '-');
        $checkCategory = $this->categoryRepository->findOneBy(["category_name" => $data['category_name']]);
        if ($checkCategory !== null) {
            throw new BadRequestHttpException(sprintf("Category (%s) already exists.", $data['category_name']));
        }
        $category = $this->getCategoryObject(new Category(), $data);
        if ($this->httpRequestService->validateData(
            $category
        )) {
            $getAdminPermission = $this->permissionRepository->findOneBy(["name" => PermissionService::PERMISSION_ADMIN]);
            if ($getAdminPermission === null) {
                throw new BadRequestHttpException(
                    "Admin permission does not exist."
                );
            }
            $createCategory = $this->categoryRepository->saveCategory($category);
            $this->userCategoryRepository->createUserCategory($this->user, $createCategory, [$getAdminPermission]);
            return $createCategory;
        }
        return false;
    }

    public function updateCategory(Category $category, array $data)
    {
        if ($this->httpRequestService->validateData(
            $this->getCategoryObject($category, $data)
        )) {
            return $this->categoryRepository->saveCategory($category);
        }
        return false;
    }


    public function deleteCategoryById(int $categoryId)
    {
        $category = $this->categoryRepository->findOneBy(["id" => $categoryId]);
        if ($category === null) {
            throw new BadRequestHttpException(sprintf("Category id: %s not found in database.", $categoryId));
        }
        return $this->categoryRepository->deleteCategory($category);
    }

    public function deleteCategory(Category $category)
    {
        if ($category === null) {
            return false;
        }
        return $this->categoryRepository->deleteCategory($category);
    }


}
