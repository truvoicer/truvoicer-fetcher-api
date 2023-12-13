<?php

namespace App\Repositories;

use App\Models\UserCategory;

class UserCategoryRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(UserCategory::class);
    }

    public function findCategoriesByUser(User $user, string $sort,  string $order, ?int $count)
    {
        $query = $this->createQueryBuilder('userCategory')
            ->where("userCategory.user = :user")
            ->leftJoin('userCategory.category','category')
            ->setParameter("user", $user)
            ->orderBy("category.$sort", $order);

        if ($count !== null && $count > 0) {
            $query->setMaxResults($count);
        }
        return $query->getQuery()
            ->getResult()
            ;
    }

    public function saveUserCategory(UserCategory $userCategory) {
        $this->getEntityManager()->persist($userCategory);
        $this->getEntityManager()->flush();
        return $userCategory;
    }

    public function createUserCategory(User $user, Category $category, array $permissions = []) {
        $userCategory = new UserCategory();
        $userCategory->setUser($user);
        $userCategory->setCategory($category);
        foreach ($permissions as $permission) {
            if ($permission instanceof Permission) {
                $userCategory->addPermission($permission);
            }
        }
        return $this->saveUserCategory($userCategory);
    }

    public function deleteUserCategoriessRelationsByUser(User $user)
    {
        $getUserCategories = $this->findBy(["user" => $user]);
        foreach ($getUserCategories as $userCategory) {
            $this->delete($userCategory);
        }
        return true;
    }

    public function deleteUserCategoriessRelationsByCategory(User $user, Category $category)
    {
        $getUserCategories = $this->findBy(["user" => $user, "category" => $category]);
        foreach ($getUserCategories as $userCategory) {
            $this->delete($userCategory);
        }
        return true;
    }

    public function delete(UserCategory $userCategory) {
        if ($userCategory != null) {
            $this->getEntityManager()->remove($userCategory);
            $this->getEntityManager()->flush();
            return true;
        }
        return false;
    }
}
