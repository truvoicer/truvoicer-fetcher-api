<?php

namespace App\Repositories;

use App\Models\Category;
use App\Models\CategoryUser;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Pivot;

class CategoryUserRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(CategoryUser::class);
    }

    public function findCategoriesByUser(User $user, string $sort,  string $order, ?int $count)
    {
        $this->addWhere('user_id', $user->id);
        return $this->findAllWithParams($sort, $order, $count);
    }

    public function createUserCategory(User $user, Category $category, array $permissions = []) {
        $syncCat = $user->categories()->toggle([$category->id]);
        if (!$this->dbHelpers->validateToggle($syncCat, [$category->id])) {
            return false;
        }

        $categoryUserRel = $this->findOneBy([
            ['category_id', '=', $category->id],
            ['user_id', '=', $user->id],
        ]);
        if (!$categoryUserRel) {
            return false;
        }
        foreach ($permissions as $permission) {
            $savePermission = $categoryUserRel->permissions()->toggle([$permission->id]);
            if (!$this->dbHelpers->validateToggle($savePermission, [$permission->id])) {
                return false;
            }
        }
        return true;
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
