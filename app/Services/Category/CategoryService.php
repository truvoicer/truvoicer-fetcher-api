<?php

namespace App\Services\Category;

use Truvoicer\TruFetcherGet\Models\Category;
use Truvoicer\TruFetcherGet\Models\CategoryUser;
use App\Models\User;
use Truvoicer\TruFetcherGet\Repositories\CategoryRepository;
use Truvoicer\TruFetcherGet\Repositories\PermissionRepository;
use Truvoicer\TruFetcherGet\Repositories\ProviderRepository;
use Truvoicer\TruFetcherGet\Repositories\CategoryUserRepository;
use App\Services\Auth\AuthService;
use App\Services\BaseService;
use App\Services\Permission\AccessControlService;
use App\Services\Permission\PermissionService;
use Truvoicer\TruFetcherGet\Helpers\Tools\UtilHelpers;
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
        parent::__construct();
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

    public function findByParams(string $sort, string $order, int $count = -1, ?bool $pagination = true)
    {
        $this->categoryRepository->setPagination($pagination);
        $this->categoryRepository->setOrderDir($order);
        $this->categoryRepository->setSortField($sort);
        $this->categoryRepository->setLimit($count);
        return $this->categoryRepository->findMany();
    }

    public function findUserCategories(User $user, ?bool $pagination = true)
    {
        $this->userCategoryRepository->setPagination($pagination);
        $this->userCategoryRepository->setPermissions([
            PermissionService::PERMISSION_ADMIN,
            PermissionService::PERMISSION_READ,
        ]);
        return $this->userCategoryRepository->findModelsByUser(
            new Category(),
            $user
        );
    }


    public function getCategorySelectedProvidersList(string $selectedProviders, $user)
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
        foreach ($category->providers()->get() as $provider) {
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
        $fields = [
            'name',
            'label',
        ];
        $categoryData = [];
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $categoryData[$field] = trim($data[$field]);
            }
        }
        return $categoryData;
    }

    public function createCategory(User $user, array $data)
    {
        if (empty($data['label'])) {
            if ($this->throwException) {
                throw new BadRequestHttpException("Category label is required.");
            }
            return false;
        }
        if (empty($data['name'])) {
            $data['name'] = UtilHelpers::labelToName($data['label'], false, '-');
        }

        $checkCategory = $this->userCategoryRepository->findUserModelBy(new Category(), $user, [
            ['name', '=', $data['name']]
        ], false);

        if ($checkCategory instanceof Category) {
            if ($this->throwException) {
                throw new BadRequestHttpException(sprintf("Category (%s) already exists.", $data['name']));
            }
            return false;
        }
        $categoryData = $this->getCategoryObject($data);

        $createCategory = $this->categoryRepository->save($categoryData);

        if (!$createCategory) {
            if ($this->throwException) {
                throw new BadRequestHttpException(
                    "Error creating category"
                );
            }
            return false;
        }

        return $this->permissionEntities->setThrowException($this->throwException)->saveUserEntityPermissions(
            $user,
            $this->categoryRepository->getModel(),
            ['name' => PermissionService::PERMISSION_ADMIN],
        );
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
    public function deleteBatch(array $ids)
    {
        if (!count($ids)) {
            throw new BadRequestHttpException("No category ids provided.");
        }
        return $this->categoryRepository->deleteBatch($ids);
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
