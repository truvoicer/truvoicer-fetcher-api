<?php

namespace App\Repositories;

use App\Helpers\Db\DbHelpers;
use App\Models\Category;
use App\Models\Property;
use App\Models\Provider;
use App\Models\User;
use App\Services\Permission\PermissionService;

class ProviderRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(Provider::class);
    }

    public function getModel(): Provider
    {
        return parent::getModel();
    }

    public function getProviderList(?string $sort = "name", ?string $order = "asc", ?int $count = -1)
    {
        $this->setOrderDir($order);
        $this->setSortField($sort);
        $this->setLimit($count);
        return $this->getResults(
            $this->getQuery()
        );
    }

    public function findUserProviders(User $user, ?string $sort = "name", ?string $order = "asc", ?int $count = -1)
    {
        $this->setPermissions([
            PermissionService::PERMISSION_ADMIN,
            PermissionService::PERMISSION_READ,
        ]);
        return $this->getResults(
            $this->getModelByUserQuery(
                new Provider(),
                $user
            )
        );
    }

    public function getAllProvidersArray()
    {
        return $this->findAll();
    }

    public function findByQuery($query)
    {
        return $this->findByLabelOrName($query);
    }

    /**
     * @param int $providerId
     * @return \Illuminate\Database\Eloquent\Model Returns an array of Provider objects
     */
    public function getProviderById(int $providerId)
    {
        return $this->findById($providerId);
    }

    public function findByParams(string $sort, string $order, ?int $count)
    {
        return $this->findAllWithParams($sort, $order, $count);
    }

    public function createProvider(array $providerData)
    {
        return $this->insert($providerData);
    }

    public function updateProvider(Provider $provider, array $data)
    {
        $this->setModel($provider);
        return $this->save($data);
    }


    public function getProviderProperty(Provider $provider, Property $property)
    {
        return $provider->property()->where('property_id', $property->id)->first();
    }

    public function getProviderPropsByProviderId(int $providerId)
    {
        $provider = $this->getProviderById($providerId);
        return $this->getResults($provider->property());
    }

    public function deleteProvider(Provider $provider)
    {
        $this->setModel($provider);
        return $this->delete();
    }

    public function deleteProviderPropsByProvider(Provider $provider)
    {
        return $provider->property()->delete();
    }

    public function userHasEntityPermissions(User $user, Provider $category, array $permissions)
    {
        $this->setPermissions($permissions);
        $checkCategory = $this->findUserModelBy(new Provider(), $user, [
            ['providers.id', '=', $category->id]
        ]);

        return ($checkCategory instanceof Provider);
    }

    public function getUserPermissions(User $user, Provider $provider)
    {
        $providerUserId = $user->providers()
            ->where('provider_id', '=', $provider->id)
            ->withPivot('id')
            ->first()
            ->getOriginal('pivot_id');
        if (!$providerUserId) {
            return null;
        }

        $providerUserRepo = new ProviderUserRepository();
        $providerUser = $providerUserRepo->findById($providerUserId);
        if (!$providerUser) {
            return null;
        }
        return $this->getResults($providerUser->permissions());
    }

    public function getPermissionsListByUser(User $user, int $id, string $sort, string $order, ?int $count)
    {
        return $this->getResults(
            $user
                ->providerPermissions()
                ->whereHas('providerUser', function ($query) use ($id) {
                    $query->where('provider_id', $id);
                })
                ->with('permission')
        );
    }

    public function deleteUserPermissions(User $user, Provider $provider)
    {
        return null;
    }

    public function saveProviderCategoryEntities(Provider $provider, string $relatedEntityClass, array $categoryIds)
    {
        if (!$provider->exists) {
            return false;
        }
        $provider->categories()->whereNotIn('category_id', $categoryIds)->detach();
        $provider->categories()->sync($categoryIds);
        return true;
    }
}
