<?php

namespace App\Repositories;

use App\Models\Category;
use App\Models\CategoryUser;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

class CategoryUserRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(CategoryUser::class);
    }

    public function getCategoriesByUserQuery(User $user, ?bool $checkPermissions = true)
    {
        $query = $user->categories();
        $query->whereHas('categoryUser', function ($query) use ($checkPermissions) {
            if ($checkPermissions) {
                $query->whereHas('categoryUserPermission', function ($query) {
                    $query->whereHas('permission', function ($query) {
                        $query->whereIn('name', $this->permissions);
                    });
                });
            }
        });
        $this->resetPermissions();
        return $query;
    }

    public function findCategoriesByUser(User $user, ?bool $checkPermissions = true)
    {
        return $this->getCategoriesByUserQuery($user, $checkPermissions)->get();
    }

    public function findUserCategoryBy(User $user, array $params, ?bool $checkPermissions = true)
    {
        $query = $this->getCategoriesByUserQuery($user, $checkPermissions);
        $query = $this->applyConditionsToQuery($params, $query);
        return $query->first();
    }

    public function createUserCategory(User $user, Category $category, array $permissions = [])
    {
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
