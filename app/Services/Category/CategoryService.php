<?php

namespace App\Services\Category;

use App\Models\Category;
use App\Models\User;
use App\Repositories\CategoryRepository;
use App\Repositories\PermissionRepository;
use App\Repositories\ProviderRepository;
use App\Repositories\CategoryUserRepository;
use App\Services\Auth\AuthService;
use App\Services\BaseService;
use App\Services\Permission\AccessControlService;
use App\Services\Permission\PermissionService;
use App\Helpers\Tools\UtilHelpers;
use App\Services\User\UserAdminService;
use Illuminate\Support\Facades\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class CategoryService extends BaseService
{
    protected PermissionRepository $permissionRepository;
    protected ProviderRepository $providerRepository;
    protected CategoryRepository $categoryRepository;
    protected CategoryUserRepository $userCategoryRepository;
    protected AccessControlService $accessControlService;

    public function __construct(AccessControlService $accessControlService)
    {
        $this->userCategoryRepository = new CategoryUserRepository();
        $this->permissionRepository = new PermissionRepository();
        $this->categoryRepository = new CategoryRepository();
        $this->providerRepository = new ProviderRepository();
        $this->accessControlService = $accessControlService;
    }

    public function getAllCategoriesArray()
    {
        return $this->categoryRepository->findAll()->toArray();
    }

    public function findByQuery(string $query)
    {
        $this->categoryRepository->addWhere("label", "LIKE", "%" . $query . "%");
        $this->categoryRepository->addWhere("name", "LIKE", "%" . $query . "%", "OR");
        return $this->categoryRepository->findMany();
    }

    public function findByParams(string $sort, string $order, int $count)
    {
        $this->categoryRepository->setOrderDir($order);
        $this->categoryRepository->setSortField($sort);
        $this->categoryRepository->setLimit($count);
        return $this->categoryRepository->findMany();
    }

    public function getCategoryList(string $sort, string $order, ?int $count)
    {
        return $this->findByParams(
            $sort,
            $order,
            $count
        );
    }

    public function findUserCategories(User $user, string $sort, string $order, ?int $count) {
        $this->userCategoryRepository->setPermissions([
            PermissionService::PERMISSION_ADMIN,
            PermissionService::PERMISSION_READ,
        ]);
        return $this->userCategoryRepository->findCategoriesByUser(
            $user,
            $sort,
            $order,
            $count
        );
    }


    public function getCategorySelectedProvidersList(string $selectedProviders = null, $user)
    {
        $selectedProvidersArray = explode(",", $selectedProviders);
        $providerArray = [];
        $i = 0;
        $this->accessControlService->setUser($user);
        foreach ($selectedProvidersArray as $providerId) {
            $provider = $this->providerRepository->findById((int)$providerId);

            $checkPermission = $this->accessControlService->checkPermissionsForEntity(
                $provider,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_READ,
                ],
                false
            );
            if (!$checkPermission) {
                continue;
            }
            $providerArray[$i]['id'] = $provider->id;
            $providerArray[$i]['name'] = $provider->name;
            $providerArray[$i]['label'] = $provider->label;
            $i++;
        };
        return $providerArray;
    }

    public function getCategoryProviderList(Category $category, $user)
    {
        $providerArray = [];
        $i = 0;
        $this->accessControlService->setUser($user);
        foreach ($category->provider()->get() as $provider) {

            $checkPermission = $this->accessControlService->checkPermissionsForEntity(
                $provider,
                [
                    PermissionService::PERMISSION_ADMIN,
                    PermissionService::PERMISSION_READ,
                ],
                false
            );
            if (!$checkPermission) {
                continue;
            }
            $providerArray[$i]['id'] = $provider->id;
            $providerArray[$i]['name'] = $provider->name;
            $providerArray[$i]['label'] = $provider->label;
            $i++;
        };
        return $providerArray;
    }

    public function getCategoryById(int $categoryId)
    {
        $category = $this->categoryRepository->findById($categoryId);
        if ($category === null) {
            throw new BadRequestHttpException(sprintf("Category id:%s not found in database.",
                $categoryId
            ));
        }
        return $category;
    }

    private function getCategoryObject(array $data)
    {
        $categoryData = [];
        $categoryData['name'] = $data['name'];
        $categoryData['label'] = $data['label'];
        return $categoryData;
    }

    public function createCategory(User $user, array $data)
    {
        if (empty($providerData['label'])) {
            throw new BadRequestHttpException("Category label is required.");
        }
        if (empty($data['name'])) {
            $data['name'] = UtilHelpers::labelToName($providerData['label'], false, '-');
        }

        $checkCategory = $this->userCategoryRepository->findUserCategoryBy($user, [
            ['name', '=', $data['name']]
        ], false);

        if ($checkCategory instanceof Category) {
            throw new BadRequestHttpException(sprintf("Category (%s) already exists.", $data['name']));
        }
        $categoryData = $this->getCategoryObject($data);

        $getAdminPermission = $this->permissionRepository->findOneBy(
            [["name", '=', PermissionService::PERMISSION_ADMIN]]
        );
        if ($getAdminPermission === null) {
            throw new BadRequestHttpException(
                "Admin permission does not exist."
            );
        }
        $createCategory = $this->categoryRepository->save($categoryData);

        if (!$createCategory) {
            throw new BadRequestHttpException(
                "Error creating category"
            );
        }

        $category = $this->categoryRepository->getModel();
        return $this->userCategoryRepository->createUserCategory(Request::user(), $category, [$getAdminPermission]);
    }

    public function updateCategory(Category $category, array $data)
    {
        $categoryData = $this->getCategoryObject($data);
        return $this->categoryRepository->setModel($category)->save($categoryData);
    }


    public function deleteCategoryById(int $categoryId)
    {
        $category = $this->categoryRepository->findById($categoryId);
        if ($category === null) {
            throw new BadRequestHttpException(sprintf("Category id: %s not found in database.", $categoryId));
        }
        return $this->categoryRepository->setModel($category)->delete();
    }

    public function deleteCategory(Category $category)
    {
        return $this->categoryRepository->setModel($category)->delete();
    }

    public function getPermissionRepository(): PermissionRepository
    {
        return $this->permissionRepository;
    }

    public function getProviderRepository(): ProviderRepository
    {
        return $this->providerRepository;
    }

    public function getCategoryRepository(): CategoryRepository
    {
        return $this->categoryRepository;
    }

    public function getUserCategoryRepository(): CategoryUserRepository
    {
        return $this->userCategoryRepository;
    }

}
