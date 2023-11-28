<?php
namespace App\Services\Category;


use App\Models\Category;
use App\Models\User;
use App\Services\Permission\AccessControlService;
use App\Services\Tools\HttpRequestService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class CategoryEntityService extends CategoryService
{
    const ENTITY_NAME = "category";

    protected AccessControlService $accessControlService;

    public function __construct(EntityManagerInterface $entityManager, HttpRequestService $httpRequestService,
                                TokenStorageInterface $tokenStorage, AccessControlService $accessControlService)
    {
        parent::__construct($entityManager, $httpRequestService, $tokenStorage, $accessControlService);
    }

    public function getUserCategoryByUser(User $user, int $categoryId)
    {
        $this->userCategoryRepository->addWhere("category", $this->getCategoryById($categoryId));
        $this->userCategoryRepository->addWhere("user", $user->getId());
        return $this->userCategoryRepository->findOne();
    }
    public function getUserCategoryList(User $user, Category $category)
    {
        $this->userCategoryRepository->addWhere("category", $category);
        $this->userCategoryRepository->addWhere("user", $user->getId());
        return $this->userCategoryRepository->findMany();
    }
    public function getUserCategoryPermissionsListByUser(string $sort, string $order, ?int $count, $user = null)
    {
        $getCategories = $this->userCategoryRepository->findCategoriesByUser(
            ($user === null) ? $this->user : $user,
            $sort,
            $order,
            $count
        );
        return array_map(function ($userCategory) {
            return [
                "category" => $userCategory->getCategory(),
                "permission" => $userCategory->getPermission()
            ];
        }, $getCategories);
    }
    public function deleteUserCategoryPermissions(User $user, Category $category)
    {
        return $this->userCategoryRepository->deleteUserCategoriessRelationsByCategory($user, $category);
    }

    public function saveUserCategoryPermissions(User $user, int $categoryId, array $permissions)
    {
        $getCategory = $this->categoryRepository->find($categoryId);
        if ($getCategory === null) {
            return false;
        }
        $this->userCategoryRepository->deleteUserCategoriessRelationsByCategory($user, $getCategory);
        $buildPermissions = array_map(function ($permission) {
            return $this->permissionRepository->find($permission);
        }, $permissions);
        $this->userCategoryRepository->createUserCategory($user, $getCategory, $buildPermissions);
        return true;
    }
}
