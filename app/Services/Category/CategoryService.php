<?php

namespace App\Services\Category;

use App\Models\Category;
use App\Models\User;
use App\Repositories\CategoryRepository;
use App\Repositories\PermissionRepository;
use App\Repositories\ProviderRepository;
use App\Repositories\UserCategoryRepository;
use App\Services\Auth\AuthService;
use App\Services\BaseService;
use App\Services\Permission\AccessControlService;
use App\Services\Permission\PermissionService;
use App\Services\Provider\ProviderEntityService;
use App\Services\Tools\UtilsService;
use App\Services\User\UserAdminService;
use Illuminate\Support\Facades\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class CategoryService extends BaseService
{
    const SERVICE_ALIAS = CategoryEntityService::class;
    protected PermissionRepository $permissionRepository;
    protected ProviderRepository $providerRepository;
    protected CategoryRepository $categoryRepository;
    protected UserCategoryRepository $userCategoryRepository;
    protected AccessControlService $accessControlService;

    public function __construct(AccessControlService $accessControlService)
    {
        $this->userCategoryRepository = new UserCategoryRepository();
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
        $this->categoryRepository->setOrderBy($order);
        $this->categoryRepository->setSort($sort);
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

    public function findUserPermittedCategories(string $sort, string $order, ?int $count, ?User $user = null) {
        $getCategories = $this->findUserCategories(
            $sort,
            $order,
            $count
        );
        if (
            UserAdminService::userTokenHasAbility($user, AuthService::ABILITY_SUPERUSER) ||
            UserAdminService::userTokenHasAbility($user, AuthService::ABILITY_ADMIN)
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
            $provider = $this->providerRepository->findById((int)$providerId);
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
        foreach ($category->provider()->get() as $provider) {
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

    public function createCategory(array $data)
    {
        if (empty($data['label'])) {
            throw new BadRequestHttpException("Category label is not set.");
        }
        $data['name'] = UtilsService::labelToName($data['label'], false, '-');

        $checkCategory = $this->categoryRepository->findOneBy(
            [["name", '=', $data['name']]]
        );
        if ($checkCategory !== null) {
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
        $this->userCategoryRepository->createUserCategory(Request::user(), $category, [$getAdminPermission]);
        return $category;
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


}
