<?php

namespace App\Repositories;

use App\Models\Category;
use App\Models\CategoryUser;
use App\Models\CategoryUserPermission;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CategoryRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(Category::class);
    }

    public function getModel(): Category
    {
        return parent::getModel();
    }

    public function getAllCategoriesArray() {
        return $this->findAll();
    }

    public function findByQuery($query)
    {
        return $this->findByLabelOrName($query);
    }

    public function saveCategory(array $data)
    {
        return $this->save($data);
    }

    public function findByParams(string $sort, string  $order, ?int $count)
    {
        return $this->findAllWithParams($sort, $order, $count);
    }

    public function deleteCategory(Category $category) {
        $this->setModel($category);
        return $this->delete();
    }


    public function userHasEntityPermissions(User $user, Category $category, array $permissions)
    {
        $userCategoryRepository = new CategoryUserRepository();
        $userCategoryRepository->setPermissions($permissions);
        $checkCategory = $userCategoryRepository->findUserCategoryBy($user, [
            ['categories.id', '=', $category->id]
        ]);

        return ($checkCategory instanceof Category);
    }

    public function getUserPermissions(User $user, Category $category)
    {
        $categoryUserId = $user->categories()
            ->where('category_id', '=', $category->id)
            ->withPivot('id')
            ->first()
            ->getOriginal('pivot_id');
        if (!$categoryUserId) {
            return null;
        }

        $catUserRepo = new CategoryUserRepository();
        $catUser = $catUserRepo->findById($categoryUserId);
        if (!$catUser) {
            return null;
        }
        return $catUser->permissions()->get();
    }

    public function getPermissionsListByUser(User $user, string $sort, string $order, ?int $count) {

        return null;
    }

    public function saveUserPermissions(User $user, int $categoryId, array $permissions)
    {

        return null;
    }
    public function deleteUserPermissions(User $user, Category $category)
    {
        return null;
    }
}
