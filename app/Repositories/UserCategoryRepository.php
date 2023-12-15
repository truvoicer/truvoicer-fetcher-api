<?php

namespace App\Repositories;

use App\Models\Category;
use App\Models\Permission;
use App\Models\User;
use App\Models\UserCategory;

class UserCategoryRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(UserCategory::class);
    }

    public function findCategoriesByUser(User $user, string $sort,  string $order, ?int $count)
    {
        $this->addWhere('user_id', $user->id);
        return $this->findAllWithParams($sort, $order, $count);
    }

    public function createUserCategory(User $user, Category $category, array $permissions = []) {
        $saveUserCategory = $user->category()->save($category);
        foreach ($permissions as $permission) {
            if ($permission instanceof Permission) {
                $saveUserCategory->permission()->save($permission);
            }
        }
        return $saveUserCategory;
    }

    public function deleteUserCategoriessRelationsByUser(User $user)
    {
        return $user->category()->delete();
    }

    public function deleteUserCategoriessRelationsByCategory(User $user, Category $category)
    {
        return $user->category()->where('category_id', $category->id)->delete();
    }
}
