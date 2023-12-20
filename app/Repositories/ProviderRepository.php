<?php

namespace App\Repositories;

use App\Models\Property;
use App\Models\Provider;
use App\Models\User;
use App\Models\UserProvider;

class ProviderRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(Provider::class);
    }

    public function getAllProvidersArray() {
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

    public function findByParams(string $sort,  string $order, ?int $count)
    {
        return $this->findAllWithParams($sort, $order, $count);
    }

    public function createProvider(User $user, array $providerData, array $permissions = [])
    {
        $insert = $this->insert($providerData);
        if (!$insert) {
            return false;
        }
        $userProviderRepo = new UserProviderRepository();
        return $userProviderRepo->createUserProvider($user, $this->model, $permissions);
    }

    public function updateProvider(Provider $provider, array $data)
    {
        $this->setModel($provider);
        return $this->save($data);
    }

    public function createProviderProperty(Provider $provider, Property $property, string $propertyValue) {
        return $provider->property()->save($property);
    }

    public function getProviderProperty(Provider $provider, Property $property) {
        return $provider->property()->where('property_id', $property->id)->first();
    }

    public function getProviderPropsByProviderId(int $providerId) {
        $provider = $this->getProviderById($providerId);
        return $provider->property()->get();
    }

    public function deleteProvider(Provider $provider) {
        $this->setModel($provider);
        return $this->delete();
    }
    public function deleteProviderPropsByProvider(Provider $provider) {
        return $provider->property()->delete();
    }

}
